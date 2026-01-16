<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InversionPagadaClienteMail;
use App\Mail\InversionPagadaAdminMail;
use App\Models\Inversion;
use App\Models\MovimientoCaja;
use App\Models\UserInversion;
use App\Services\SaldoDisponibleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;

class UserInversionSaldoController extends Controller
{
    public function __construct(private SaldoDisponibleService $saldoService) {}

    /**
     * POST /api/inversiones/{id}/pay-saldo
     */
    public function paySaldo(Request $request, int $id)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        $inv = UserInversion::query()
            ->where('id', $id)
            ->where('id_cliente', $cliente->id)
            ->first();

        if (!$inv) {
            return response()->json(['ok'=>false,'error'=>'Inversión no encontrada.'], 404);
        }
        if ((int)$inv->status !== 1) {
            return response()->json(['ok'=>false,'error'=>'Solo se puede pagar una inversión Pendiente.'], 409);
        }

        $monto = (float)($inv->inversion ?? 0);
        if ($monto <= 0) {
            return response()->json(['ok'=>false,'error'=>'Monto inválido.'], 422);
        }

        try {
            $detalle = DB::transaction(function () use ($cliente, $inv, $monto) {

                // 1) Consumir saldo (depósitos → inversiones terminadas → ahorros)
                $detalleSaldo = $this->saldoService->consumePreferDepositos($cliente->id, $monto);

                // 2) Activar inversión
                $u = UserInversion::query()->lockForUpdate()->findOrFail($inv->id);
                if ((int)$u->status === 2) {
                    return $detalleSaldo;
                }

                $plan  = Inversion::find($u->id_activo);

                // meses: primero tiempo, si no, sacarlo de plan->periodo
                $meses = (int)($u->tiempo ?: $this->monthsFromPlan($plan));
                if ($meses <= 0) $meses = 1;

                $inicio = now();
                $fin    = (clone $inicio)->addMonthsNoOverflow($meses);

                $u->status = 2;
                $u->fecha_inicio = $inicio;

                // ✅ FIX: guardar fecha_fin (y fecha_fin_calc si existe)
                $table = $u->getTable();

                if (Schema::hasColumn($table, 'fecha_fin')) {
                    $u->fecha_fin = $fin;
                }
                if (Schema::hasColumn($table, 'fecha_fin_calc')) {
                    $u->fecha_fin_calc = $fin;
                }

                $u->capital_actual = $monto;
                if (Schema::hasColumn($table, 'interes_acumulado')) $u->interes_acumulado = 0;
                if (Schema::hasColumn($table, 'rendimiento_generado')) $u->rendimiento_generado = 0;
                if (Schema::hasColumn($table, 'fecha_ultimo_calculo')) $u->fecha_ultimo_calculo = $inicio;

                if (Schema::hasColumn($table, 'payment_method')) $u->payment_method = 'saldo';
                if (Schema::hasColumn($table, 'payment_status')) $u->payment_status = 'paid';

                $u->save();

                // 3) Movimiento caja (best-effort)
                try {
                    $mov  = new MovimientoCaja();
                    $tMov = $mov->getTable();

                    $data = [];

                    if (Schema::hasColumn($tMov, 'id_caja') && !empty($u->id_caja)) {
                        $data['id_caja'] = (int)$u->id_caja;
                    }

                    foreach (['monto','cantidad','importe','total'] as $c) {
                        if (Schema::hasColumn($tMov, $c)) { $data[$c] = $monto; break; }
                    }

                    if (Schema::hasColumn($tMov, 'monto_anterior')) $data['monto_anterior'] = 0;
                    if (Schema::hasColumn($tMov, 'monto_nuevo'))    $data['monto_nuevo']    = $monto;

                    if (Schema::hasColumn($tMov, 'descripcion')) $data['descripcion'] = "Pago inversión #{$u->id} (Saldo)";
                    if (Schema::hasColumn($tMov, 'origen'))      $data['origen'] = 'inversion';
                    if (Schema::hasColumn($tMov, 'origen_id'))   $data['origen_id'] = $u->id;
                    if (Schema::hasColumn($tMov, 'fecha'))       $data['fecha'] = now();

                    if (Schema::hasColumn($tMov, 'id_usuario'))  $data['id_usuario'] = (int)($cliente->id ?? 0);
                    if (Schema::hasColumn($tMov, 'tipo'))        $data['tipo'] = 'entrada';
                    if (Schema::hasColumn($tMov, 'status'))      $data['status'] = 1;

                    if (count($data) >= 2) DB::table($tMov)->insert($data);
                } catch (\Throwable $e) {
                    Log::warning('paySaldo inversión: no se pudo crear movimiento caja', [
                        'invId'=>$u->id,
                        'ex'=>$e->getMessage(),
                    ]);
                }

                return $detalleSaldo;
            });

        } catch (\RuntimeException $e) {
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        // Normalizar origenes para correos
        $origenes = [];
        try {
            $origenes = $this->normalizarOrigenesSaldo($detalle, (int)$cliente->id);
        } catch (\Throwable $e) {
            Log::warning('No se pudo normalizar origenes saldo', [
                'inv_id' => $inv->id,
                'ex'     => $e->getMessage(),
            ]);
        }

        // Correo al cliente (best-effort)
        try {
            $invMail = UserInversion::with('plan')->find($inv->id);

            if (!empty($cliente->email)) {
                Mail::to($cliente->email)->send(new InversionPagadaClienteMail(
                    $invMail,
                    $cliente,
                    'saldo',
                    $origenes
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de inversión pagada (saldo) al cliente', [
                'inv_id' => $inv->id,
                'ex'     => $e->getMessage(),
            ]);
        }

        // Correo al admin (best-effort)
        try {
            $invAdmin = UserInversion::with('plan','caja')->find($inv->id);

            $adminEmail = trim((string) config('services.admin.email'));
            if ($adminEmail !== '') {
                Mail::to($adminEmail)
                    ->send(new InversionPagadaAdminMail($invAdmin, $cliente, $origenes));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de inversión pagada (saldo) al admin', [
                'inv_id' => $inv->id,
                'ex'     => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ok'              => true,
            'message'         => 'Inversión pagada con saldo y activada.',
            'saldo_consumido' => $detalle,
        ]);
    }

    /** Extrae meses desde plan->periodo (numérico o “X meses”). */
    private function monthsFromPlan($plan): int
    {
        if (!$plan) return 1;

        $periodo = trim((string)($plan->periodo ?? ''));
        if ($periodo === '') return 1;

        if (is_numeric($periodo)) return max(1, (int)$periodo);

        if (preg_match('/\d+/', $periodo, $m)) {
            return max(1, (int)$m[0]);
        }

        return 1;
    }

    /**
     * Convierte el detalle del saldoService a una lista para el correo,
     * e intenta agregar fecha consultando la BD por IDs.
     */
    private function normalizarOrigenesSaldo(array $detalle, int $clienteId): array
    {
        $out = [];

        // DEPÓSITOS
        $depIds = collect($detalle['deposits'] ?? [])->pluck('id')->filter()->unique()->values();
        $depMap = collect();

        if ($depIds->isNotEmpty() && Schema::hasTable('user_depositos')) {
            $cols = ['id','id_cliente'];

            if (Schema::hasColumn('user_depositos','fecha_deposito')) $cols[] = 'fecha_deposito';
            elseif (Schema::hasColumn('user_depositos','fecha_alta')) $cols[] = 'fecha_alta';
            elseif (Schema::hasColumn('user_depositos','created_at')) $cols[] = 'created_at';

            $depMap = DB::table('user_depositos')
                ->where('id_cliente', $clienteId)
                ->whereIn('id', $depIds)
                ->get($cols)
                ->keyBy('id');
        }

        foreach (($detalle['deposits'] ?? []) as $d) {
            $row = $depMap->get($d['id'] ?? null);

            $fecha = $row->fecha_deposito
                ?? $row->fecha_alta
                ?? $row->created_at
                ?? null;

            $out[] = [
                'tipo'  => 'Depósito',
                'ref'   => !empty($d['id']) ? '#'.$d['id'] : null,
                'monto' => $d['monto'] ?? null,
                'fecha' => $fecha ? Carbon::parse($fecha)->format('Y-m-d H:i:s') : null,
            ];
        }

        // INVERSIONES TERMINADAS
        $invIds = collect($detalle['inversiones'] ?? [])->pluck('id')->filter()->unique()->values();
        $invMap = collect();

        if ($invIds->isNotEmpty() && Schema::hasTable('user_inversiones')) {
            $cols = ['id','id_cliente'];
            if (Schema::hasColumn('user_inversiones','fecha_fin')) $cols[] = 'fecha_fin';
            elseif (Schema::hasColumn('user_inversiones','updated_at')) $cols[] = 'updated_at';

            $invMap = DB::table('user_inversiones')
                ->where('id_cliente', $clienteId)
                ->whereIn('id', $invIds)
                ->get($cols)
                ->keyBy('id');
        }

        foreach (($detalle['inversiones'] ?? []) as $d) {
            $row = $invMap->get($d['id'] ?? null);
            $fecha = $row->fecha_fin ?? $row->updated_at ?? null;

            $out[] = [
                'tipo'  => 'Inversión terminada',
                'ref'   => !empty($d['id']) ? '#'.$d['id'] : null,
                'monto' => $d['monto'] ?? null,
                'fecha' => $fecha ? Carbon::parse($fecha)->format('Y-m-d') : null,
            ];
        }

        // AHORROS
        $ahoIds = collect($detalle['ahorros'] ?? [])->pluck('id')->filter()->unique()->values();
        $ahoMap = collect();

        if ($ahoIds->isNotEmpty() && Schema::hasTable('user_ahorro')) {
            $cols = ['id','id_cliente'];
            if (Schema::hasColumn('user_ahorro','fecha_inicio')) $cols[] = 'fecha_inicio';
            elseif (Schema::hasColumn('user_ahorro','created_at')) $cols[] = 'created_at';

            $ahoMap = DB::table('user_ahorro')
                ->where('id_cliente', $clienteId)
                ->whereIn('id', $ahoIds)
                ->get($cols)
                ->keyBy('id');
        }

        foreach (($detalle['ahorros'] ?? []) as $d) {
            $row = $ahoMap->get($d['id'] ?? null);
            $fecha = $row->fecha_inicio ?? $row->created_at ?? null;

            $out[] = [
                'tipo'  => 'Ahorro',
                'ref'   => !empty($d['id']) ? '#'.$d['id'] : null,
                'monto' => $d['monto'] ?? null,
                'fecha' => $fecha ? Carbon::parse($fecha)->format('Y-m-d') : null,
            ];
        }

        return $out;
    }
}
