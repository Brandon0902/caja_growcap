<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NuevaSolicitudRetiroAdminMail;
use App\Mail\NuevaSolicitudRetiroClienteMail;
use App\Models\Cliente;
use App\Models\Retiro;
use App\Models\RetiroAhorro;
use App\Models\User;
use App\Notifications\NuevaSolicitudNotification;
use App\Services\SaldoDisponibleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RetirosClienteApiController extends Controller
{
    public function __construct(private SaldoDisponibleService $saldoService) {}

    /** Resuelve al cliente autenticado (Sanctum). */
    private function cliente(Request $request): Cliente
    {
        $u = auth('sanctum')->user() ?? $request->user();
        if ($u instanceof Cliente) return $u;

        if ($u && isset($u->id_cliente) && $u->id_cliente) {
            $c = Cliente::find($u->id_cliente);
            if ($c) return $c;
        }
        throw new AuthenticationException('El token no corresponde a un cliente.');
    }

    /** Resuelve id_caja: toma la caja de una inversión activa; si no, la primera caja. */
    private function resolverIdCajaParaCliente(int $clienteId): ?int
    {
        // 1) Caja de inversiones activas del cliente (status=2)
        if (Schema::hasTable('user_inversiones') && Schema::hasColumn('user_inversiones','id_caja')) {
            $cajaInv = DB::table('user_inversiones')
                ->where('id_cliente', $clienteId)
                ->when(
                    Schema::hasColumn('user_inversiones','status'),
                    fn($q)=>$q->where('status',2)
                )
                ->orderByDesc('id')
                ->value('id_caja');
            if ($cajaInv) return (int) $cajaInv;
        }

        // 2) Primera caja disponible en "cajas"
        if (Schema::hasTable('cajas')) {
            $primera = DB::table('cajas')->orderBy('id_caja')->value('id_caja');
            if ($primera) return (int) $primera;
        }

        return null;
    }

    /** GET /api/client/retiros/saldos */
    public function saldos(Request $request)
    {
        $cliente = $this->cliente($request);
        $debug   = $request->boolean('debug');

        $saldoAhorro = 0.0;
        if (Schema::hasTable('user_ahorro') && Schema::hasColumn('user_ahorro','saldo_disponible')) {
            $q = DB::table('user_ahorro')->where('id_cliente', $cliente->id);
            if (Schema::hasColumn('user_ahorro','status')) $q->where('status', 2);
            $saldoAhorro = (float) $q->sum('saldo_disponible');
        }

        $detalle = $this->saldoService->forCliente((int)$cliente->id);
        $saldoGlobal = (float)($detalle['total'] ?? 0);

        Log::info('[API:Retiros] saldos()', [
            'cliente_id'   => $cliente->id,
            'ahorro'       => $saldoAhorro,
            'saldo_global' => $saldoGlobal,
            'debug'        => $debug,
        ]);

        return response()->json([
            'ok'     => true,
            'fecha'  => now()->isoFormat('dddd DD [de] MMMM [de] YYYY'),
            'saldos' => [
                'ahorro'      => round($saldoAhorro, 2),
                'inversiones' => round($saldoGlobal, 2),
            ],
        ]);
    }

    /** GET /api/client/retiros/ahorros -> lista de ahorros con saldo (>0) y status=2 */
    public function ahorrosConSaldo(Request $request)
    {
        $cliente = $this->cliente($request);

        $q = DB::table('user_ahorro as ua')
            ->leftJoin('ahorros as a', 'a.id', '=', 'ua.ahorro_id')
            ->where('ua.id_cliente', $cliente->id)
            ->where('ua.saldo_disponible', '>', 0);

        if (Schema::hasColumn('user_ahorro', 'status')) {
            $q->where('ua.status', 2);
        }

        $rows = $q->selectRaw('ua.id, ua.saldo_disponible, ua.frecuencia_pago, ua.fecha_fin, a.tipo_ahorro as plan')
            ->orderBy('ua.id', 'desc')
            ->get()
            ->map(function ($r) {
                return [
                    'id'               => (int) $r->id,
                    'plan'             => $r->plan ?: ('Ahorro #'.$r->id),
                    'saldo_disponible' => (float) $r->saldo_disponible,
                    'frecuencia'       => $r->frecuencia_pago,
                    'fecha_corte'      => $r->fecha_fin,
                ];
            });

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    /** POST /api/client/retiros/ahorro  { ahorro_id, tipo, monto, nip } */
    public function solicitarAhorro(Request $request)
    {
        $cliente = $this->cliente($request);
        Log::info('[API:Retiros] solicitarAhorro IN', ['cliente'=>$cliente->id, 'payload'=>$request->all()]);

        $data = $request->validate([
            'ahorro_id' => ['required','integer','min:1'],
            'tipo'      => ['required','string','max:100'],
            'monto'     => ['required','numeric','min:1000'],
            'nip'       => ['required','string','max:100'],
            'id_caja'   => ['nullable','integer','min:1'],
        ]);

        // NIP
        $nipDb = DB::table('user_data')->where('id_cliente', $cliente->id)->value('nip');
        if (!$nipDb || trim((string)$data['nip']) !== trim((string)$nipDb)) {
            Log::warning('[API:Retiros] NIP inválido (ahorro)', ['cliente'=>$cliente->id]);
            return response()->json(['ok'=>false,'error'=>'NIP inválido.'], 422);
        }

        $monto = (float) $data['monto'];
        $montoSql = number_format($monto, 2, '.', '');

        return DB::transaction(function () use ($cliente, $data, $monto, $montoSql) {

            // Ahorro (debe ser status=2) y lock para evitar doble gasto
            $q = DB::table('user_ahorro')
                ->where('id_cliente', $cliente->id)
                ->where('id', (int)$data['ahorro_id']);

            if (Schema::hasColumn('user_ahorro','status')) {
                $q->where('status', 2);
            }

            $ahorro = $q->lockForUpdate()->first();

            if (!$ahorro) {
                return response()->json(['ok'=>false,'error'=>'Ahorro no encontrado o inactivo.'], 404);
            }

            $disp = (float) ($ahorro->saldo_disponible ?? 0);
            if ($monto > $disp) {
                return response()->json(['ok'=>false,'error'=>'Saldo disponible insuficiente en ese ahorro.'], 422);
            }

            // ✅ Descontar ya
            DB::table('user_ahorro')
                ->where('id', (int)$data['ahorro_id'])
                ->update([
                    'saldo_disponible' => DB::raw("saldo_disponible - {$montoSql}"),
                ]);

            // Resolver id_caja
            $idCaja = !empty($data['id_caja']) ? (int)$data['id_caja'] : $this->resolverIdCajaParaCliente($cliente->id);
            if ($idCaja === null) {
                throw new \RuntimeException('No hay caja disponible para registrar el retiro.');
            }

            $r = RetiroAhorro::create([
                'id_cliente'      => $cliente->id,
                'id_ahorro'       => (int)$data['ahorro_id'],
                'tipo'            => $data['tipo'],
                'cantidad'        => $monto,
                'fecha_solicitud' => now(),
                'status'          => 0,
                'created_at'      => now(),
                'id_caja'         => $idCaja,
                // ya se descontó en user_ahorro
                'descuento_aplicado' => 1,
            ]);

            Log::info('[API:Retiros] solicitarAhorro OK', ['retiro_id'=>$r->id, 'id_caja'=>$idCaja]);

            // ===== CORREOS: nueva solicitud retiro por AHORRO =====
            try {
                $adminEmail = config('services.retiros.admin_email') ?? config('mail.from.address');

                // correo al admin con datos del cliente + retiro
                if ($adminEmail) {
                    Mail::to($adminEmail)->send(
                        new NuevaSolicitudRetiroAdminMail($cliente, $r, 'ahorro')
                    );
                }

                // correo al cliente (sin mostrar el id del retiro en la vista)
                if (!empty($cliente->email)) {
                    Mail::to($cliente->email)->send(
                        new NuevaSolicitudRetiroClienteMail($cliente, $r, 'ahorro')
                    );
                }
            } catch (\Throwable $e) {
                Log::error('[API:Retiros] error enviando correos nueva solicitud ahorro', [
                    'cliente_id' => $cliente->id,
                    'retiro_id'  => $r->id,
                    'ex'         => $e,
                ]);
            }

            $clienteNombre = trim(sprintf('%s %s', (string)($cliente->nombre ?? ''), (string)($cliente->apellido ?? '')));
            $titulo = 'Nuevo retiro de ahorro';
            $mensaje = $clienteNombre !== '' ? "Cliente {$clienteNombre} solicitó un retiro." : 'Se solicitó un retiro de ahorro.';
            $url = route('retiros.index');

            User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
                $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
            });

            return response()->json([
                'ok'        => true,
                'message'   => 'Solicitud de retiro de ahorro enviada.',
                'retiro_id' => $r->id,
            ], 201);
        });
    }

    /** POST /api/client/retiros/inversion  { tipo, monto, nip } */
    public function solicitarInversion(Request $request)
    {
        $cliente = $this->cliente($request);
        Log::info('[API:Retiros] solicitarInversion IN', ['cliente'=>$cliente->id, 'payload'=>$request->all()]);

        $data = $request->validate([
            'tipo'    => ['required','string','max:100'],
            'monto'   => ['required','numeric','min:1000'],
            'nip'     => ['required','string','max:100'],
            'id_caja' => ['nullable','integer','min:1'],
        ]);

        // NIP
        $nipDb = DB::table('user_data')->where('id_cliente', $cliente->id)->value('nip');
        if (!$nipDb || trim((string)$data['nip']) !== trim((string)$nipDb)) {
            Log::warning('[API:Retiros] NIP inválido (inv)', ['cliente'=>$cliente->id]);
            return response()->json(['ok'=>false,'error'=>'NIP inválido.'], 422);
        }

        $monto = (float) $data['monto'];

        // ✅ Consumir usando el mismo flujo que saldo disponible
        try {
            $consumo = $this->saldoService->consumePreferDepositos((int)$cliente->id, $monto);
        } catch (\RuntimeException $e) {
            Log::warning('[API:Retiros] saldo insuficiente para retiro inversion', [
                'cliente_id' => $cliente->id,
                'monto'      => $monto,
                'msg'        => $e->getMessage(),
            ]);
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('[API:Retiros] error en consumePreferDepositos', [
                'cliente_id' => $cliente->id,
                'monto'      => $monto,
                'ex'         => $e->getMessage(),
            ]);
            return response()->json([
                'ok'    => false,
                'error' => 'No se pudo procesar el retiro. Inténtalo de nuevo.',
            ], 500);
        }

        Log::info('[API:Retiros] solicitarInversion consumo saldo', [
            'cliente_id' => $cliente->id,
            'monto'      => $monto,
            'detalle'    => $consumo,
        ]);

        // Resolver id_caja
        $idCaja = !empty($data['id_caja']) ? (int)$data['id_caja'] : $this->resolverIdCajaParaCliente($cliente->id);
        if ($idCaja === null) {
            Log::error('[API:Retiros] sin caja disponible para retiro inversion', [
                'cliente_id' => $cliente->id,
            ]);
            return response()->json([
                'ok'    => false,
                'error' => 'No hay caja disponible para registrar el retiro.',
            ], 500);
        }

        // Crear retiro (descuento YA aplicado por el servicio)
        $r = Retiro::create([
            'id_cliente'        => $cliente->id,
            'tipo'              => $data['tipo'],
            'cantidad'          => $monto,
            'fecha_solicitud'   => now(),
            'status'            => 0,
            'id_caja'           => $idCaja,
            'descuento_aplicado'=> 1,
        ]);

        // Guardar resumen + detalle completo si existen las columnas
        if (Schema::hasTable('retiros')) {
            $update = [];

            // primeros ids usados por tipo
            $primDep = !empty($consumo['deposits'][0]['id'])    ? (int)$consumo['deposits'][0]['id']    : null;
            $primInv = !empty($consumo['inversiones'][0]['id']) ? (int)$consumo['inversiones'][0]['id'] : null;
            $primAho = !empty($consumo['ahorros'][0]['id'])     ? (int)$consumo['ahorros'][0]['id']     : null;

            if (Schema::hasColumn('retiros','id_user_deposito') && $primDep) {
                $update['id_user_deposito'] = $primDep;
            }
            if (Schema::hasColumn('retiros','id_user_inversion') && $primInv) {
                $update['id_user_inversion'] = $primInv;
            }
            if (Schema::hasColumn('retiros','id_user_ahorro') && $primAho) {
                $update['id_user_ahorro'] = $primAho;
            }
            if (Schema::hasColumn('retiros','detalle_consumo')) {
                $update['detalle_consumo'] = json_encode($consumo, JSON_UNESCAPED_UNICODE);
            }

            if (!empty($update)) {
                DB::table('retiros')
                    ->where('id', $r->id)
                    ->update($update);
            }
        }

        Log::info('[API:Retiros] solicitarInversion OK', [
            'retiro_id' => $r->id,
            'id_caja'   => $idCaja,
        ]);

        // ===== CORREOS =====
        try {
            $adminEmail = config('services.retiros.admin_email') ?? config('mail.from.address');

            if ($adminEmail) {
                Mail::to($adminEmail)->send(
                    new NuevaSolicitudRetiroAdminMail($cliente, $r, 'inversion')
                );
            }

            if (!empty($cliente->email)) {
                Mail::to($cliente->email)->send(
                    new NuevaSolicitudRetiroClienteMail($cliente, $r, 'inversion')
                );
            }
        } catch (\Throwable $e) {
            Log::error('[API:Retiros] error enviando correos nueva solicitud inversion', [
                'cliente_id' => $cliente->id,
                'retiro_id'  => $r->id,
                'ex'         => $e,
            ]);
        }

        $clienteNombre = trim(sprintf('%s %s', (string)($cliente->nombre ?? ''), (string)($cliente->apellido ?? '')));
        $titulo = 'Nuevo retiro de inversión';
        $mensaje = $clienteNombre !== '' ? "Cliente {$clienteNombre} solicitó un retiro." : 'Se solicitó un retiro de inversión.';
        $url = route('retiros.index');

        User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
            $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
        });

        return response()->json([
            'ok'        => true,
            'message'   => 'Solicitud de retiro enviada.',
            'retiro_id' => $r->id,
            'detalle'   => $consumo,
        ], 201);
    }

    /** GET /api/client/retiros/mis */
    public function misRetiros(Request $request)
    {
        $cliente = $this->cliente($request);

        $inv = Retiro::where('id_cliente', $cliente->id)
            ->orderByDesc('fecha_solicitud')
            ->limit(50)
            ->get();

        $ah  = RetiroAhorro::where('id_cliente', $cliente->id)
            ->orderByDesc('fecha_solicitud')
            ->limit(50)
            ->get();

        return response()->json([
            'ok'          => true,
            'inversiones' => $inv,
            'ahorro'      => $ah,
        ]);
    }
}