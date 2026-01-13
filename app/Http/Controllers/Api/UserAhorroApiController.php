<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ahorro;
use App\Models\Caja;
use App\Models\UserAhorro;
use App\Models\Cliente;
use App\Models\User;
use App\Notifications\NuevaSolicitudNotification;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

// ==== HELPERS / SUPPORT ====
use App\Domain\Ahorros\Support\NipHelper;
use App\Domain\Ahorros\Support\AhorroGuard;
use App\Domain\Ahorros\Support\MovimientosHelper;
use App\Domain\Ahorros\Support\AhorroPlanHelper;

// (si en este controller realmente los usas, si no, puedes quitarlos)
use App\Models\MovimientoAhorro;
use App\Models\RetiroAhorro;

// ==== CORREO ====
use App\Services\ClienteMailService;
use App\Mail\NuevoAhorroCreadoMail;
use App\Mail\AhorroPendienteClienteMail;
use App\Mail\AhorroPendienteAdminMail;

use Stripe\StripeClient;

// ✅ OPCIÓN B: mensajes persistentes en BD (API)
use App\Models\StripeReturnMessage;

class UserAhorroApiController extends Controller
{
    
    public function __construct(private ClienteMailService $clienteMail) {
        
    }
    
    
    /** Resuelve el cliente autenticado (Sanctum). */
    private function cliente(Request $request): Cliente
    {
        $u = auth('sanctum')->user() ?? $request->user();

        if ($u instanceof Cliente) return $u;

        if ($u && isset($u->id_cliente) && $u->id_cliente) {
            if ($c = Cliente::find($u->id_cliente)) return $c;
        }

        throw new AuthenticationException('El token no corresponde a un cliente.');
    }

    /** Próxima fecha de liquidación desde una frecuencia. */
    private function calcProximaLiquidacion($desde, string $freq): string
    {
        $d = Carbon::parse($desde);
        return match ($freq) {
            'Semanal'   => $d->copy()->addWeek()->toDateString(),
            'Quincenal' => $d->copy()->addDays(15)->toDateString(),
            default     => $d->copy()->addMonthNoOverflow()->toDateString(), // Mensual
        };
    }

    /** Normaliza montos con posibles separadores y símbolos. */
    private function normalizaMonto(mixed $raw): float
    {
        $norm = str_replace([' ', '$'], '', (string) $raw);
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $norm)) {
            $norm = str_replace('.', '', $norm);
            $norm = str_replace(',', '.', $norm);
        } else {
            $norm = str_replace(',', '', $norm);
        }
        return (float) $norm;
    }

    /**
     * ✅ NUEVO:
     * Convierte monto_minimo (mensual) al mínimo requerido según la frecuencia elegida.
     * Regla: Semanal = mensual/4, Quincenal = mensual/2, Mensual = mensual.
     */
    private function minCuotaPorFrecuencia(float $montoMinMensual, string $freq): float
    {
        $montoMinMensual = max(0, (float)$montoMinMensual);

        return match ($freq) {
            'Semanal'   => round($montoMinMensual / 4, 2),
            'Quincenal' => round($montoMinMensual / 2, 2),
            default     => round($montoMinMensual, 2), // Mensual
        };
    }

    /** Obtiene un ahorro del cliente o lanza 404. */
    private function ahorroClienteOrFail(Cliente $cliente, int $id): UserAhorro
    {
        $a = UserAhorro::where('id', $id)->where('id_cliente', $cliente->id)->first();
        abort_unless($a, 404, 'Ahorro no encontrado.');
        return $a;
    }

    /** ✅ Opción B helper: guardar mensaje persistente en BD */
    private function saveReturnMessage(array $data): void
    {
        try {
            StripeReturnMessage::create([
                'tipo'              => $data['tipo'] ?? 'unknown',
                'entity_id'         => $data['entity_id'] ?? null,
                'user_id'           => $data['user_id'] ?? null,
                'session_id'        => $data['session_id'] ?? null,
                'payment_intent_id' => $data['payment_intent_id'] ?? null,
                'status'            => $data['status'] ?? 'warning',
                'message'           => $data['message'] ?? '',
                'seen'              => 0,
            ]);
        } catch (\Throwable $e) {
            Log::warning('StripeReturnMessage create failed', [
                'err'  => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /** GET /api/ahorros */
    public function index(Request $request)
    {
        $cliente = $this->cliente($request);

        $search  = trim((string) $request->input('search', ''));
        $status  = $request->input('status'); // permite 0,1,2
        $desde   = $request->input('desde');
        $hasta   = $request->input('hasta');
        $orden   = $request->input('orden', 'fecha_desc');
        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $query = UserAhorro::query()
            ->with([
                'caja:id_caja,nombre',
                'ahorro' => function ($q) {
                    $q->select('id','nombre','tipo_ahorro','tasa_vigente','monto_minimo','meses_minimos');
                },
            ])
            ->where('id_cliente', $cliente->id)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('id', $search)
                        ->orWhere('monto_ahorro', 'like', "%{$search}%")
                        ->orWhereHas('ahorro', function ($qa) use ($search) {
                            $qa->where('nombre', 'like', "%{$search}%")
                               ->orWhere('tipo_ahorro', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array((string)$status, ['0','1','2'], true), fn($q) => $q->where('status', (int)$status))
            ->when($desde, fn($q) => $q->whereDate('fecha_inicio', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha_inicio', '<=', $hasta));

        $query = match ($orden) {
            'monto_asc'  => $query->orderBy('monto_ahorro', 'asc'),
            'monto_desc' => $query->orderBy('monto_ahorro', 'desc'),
            'fecha_asc'  => $query->orderBy('fecha_inicio', 'asc'),
            default      => $query->orderBy('fecha_inicio', 'desc'),
        };

        $paginator = $query->paginate($perPage)->appends($request->query());

        $label = fn (int $s) => match ($s) {
            0 => 'Pendiente',
            1 => 'Activo',
            2 => 'Inactivo',
            default => 'Desconocido',
        };

        $items = $paginator->getCollection()->map(function (UserAhorro $a) use ($label) {
            $saldoAFecha = $a->saldo_fecha !== null ? (float)$a->saldo_fecha : 0.0;
            $planModel   = $a->ahorro;

            $planLabel = $planModel
                ? AhorroPlanHelper::label($planModel->nombre ?? null, $planModel->tipo_ahorro ?? null)
                : null;

            return [
                'id'                   => $a->id,
                'id_cliente'           => $a->id_cliente,
                'ahorro_id'            => $a->ahorro_id,
                'monto_ahorro'         => (float)($a->monto_ahorro ?? 0),

                'rendimiento'          => $a->rendimiento !== null ? (float)$a->rendimiento : null,
                'rendimiento_generado' => $a->rendimiento_generado !== null ? (float)$a->rendimiento_generado : null,

                'saldo_disponible'     => (float)($a->saldo_disponible ?? 0),
                'saldo_a_fecha'        => $saldoAFecha,

                'interes_acumulado'    => $a->interes_acumulado !== null ? (float)$a->interes_acumulado : null,
                'fecha_ultimo_calculo' => $a->fecha_ultimo_calculo,
                'fecha_fin'            => $a->fecha_fin,

                'status'               => (int)$a->status,
                'status_label'         => $label((int)$a->status),
                'fecha_solicitud'      => $a->fecha_solicitud,
                'fecha_inicio'         => $a->fecha_inicio,
                'frecuencia_pago'      => $a->frecuencia_pago,
                'tiempo'               => $a->tiempo,
                'cuota'                => $a->cuota !== null ? (float)$a->cuota : null,

                'id_caja'              => $a->id_caja,
                'caja'                 => $a->caja ? [
                    'id_caja' => $a->caja->id_caja,
                    'nombre'  => $a->caja->nombre,
                ] : null,

                'plan'                 => $planModel ? [
                    'id'            => $planModel->id,
                    'label'         => $planLabel,
                    'nombre'        => $planModel->nombre ?? null,
                    'tipo_ahorro'   => $planModel->tipo_ahorro,
                    'rendimiento'   => $planModel->tasa_vigente,
                    'monto_min'     => $planModel->monto_minimo ?? null,
                    'meses_minimos' => $planModel->meses_minimos ?? 1,
                    'is_temporada'  => AhorroPlanHelper::isTemporada($planModel->tipo_ahorro),
                    'status'        => 1,
                ] : null,

                'stripe_subscription_id' => $a->stripe_subscription_id ?? null,
            ];
        });

        return response()->json([
            'ok'   => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/ahorros
     *
     * ✅ TEMPORADA:
     * - si plan.tipo_ahorro === "temporada": se requiere fecha_fin (la elige el cliente)
     * - si NO es temporada: si mandan fecha_fin => 422
     */
    public function store(Request $request)
    {
        $cliente = $this->cliente($request);
    
        if (!$request->has('monto_ahorro') && $request->has('monto')) {
            $request->merge(['monto_ahorro' => $request->input('monto')]);
        }
    
        $request->validate([
            'ahorro_id'        => ['required','integer','exists:ahorros,id'],
            'monto_ahorro'     => ['required'],
            'fecha_inicio'     => ['nullable','date'],
            'fecha_fin'        => ['nullable','date'],
            'cuota'            => ['required','numeric','min:0'],
            'frecuencia_pago'  => ['nullable','in:Semanal,Quincenal,Mensual'],
        ]);
    
        $monto = $this->normalizaMonto($request->input('monto_ahorro'));
        if ($monto < 0) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => ['monto_ahorro' => ['El monto no puede ser negativo.']],
            ], 422);
        }
    
        $newCuota = (float) $request->input('cuota', 0);
        if ($newCuota <= 0) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => ['cuota' => ['La cuota debe ser mayor a 0.']],
            ], 422);
        }
    
        $plan = Ahorro::query()
            ->select('id','nombre','tipo_ahorro','tasa_vigente','meses_minimos','monto_minimo')
            ->findOrFail((int) $request->input('ahorro_id'));
    
        $esTemporada = AhorroPlanHelper::isTemporada($plan->tipo_ahorro);
    
        if ($esTemporada) {
            if (!$request->filled('fecha_fin')) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['fecha_fin' => ['Para planes de temporada debes elegir la fecha fin.']],
                ], 422);
            }
        } else {
            if ($request->filled('fecha_fin')) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['fecha_fin' => ['La fecha fin solo aplica para planes de temporada.']],
                ], 422);
            }
        }
    
        $tasa          = (float) ($plan->tasa_vigente ?? 0);
        $mesesMinimos  = (int)   ($plan->meses_minimos ?? 1);
        $tiempoDefault = max($mesesMinimos, 1);
    
        $freqLaboral = DB::table('user_laborales')
            ->where('id_cliente', $cliente->id)
            ->orderByDesc('id')
            ->value('recurrencia_pago');
    
        if (!in_array($freqLaboral, ['Semanal','Quincenal','Mensual'], true)) {
            $freqLaboral = 'Mensual';
        }
    
        $freqReq = $request->input('frecuencia_pago');
        $freq = in_array($freqReq, ['Semanal','Quincenal','Mensual'], true) ? $freqReq : $freqLaboral;
    
        $minPlanMensual = (float) ($plan->monto_minimo ?? 0);
        $minReq = $this->minCuotaPorFrecuencia($minPlanMensual, $freq);
    
        if ($newCuota < $minReq) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'cuota' => [
                        "La cuota debe ser igual o mayor al mínimo para {$freq} ($".number_format($minReq,2).")."
                    ],
                ],
            ], 422);
        }
    
        $existente = UserAhorro::query()
            ->where('id_cliente', $cliente->id)
            ->where('ahorro_id', (int)$plan->id)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();
    
        if ($existente) {
            return response()->json([
                'ok'      => true,
                'action'  => 'update',
                'message' => 'Ya existe un ahorro activo de este tipo. Se reemplazará la suscripción y se cobrará el nuevo monto + cuota.',
                'ahorro'  => [
                    'id'                     => $existente->id,
                    'id_cliente'             => $existente->id_cliente,
                    'ahorro_id'              => $existente->ahorro_id,
                    'stripe_subscription_id' => $existente->stripe_subscription_id ?? null,
                    'monto_actual'           => (float) ($existente->monto_ahorro ?? 0),
                    'cuota_actual'           => (float) ($existente->cuota ?? 0),
                ],
                'requested' => [
                    'new_monto_inicial' => $monto,
                    'new_cuota'         => $newCuota,
                    'fecha_fin'         => $request->input('fecha_fin'),
                    'frecuencia_pago'   => $freq,
                ],
                'plan' => [
                    'id'               => $plan->id,
                    'nombre'           => $plan->nombre,
                    'tipo_ahorro'      => $plan->tipo_ahorro,
                    'monto_minimo'     => $plan->monto_minimo,
                    'minimo_requerido' => $minReq,
                ],
            ], 200);
        }
    
        DB::transaction(function () use ($cliente, $plan) {
            $q = UserAhorro::where('id_cliente', $cliente->id)
                ->where('ahorro_id', (int)$plan->id)
                ->where('status', 0);
    
            if (Schema::hasColumn('user_ahorro', 'stripe_subscription_id')) {
                $q->whereNull('stripe_subscription_id');
            }
    
            $q->delete();
        });
    
        $defaultCajaId = null;
        if (Schema::hasColumn('user_ahorro', 'id_caja')) {
            $defaultCajaId = (int) config('app.caja_ahorros_id', 1);
            if ($defaultCajaId <= 0) {
                $defaultCajaId = Caja::query()
                    ->when(
                        Schema::hasColumn('cajas','estado'),
                        fn($q) => $q->where('estado','abierta')
                    )
                    ->value('id_caja') ?? Caja::query()->value('id_caja');
            }
        }
    
        $rendTopePeriodo = round($monto * ($tasa / 100) * ($tiempoDefault / 12), 2);
    
        $ahorro = DB::transaction(function () use (
            $cliente, $request, $monto, $newCuota, $tasa, $rendTopePeriodo,
            $defaultCajaId, $tiempoDefault, $mesesMinimos, $freq, $esTemporada
        ) {
            $fi = $request->input('fecha_inicio') ?: now();
            $fiCarbon = Carbon::parse($fi);
    
            if ($esTemporada) {
                $ffCarbon = Carbon::parse($request->input('fecha_fin'))->startOfDay();
                if ($ffCarbon->lte($fiCarbon->copy()->startOfDay())) {
                    abort(response()->json([
                        'message' => 'The given data was invalid.',
                        'errors'  => ['fecha_fin' => ['La fecha fin debe ser posterior a la fecha de inicio.']],
                    ], 422));
                }
                $fechaFin = $ffCarbon->toDateString();
            } else {
                $fechaFin = $this->calcProximaLiquidacion($fi, $freq);
            }
    
            $payload = [
                'id_cliente'           => $cliente->id,
                'ahorro_id'            => (int) $request->input('ahorro_id'),
                'fecha_solicitud'      => now(),
                'fecha_inicio'         => $fi,
    
                'monto_ahorro'         => $monto,
                'cuota'                => $newCuota,
    
                'rendimiento'          => $tasa,
                'rendimiento_generado' => $rendTopePeriodo,
    
                'saldo_disponible'     => 0,
                'saldo_fecha'          => 0,
                'interes_acumulado'    => 0,
    
                'status'               => 0,
    
                'id_caja'              => $defaultCajaId,
                'tiempo'               => $tiempoDefault,
                'meses_minimos'        => $mesesMinimos ?: 1,
                'tipo'                 => 1,
                'frecuencia_pago'      => $freq,
    
                'fecha_ultimo_calculo' => $fiCarbon->toDateString(),
                'fecha_fin'            => $fechaFin,
            ];
    
            if (Schema::hasColumn('user_ahorro', 'stripe_status')) {
                $payload['stripe_status'] = 'pending';
            }
    
            $nuevo = UserAhorro::create($payload);
    
            return $nuevo->load([
                'caja:id_caja,nombre',
                'ahorro' => function ($q) {
                    $q->select('id','nombre','tipo_ahorro','tasa_vigente','monto_minimo','meses_minimos');
                },
            ]);
        });
    
        // ✅ DATOS PARA MAILS (con tu servicio)
        $clienteData = $this->clienteMail->mailData($cliente);
        $clienteEmail = trim((string)($clienteData['email'] ?? ''));
        $adminEmail = trim((string)config('mail.from.address')); // MAIL_FROM_ADDRESS
    
        try {
            DB::afterCommit(function () use ($ahorro, $clienteData, $clienteEmail, $adminEmail) {
    
                if ($clienteEmail !== '') {
                    Mail::to($clienteEmail)->send(
                        new \App\Mail\AhorroPendienteClienteMail($ahorro, $clienteData)
                    );
                }
    
                if ($adminEmail !== '') {
                    Mail::to($adminEmail)->send(
                        new \App\Mail\AhorroPendienteAdminMail($ahorro, $clienteData)
                    );
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Ahorro pendiente: afterCommit/mail falló', [
                'ahorro_id'  => $ahorro->id ?? null,
                'cliente_id' => $clienteData['id'] ?? null,
                'err'        => $e->getMessage(),
            ]);
        }

        $clienteNombre = trim(sprintf('%s %s', (string)($cliente->nombre ?? ''), (string)($cliente->apellido ?? '')));
        $titulo = 'Nueva solicitud de ahorro';
        $mensaje = $clienteNombre !== '' ? "Cliente {$clienteNombre} creó una solicitud." : 'Se creó una nueva solicitud.';
        $url = route('user_ahorros.show', $ahorro);

        User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
            $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
        });

        return response()->json([
            'ok'      => true,
            'action'  => 'create',
            'message' => 'Ahorro creado en pendiente. Se activará cuando Stripe confirme el pago.',
            'ahorro'  => [
                'id'                     => $ahorro->id,
                'id_cliente'             => $ahorro->id_cliente,
                'ahorro_id'              => $ahorro->ahorro_id,
                'monto_ahorro'           => (float) $ahorro->monto_ahorro,
                'cuota'                  => $ahorro->cuota !== null ? (float)$ahorro->cuota : null,
                'fecha_fin'              => $ahorro->fecha_fin,
                'frecuencia_pago'        => $ahorro->frecuencia_pago,
                'status'                 => (int) $ahorro->status,
                'stripe_subscription_id' => $ahorro->stripe_subscription_id ?? null,
            ],
        ], 201);
    }



    /** GET /api/ahorros/planes */
    public function planes(Request $request)
    {
        $planes = Ahorro::query()
            ->orderBy('nombre')
            ->get(['id','nombre','tipo_ahorro','tasa_vigente','monto_minimo','meses_minimos'])
            ->map(function (Ahorro $p) {
                return [
                    'id'            => $p->id,
                    'label'         => AhorroPlanHelper::label($p->nombre, $p->tipo_ahorro),
                    'nombre'        => $p->nombre,
                    'tipo_ahorro'   => $p->tipo_ahorro,
                    'is_temporada'  => AhorroPlanHelper::isTemporada($p->tipo_ahorro),
                    'rendimiento'   => $p->tasa_vigente,
                    'monto_min'     => $p->monto_minimo, // mensual (front lo ajusta)
                    'meses_minimos' => $p->meses_minimos,
                    'status'        => 1,
                ];
            });

        return response()->json(['ok' => true, 'data' => $planes]);
    }

    /** GET /api/ahorros/frecuencia */
    public function frecuencia(Request $request)
    {
        $cliente = $this->cliente($request);

        $row = DB::table('user_laborales')
            ->where('id_cliente', $cliente->id)
            ->orderByDesc('id')
            ->first();

        Log::info('Ahorros.frecuencia', [
            'cliente_id' => $cliente->id,
            'hallado'    => (bool) $row,
            'recurrencia'=> $row->recurrencia_pago ?? null,
        ]);

        $freq = $row->recurrencia_pago ?? null;
        if (!in_array($freq, ['Semanal','Quincenal','Mensual'], true)) {
            $freq = 'Mensual';
        }

        return response()->json(['ok' => true, 'frecuencia' => $freq]);
    }

    // =========================
    // ===== Acciones NIP ======
    // =========================

    public function retirar(Request $request, int $id)
    {
        $cliente = $this->cliente($request);

        $request->validate([
            'monto' => ['required'],
            'nip'   => ['required','string'],
            'nota'  => ['nullable','string','max:255'],
        ]);

        if (!NipHelper::verify($cliente, (string) $request->input('nip'))) {
            return response()->json(['ok'=>false,'error'=>'NIP inválido.'], 422);
        }

        $monto  = $this->normalizaMonto($request->input('monto'));
        $ahorro = $this->ahorroClienteOrFail($cliente, $id);

        try {
            AhorroGuard::ensurePuedeDebitar($ahorro, $monto);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok'=>false,'error'=>$e->getMessage()], 422);
        }

        $nota = trim((string)($request->input('nota') ?? $request->input('obs') ?? 'Retiro del ahorro'));

        $result = DB::transaction(function () use ($ahorro, $monto, $nota) {
            $ahorro->saldo_disponible = (float)$ahorro->saldo_disponible - $monto;
            $ahorro->save();

            MovimientosHelper::registrar($ahorro, 'RETIRO', -$monto, (float)$ahorro->saldo_disponible, $nota, null);

            RetiroAhorro::create([
                'tipo'               => 1,
                'fecha_aprobacion'   => now(),
                'id_cliente'         => $ahorro->id_cliente,
                'fecha_transferencia'=> null,
                'created_at'         => now(),
                'fecha_solicitud'    => now(),
                'cantidad'           => $monto,
                'id_ahorro'          => $ahorro->id,
                'status'             => 1,
            ]);

            return ['nuevo_saldo_disponible' => (float)$ahorro->saldo_disponible];
        });

        return response()->json(['ok'=>true,'message'=>'Retiro realizado.'] + $result);
    }

    public function transferir(Request $request, int $id)
    {
        $cliente = $this->cliente($request);

        $request->validate([
            'monto'       => ['required'],
            'nip'         => ['required','string'],
            'destino_id'  => ['required','integer'],
            'nota'        => ['nullable','string','max:255'],
        ]);

        if (!NipHelper::verify($cliente, (string)$request->input('nip'))) {
            return response()->json(['ok'=>false,'error'=>'NIP inválido.'], 422);
        }

        $monto   = $this->normalizaMonto($request->input('monto'));
        $origen  = $this->ahorroClienteOrFail($cliente, $id);
        $destino = $this->ahorroClienteOrFail($cliente, (int)$request->input('destino_id'));

        try {
            AhorroGuard::assertActivo($origen);
            AhorroGuard::assertActivo($destino);
            AhorroGuard::ensurePuedeDebitar($origen, $monto);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok'=>false,'error'=>$e->getMessage()], 422);
        }

        if ($origen->id === $destino->id) {
            return response()->json(['ok'=>false,'error'=>'El ahorro destino debe ser distinto al origen.'], 422);
        }

        $nota = trim((string)($request->input('nota') ?? $request->input('obs') ?? "Transferencia de ahorro #{$origen->id} a #{$destino->id}"));

        $result = DB::transaction(function () use ($origen,$destino,$monto,$nota) {
            $origen->saldo_disponible = (float)$origen->saldo_disponible - $monto;
            $origen->save();
            MovimientosHelper::registrar($origen, 'TRANSFER', -$monto, (float)$origen->saldo_disponible, $nota);

            $destino->saldo_disponible = (float)$destino->saldo_disponible + $monto;
            $destino->save();
            MovimientosHelper::registrar($destino, 'TRANSFER_IN', $monto, (float)$destino->saldo_disponible, $nota);

            return [
                'origen_saldo'  => (float)$origen->saldo_disponible,
                'destino_saldo' => (float)$destino->saldo_disponible,
            ];
        });

        return response()->json(['ok'=>true,'message'=>'Transferencia realizada.'] + $result);
    }

    public function abonarPrestamo(Request $request, int $id)
    {
        $cliente = $this->cliente($request);

        $request->validate([
            'monto'       => ['required'],
            'nip'         => ['required','string'],
            'prestamo_id' => ['required','integer'],
            'nota'        => ['nullable','string','max:255'],
        ]);

        if (!NipHelper::verify($cliente, (string)$request->input('nip'))) {
            return response()->json(['ok'=>false,'error'=>'NIP inválido.'], 422);
        }

        $monto  = $this->normalizaMonto($request->input('monto'));
        $ahorro = $this->ahorroClienteOrFail($cliente, $id);

        try {
            AhorroGuard::ensurePuedeDebitar($ahorro, $monto);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok'=>false,'error'=>$e->getMessage()], 422);
        }

        $prestamoId = (int) $request->input('prestamo_id');
        $nota = trim((string)($request->input('nota') ?? $request->input('obs') ?? "Abono a préstamo #{$prestamoId}"));

        $result = DB::transaction(function () use ($ahorro, $monto, $nota) {
            $ahorro->saldo_disponible = (float)$ahorro->saldo_disponible - $monto;
            $ahorro->save();

            MovimientosHelper::registrar($ahorro, 'ABONO_PRESTAMO', -$monto, (float)$ahorro->saldo_disponible, $nota);

            return ['nuevo_saldo_disponible' => (float)$ahorro->saldo_disponible];
        });

        return response()->json(['ok'=>true,'message'=>'Abono aplicado.'] + $result);
    }

    // =========================
    // ===== Stripe helpers =====
    // =========================

    private function stripe(): StripeClient
    {
        $secretKey = config('services.stripe.secret');
        if (!$secretKey) throw new \RuntimeException('Stripe secret no configurado.');
        return new StripeClient($secretKey);
    }

    private function intervalFromFreq(?string $freq): array
    {
        $freq = $freq ?: 'Mensual';
        return match ($freq) {
            'Semanal'   => ['week', 1],
            'Quincenal' => ['week', 2],
            default     => ['month', 1],
        };
    }

    private function ahorrosProductId(): string
    {
        $pid = (string) (config('services.stripe.ahorros_product_id') ?? env('STRIPE_AHORROS_PRODUCT_ID', ''));
        $pid = trim($pid);
        if ($pid === '') throw new \RuntimeException('Falta STRIPE_AHORROS_PRODUCT_ID (producto activo).');
        return $pid;
    }

    private function getOrCreateRecurringPrice(
        StripeClient $stripe,
        string $productId,
        int $unitAmountCents,
        string $interval,
        int $intervalCount
    ): string {
        $lookupKey = "ahorro_{$interval}_{$intervalCount}_{$unitAmountCents}";

        $existing = $stripe->prices->all([
            'lookup_keys' => [$lookupKey],
            'active'      => true,
            'limit'       => 1,
        ]);

        if (!empty($existing->data)) {
            return $existing->data[0]->id;
        }

        $price = $stripe->prices->create([
            'currency'    => 'mxn',
            'unit_amount' => $unitAmountCents,
            'recurring'   => [
                'interval'       => $interval,
                'interval_count' => $intervalCount,
            ],
            'product'    => $productId,
            'lookup_key' => $lookupKey,
            'metadata'   => ['type' => 'ahorro_recurring'],
        ]);

        return $price->id;
    }

    private function updateStripeSubscriptionAmount(string $subscriptionId, float $newCuota, string $freq): void
    {
        [$interval, $intervalCount] = $this->intervalFromFreq($freq);

        $stripe = $this->stripe();

        $sub = $stripe->subscriptions->retrieve($subscriptionId, [
            'expand' => ['items.data.price'],
        ]);

        $item = $sub->items->data[0] ?? null;
        if (!$item) throw new \RuntimeException('No se encontró subscription item.');

        $productId = $this->ahorrosProductId();

        $priceId = $this->getOrCreateRecurringPrice(
            $stripe,
            $productId,
            (int) round($newCuota * 100),
            $interval,
            $intervalCount
        );

        $stripe->subscriptions->update($subscriptionId, [
            'items' => [[
                'id'    => $item->id,
                'price' => $priceId,
            ]],
            'proration_behavior' => 'none', // ✅ NO cobro hoy, aplica próximo ciclo
        ]);
    }

    /**
     * ✅ Si el ahorro está PENDIENTE (0) y ya existe un checkout_session_id,
     * expiramos el checkout anterior para que NO se pueda pagar con cuota vieja.
     */
    private function expireOldCheckoutSessionIfAny(UserAhorro $ahorro): bool
    {
        $table = $ahorro->getTable();

        if (!Schema::hasColumn($table, 'stripe_checkout_session_id')) return false;

        $sessionId = trim((string)($ahorro->stripe_checkout_session_id ?? ''));
        if ($sessionId === '') return false;

        // Solo tiene sentido si aún NO hay suscripción
        $subId = trim((string)($ahorro->stripe_subscription_id ?? ''));
        if ($subId !== '') return false;

        // Expirar en Stripe
        $this->stripe()->checkout->sessions->expire($sessionId, []);

        // Limpiar en BD para obligar a crear otro checkout
        DB::transaction(function () use ($ahorro, $table) {
            $row = UserAhorro::where('id', $ahorro->id)->lockForUpdate()->first();
            if (!$row) return;

            if (Schema::hasColumn($table, 'stripe_checkout_session_id')) {
                $row->stripe_checkout_session_id = null;
            }

            // opcional: mantener flags en "pending"
            if (Schema::hasColumn($table, 'payment_status') && empty($row->payment_status)) {
                $row->payment_status = 'pending';
            }
            if (Schema::hasColumn($table, 'stripe_status') && empty($row->stripe_status)) {
                $row->stripe_status = 'pending';
            }

            $row->save();
        });

        return true;
    }

    // =========================
    // ===== Editar cuota ======
    // =========================

    public function cambiarCuota(Request $request, int $id)
    {
        $cliente = $this->cliente($request);

        $request->validate([
            'cuota' => ['required','numeric','min:0'],
            'nip'   => ['required','string'],
            'nota'  => ['nullable','string','max:255'],
        ]);

        if (!NipHelper::verify($cliente, (string)$request->input('nip'))) {
            return response()->json(['ok'=>false,'error'=>'NIP inválido.'], 422);
        }

        $ahorro = $this->ahorroClienteOrFail($cliente, $id);

        // ✅ Permitir cambiar cuota en Pendiente(0) o Activo(1)
        if (!in_array((int)$ahorro->status, [0,1], true)) {
            return response()->json([
                'ok'    => false,
                'error' => 'Solo puedes cambiar la cuota cuando el ahorro esté Pendiente o Activo.',
            ], 422);
        }

        $nuevaCuota = (float) $request->input('cuota');
        $anterior   = (float) ($ahorro->cuota ?? 0.0);

        // ✅ Validar mínimo por frecuencia (monto_minimo mensual del plan)
        $plan = Ahorro::query()->select('id','monto_minimo')->find($ahorro->ahorro_id);
        $minMensual = (float)($plan->monto_minimo ?? 0);

        $freq = $ahorro->frecuencia_pago ?: 'Mensual';
        if (!in_array($freq, ['Semanal','Quincenal','Mensual'], true)) $freq = 'Mensual';

        $minReq = $this->minCuotaPorFrecuencia($minMensual, $freq);

        if ($nuevaCuota < $minReq) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'cuota' => [
                        "La cuota debe ser igual o mayor al mínimo para {$freq} ($".number_format($minReq,2).")."
                    ],
                ],
            ], 422);
        }

        $nota = trim((string)($request->input('nota') ?? $request->input('obs') ?? "Cambio de cuota {$anterior} → {$nuevaCuota}"));

        $requiresNewCheckout = false;

        // ✅ Si está PENDIENTE (0) y ya existe un checkout viejo, lo expiramos
        if ((int)$ahorro->status === 0) {
            try {
                $requiresNewCheckout = $this->expireOldCheckoutSessionIfAny($ahorro);
            } catch (\Throwable $e) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'No se pudo invalidar el checkout anterior (para evitar pagar con cuota vieja). Intenta de nuevo. '.$e->getMessage(),
                ], 422);
            }
        }

        // ✅ Stripe: si hay suscripción, actualizarla (sin cobro hoy / sin prorrateos)
        $subId = trim((string)($ahorro->stripe_subscription_id ?? ''));
        if ($subId !== '') {
            try {
                $this->updateStripeSubscriptionAmount($subId, $nuevaCuota, $freq);
            } catch (\Throwable $e) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'No se pudo actualizar la suscripción en Stripe: '.$e->getMessage(),
                ], 422);
            }
        }

        // ✅ Persistencia con lock para evitar carreras
        DB::transaction(function () use ($ahorro, $nuevaCuota, $nota) {
            $row = UserAhorro::where('id', $ahorro->id)->lockForUpdate()->first();
            if (!$row) return;

            $row->cuota = $nuevaCuota;
            $row->save();

            MovimientosHelper::registrar($row, 'CAMBIO_CUOTA', 0.0, (float)$row->saldo_disponible, $nota);
        });

        return response()->json([
            'ok' => true,
            'message' => $subId !== ''
                ? 'Cuota actualizada y suscripción Stripe actualizada (aplica en el próximo cobro).'
                : 'Cuota actualizada.',
            'cuota' => $nuevaCuota,
            'stripe_subscription_id' => $subId !== '' ? $subId : null,
            'requires_new_checkout'  => $requiresNewCheckout, // ✅ útil para el front si estaba pendiente
        ]);
    }

    public function cancelarSuscripcionStripe(Request $request, int $id)
    {
        $cliente = $this->cliente($request);
    
        $request->validate([
            'nip'  => ['required','string'],
            'nota' => ['nullable','string','max:255'],
        ]);
    
        if (!NipHelper::verify($cliente, (string)$request->input('nip'))) {
            $this->saveReturnMessage([
                'tipo'      => 'ahorro_cancel',
                'entity_id' => $id,
                'user_id'   => $cliente->id,
                'status'    => 'danger',
                'message'   => 'NIP inválido al intentar cancelar la suscripción.',
            ]);
    
            return response()->json(['ok'=>false,'error'=>'NIP inválido.'], 422);
        }
    
        $ahorro = $this->ahorroClienteOrFail($cliente, $id);
    
        // ✅ Regla: solo cancelar cuando está Inactivo (status=2)
        if ((int)$ahorro->status !== 2) {
            $this->saveReturnMessage([
                'tipo'      => 'ahorro_cancel',
                'entity_id' => $ahorro->id,
                'user_id'   => $cliente->id,
                'status'    => 'warning',
                'message'   => 'Intento de cancelación rechazado: el ahorro no está Inactivo (status=2).',
            ]);
    
            return response()->json([
                'ok'    => false,
                'error' => 'Solo puedes cancelar cuando el ahorro esté Inactivo (status=2).',
            ], 422);
        }
    
        // ✅ NUEVO: si ya fue cancelada antes (por timestamp o por stripe_status)
        if (Schema::hasColumn('user_ahorro', 'stripe_canceled_at') && !empty($ahorro->stripe_canceled_at)) {
            $this->saveReturnMessage([
                'tipo'      => 'ahorro_cancel',
                'entity_id' => $ahorro->id,
                'user_id'   => $cliente->id,
                'status'    => 'warning',
                'message'   => 'Este ahorro ya tenía stripe_canceled_at, por lo tanto ya estaba cancelado.',
            ]);
    
            return response()->json([
                'ok'    => false,
                'error' => 'Esta suscripción ya fue cancelada anteriormente.',
            ], 422);
        }
    
        if (Schema::hasColumn('user_ahorro', 'stripe_status') && ((string)$ahorro->stripe_status === 'canceled')) {
            $this->saveReturnMessage([
                'tipo'      => 'ahorro_cancel',
                'entity_id' => $ahorro->id,
                'user_id'   => $cliente->id,
                'status'    => 'warning',
                'message'   => 'Este ahorro ya estaba terminado y cancelado.',
            ]);
    
            return response()->json([
                'ok'    => false,
                'error' => 'Esta suscripción ya fue cancelada anteriormente.',
            ], 422);
        }
    
        $subId = trim((string)($ahorro->stripe_subscription_id ?? ''));
        if ($subId === '') {
            $this->saveReturnMessage([
                'tipo'      => 'ahorro_cancel',
                'entity_id' => $ahorro->id,
                'user_id'   => $cliente->id,
                'status'    => 'warning',
                'message'   => 'Este ahorro no tiene suscripción de Stripe asociada.',
            ]);
    
            return response()->json([
                'ok'    => false,
                'error' => 'Este ahorro no tiene suscripción de Stripe asociada.',
            ], 422);
        }
    
        $nota = trim((string)($request->input('nota') ?? 'Cancelación de suscripción Stripe'));
    
        // 1) Cancelar en Stripe
        $stripeCanceled = false;
        try {
            $this->stripe()->subscriptions->cancel($subId, []);
            $stripeCanceled = true;
        } catch (\Throwable $e) {
            // ✅ Si Stripe dice "ya cancelada", lo tratamos como éxito y solo sincronizamos BD
            $msg = (string)$e->getMessage();
            $alreadyCanceled =
                str_contains(mb_strtolower($msg), 'already') && str_contains(mb_strtolower($msg), 'canceled');
    
            if (!$alreadyCanceled) {
                Log::warning('Stripe cancel subscription failed', [
                    'cliente_id' => $cliente->id,
                    'ahorro_id'  => $ahorro->id,
                    'sub_id'     => $subId,
                    'err'        => $msg,
                ]);
    
                $this->saveReturnMessage([
                    'tipo'      => 'ahorro_cancel',
                    'entity_id' => $ahorro->id,
                    'user_id'   => $cliente->id,
                    'status'    => 'danger',
                    'message'   => 'No se pudo cancelar la suscripción en Stripe: '.$msg,
                ]);
    
                return response()->json([
                    'ok'    => false,
                    'error' => 'No se pudo cancelar en Stripe: '.$msg,
                ], 422);
            }
            // si ya estaba cancelada en Stripe, seguimos para marcar BD
        }
    
        // 2) Persistir en BD (con lock) + marcar fecha de cancelación
        DB::transaction(function () use ($ahorro, $nota) {
            $row = UserAhorro::where('id', $ahorro->id)->lockForUpdate()->first();
            if (!$row) return;
    
            if (Schema::hasColumn('user_ahorro', 'stripe_status')) {
                $row->stripe_status = 'canceled';
            }
    
            if (Schema::hasColumn('user_ahorro', 'stripe_canceled_at')) {
                $row->stripe_canceled_at = now();
            }
    
            try {
                MovimientosHelper::registrar(
                    $row,
                    'CANCEL_STRIPE',
                    0.0,
                    (float)($row->saldo_disponible ?? 0),
                    $nota
                );
            } catch (\Throwable $e) {
                Log::info('MovimientosHelper registrar CANCEL_STRIPE failed', [
                    'ahorro_id' => $row->id,
                    'err'       => $e->getMessage(),
                ]);
            }
    
            $row->save();
        });
    
        // 3) Mensaje persistente
        $this->saveReturnMessage([
            'tipo'      => 'ahorro_cancel',
            'entity_id' => $ahorro->id,
            'user_id'   => $cliente->id,
            'status'    => 'success',
            'message'   => $stripeCanceled
                ? 'Suscripción cancelada en Stripe.'
                : 'Stripe ya marcaba esta suscripción como cancelada; se sincronizó la BD.',
        ]);
    
        return response()->json([
            'ok'                     => true,
            'message'                => $stripeCanceled
                ? 'Suscripción cancelada en Stripe.'
                : 'Stripe ya marcaba esta suscripción como cancelada; se sincronizó la BD.',
            'stripe_subscription_id' => $subId,
            'stripe_canceled_at'     => Schema::hasColumn('user_ahorro','stripe_canceled_at') ? now()->toDateTimeString() : null,
        ]);
    }
}
