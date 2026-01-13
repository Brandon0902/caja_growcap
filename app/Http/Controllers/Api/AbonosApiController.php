<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPrestamo;
use App\Models\UserAbono;
use App\Services\MoraService;
use App\Services\AbonoService;
use App\Models\User;
use App\Notifications\NuevaSolicitudNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

use Illuminate\Support\Facades\Mail;
use App\Mail\AbonoPagadoMail;
use App\Models\Cliente;

class AbonosApiController extends Controller
{
    public function __construct(
        private MoraService $moraService,
        private AbonoService $abonoService
    ) {}

    protected function pickCol(string $table, array $candidates): ?string
    {
        if (!Schema::hasTable($table)) return null;
        foreach ($candidates as $c) if (Schema::hasColumn($table, $c)) return $c;
        return null;
    }

    protected function computeSaldoDisponible(int $clienteId): float
    {
        $today = now()->toDateString();

        $tblInv = 'user_inversiones';
        $tblAho = 'user_ahorro';
        $tblDep = 'user_depositos';

        $hasInv = Schema::hasTable($tblInv);
        $hasAho = Schema::hasTable($tblAho);
        $hasDep = Schema::hasTable($tblDep);

        $invPrincipalCol = $this->pickCol($tblInv, ['inversion','monto','capital','cantidad']);
        $invRendCol      = $this->pickCol($tblInv, ['rendimiento_generado','rend_gen','ganancia','interes_generado']);
        $invRet1Col      = $this->pickCol($tblInv, ['retiros','monto_retirado']);
        $invRet2Col      = $this->pickCol($tblInv, ['retiros_echos','retiros_hechos']);
        $invStatusCol    = $this->pickCol($tblInv, ['status']);

        $ahoDispCol     = $this->pickCol($tblAho, ['saldo_disponible','saldo_disp']);
        $ahoStatusCol   = $this->pickCol($tblAho, ['status']);
        $ahoFechaFinCol = $this->pickCol($tblAho, ['fecha_fin']);

        $depMontoCol  = $this->pickCol($tblDep, ['cantidad','monto']);
        $depStatusCol = $this->pickCol($tblDep, ['status']);

        $sd_ahorros = 0.0;
        if ($hasAho && $ahoDispCol) {
            $q = DB::table($tblAho)->where("$tblAho.id_cliente", $clienteId);
            if ($ahoStatusCol || $ahoFechaFinCol) {
                $q->where(function($w) use ($tblAho, $ahoStatusCol, $ahoFechaFinCol, $today){
                    if ($ahoStatusCol)   $w->orWhere("$tblAho.$ahoStatusCol", 6);
                    if ($ahoFechaFinCol) $w->orWhereDate("$tblAho.$ahoFechaFinCol", '<=', $today);
                });
            }
            $sd_ahorros = (float) $q->sum($ahoDispCol);
        }

        $sd_inversiones = 0.0;
        if ($hasInv) {
            $expr = '('
                . ($invPrincipalCol ?: '0') . ' + '
                . ($invRendCol      ?: '0') . ' - '
                . ($invRet1Col      ?: '0') . ' - '
                . ($invRet2Col      ?: '0')
                . ')';
            $q = DB::table($tblInv)->where("$tblInv.id_cliente", $clienteId);
            if ($invStatusCol) $q->where("$tblInv.$invStatusCol", 1);
            $sd_inversiones = (float) $q->sum(DB::raw($expr));
        }

        $sd_depositos = 0.0;
        if ($hasDep && $depMontoCol) {
            $numExpr = "CAST(REPLACE(REPLACE($tblDep.$depMontoCol, ',', ''), '$','') AS DECIMAL(18,2))";
            $q = DB::table($tblDep)->where("$tblDep.id_cliente", $clienteId);
            if ($depStatusCol) $q->where("$tblDep.$depStatusCol", 1);
            $sd_depositos = (float) $q->sum(DB::raw($numExpr));
        }

        return (float) round(max(0, $sd_ahorros) + max(0, $sd_inversiones) + max(0, $sd_depositos), 2);
    }

    public function index(Request $request)
    {
        $cliente = auth()->user();
        abort_if(!$cliente, 401);

        $prestamos = UserPrestamo::query()
            ->where('id_cliente', $cliente->id)
            ->where('status', 5)
            ->orderByDesc('fecha_inicio')
            ->get();

        $items = $prestamos->map(function (UserPrestamo $p) {
            $abonos = UserAbono::where('user_prestamo_id', $p->id)
                ->orderBy('num_pago')
                ->get();

            $total = (float) ($p->monto_total ?? 0);
            if ($total <= 0) {
                $total = (float) $abonos->max('saldo_restante') ?: (float) $abonos->sum('cantidad');
            }

            $pagadoReal = (float) UserAbono::where('user_prestamo_id', $p->id)
                ->whereIn('status', [1, 4])
                ->sum(DB::raw('COALESCE(pago_monto, cantidad)'));

            $saldoAFavor = (float) ($p->saldo_a_favor ?? 0);
            $saldoRest   = max(0, round($total - $pagadoReal - $saldoAFavor, 2));

            return [
                'id'              => $p->id,
                'descripcion'     => $p->descripcion ?? "Préstamo #{$p->id}",
                'monto_total'     => $total,
                'monto_pagado'    => $pagadoReal,
                'saldo_a_favor'   => $saldoAFavor,
                'saldo_restante'  => $saldoRest,
                'estado'          => 'al_corriente',
                'abonos'          => $abonos->map(function (UserAbono $a) {
                    $moraEstimada = app(MoraService::class)->calcular($a, Carbon::today());
                    $st = (int) $a->status;

                    $estado = match ($st) {
                        1, 4 => 'pagado',
                        2    => 'vencido',
                        default => $moraEstimada['aplica'] ? 'vencido' : 'pendiente',
                    };

                    // ✅ FECHA (para la vista)
                    // Tu tabla trae fecha_vencimiento; la vista usa a.fecha y a.fecha_vencimiento.
                    $rawFecha = $a->fecha_vencimiento ?? $a->fecha ?? null;
                    $fechaISO = $rawFecha ? Carbon::parse($rawFecha)->toDateString() : null;

                    return [
                        'id'               => $a->id,
                        'status'           => $st,

                        // ✅ la vista usa a.numero
                        'numero'           => (int) $a->num_pago,
                        'numero_pago'      => (int) $a->num_pago,

                        // ✅ la vista usa a.fecha, y venceDe(a) busca fecha_vencimiento/vencimiento/fecha...
                        'fecha'            => $fechaISO,
                        'fecha_vencimiento'=> $fechaISO,
                        'vencimiento'      => $fechaISO,

                        'programado'       => (float) $a->cantidad,

                        'mora_generada'    => (float) ($a->mora_generada ?? 0)
                                             ?: ($moraEstimada['aplica'] ? $moraEstimada['total'] : 0.0),

                        'estado'           => $estado,

                        'pagado_stripe'    => ($st === 4),
                        'pendiente_caja'   => ($st === 4),

                        'pago_monto'       => $a->pago_monto ? (float) $a->pago_monto : null,
                        'pago_at'          => $a->pago_at ? (string) $a->pago_at : null,
                    ];
                }),
            ];
        });

        return response()->json([
            'fecha'            => Carbon::now()->toDateString(),
            'saldo_disponible' => $this->computeSaldoDisponible((int)$cliente->id),
            'prestamos'        => $items,
        ]);
    }

    public function pagar(Request $request)
    {
        $cliente = auth()->user();
        abort_if(!$cliente, 401);

        $data = $request->validate([
            'abono_id'           => ['required','integer', Rule::exists('user_abonos','id')],
            'monto'              => ['required','numeric','min:0.01'],
            'referencia'         => ['nullable','string','max:120'],
            'liquidar_intereses' => ['nullable','boolean'],
        ]);

        try {
            $abono    = UserAbono::findOrFail($data['abono_id']);
            $prestamo = UserPrestamo::findOrFail($abono->user_prestamo_id);

            $breakdown = $this->abonoService->pagar(
                $prestamo,
                $abono,
                (float)$data['monto'],
                $data['referencia'] ?? null,
                now(),
                (bool)$data['liquidar_intereses']
            );

            Mail::to('admingrowcap@casabarrel.com')
                ->send(new AbonoPagadoMail($cliente, $prestamo, $abono, (float)$data['monto'], $breakdown));

            $clienteNombre = trim(sprintf('%s %s', (string)($cliente->nombre ?? ''), (string)($cliente->apellido ?? '')));
            $titulo = 'Nuevo abono registrado';
            $mensaje = $clienteNombre !== '' ? "Cliente {$clienteNombre} registró un abono." : 'Se registró un nuevo abono.';
            $url = route('user_prestamos.show', $prestamo);

            User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
                $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
            });

            return response()->json([
                'message'          => 'Abono pagado correctamente.',
                'detalle_pagos'    => $breakdown['pagos'],
                'saldo_a_favor'    => $breakdown['saldo_a_favor'],
                'saldo_restante'   => $breakdown['saldo_restante'],
                'monto_pagado'     => $breakdown['monto_pagado'],
                'saldo_disponible' => $this->computeSaldoDisponible((int)$cliente->id),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en AbonosApiController@pagar', ['ex' => $e]);
            return response()->json(['message' => 'Error interno.'], 500);
        }
    }
}
