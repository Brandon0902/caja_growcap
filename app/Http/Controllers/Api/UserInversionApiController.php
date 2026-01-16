<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\Inversion;
use App\Models\MovimientoCaja;
use App\Models\User;
use App\Models\UserInversion;
use App\Notifications\NuevaSolicitudNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\NuevaInversionSolicitudMail;

class UserInversionApiController extends Controller
{
    /** Calcula capital/rendimiento “al día” para vista, sin persistir. */
    private function viewAccrual(UserInversion $u): array
    {
        if ((int)$u->status !== 2 || empty($u->fecha_ultimo_calculo) || $u->capital_actual === null) {
            $cap = $u->capital_actual !== null ? (float)$u->capital_actual : null;
            $ia  = $u->interes_acumulado !== null ? (float)$u->interes_acumulado : null;
            return [
                'capital_actual_hoy'       => $cap,
                'rendimiento_generado_hoy' => $ia,
                'interes_acumulado_hoy'    => $ia,
            ];
        }

        $desde = Carbon::parse($u->fecha_ultimo_calculo)->startOfDay();
        $hoy   = now()->startOfDay();

        $dias = max(0, $desde->diffInDays($hoy));
        if ($dias === 0) {
            $cap = (float)$u->capital_actual;
            $ia  = (float)($u->interes_acumulado ?? 0);
            return [
                'capital_actual_hoy'       => $cap,
                'rendimiento_generado_hoy' => $ia,
                'interes_acumulado_hoy'    => $ia,
            ];
        }

        $tasaAnual = (float)($u->rendimiento ?? 0) / 100.0;
        $r_dia     = $tasaAnual / 365.0;

        $capitalBase = (float)$u->capital_actual;
        $capitalHoy  = $capitalBase * pow(1 + $r_dia, $dias);
        $ganadoDelta = $capitalHoy - $capitalBase;

        $iaBase = (float)($u->interes_acumulado ?? 0);
        $iaHoy  = $iaBase + $ganadoDelta;

        return [
            'capital_actual_hoy'       => $capitalHoy,
            'rendimiento_generado_hoy' => $iaHoy,
            'interes_acumulado_hoy'    => $iaHoy,
        ];
    }

    /**
     * Label del plan:
     * - Si existe nombre y viene con valor -> usa ese.
     * - Si no, cae a "Inversion a X meses" basado en periodo.
     */
    private function planLabel($plan): string
    {
        if (!$plan) return 'Inversion';

        $nombre = trim((string)($plan->nombre ?? ''));
        if ($nombre !== '') return $nombre;

        $periodo = trim((string)($plan->periodo ?? ''));
        if ($periodo === '') return 'Inversion';

        $meses = null;
        if (is_numeric($periodo)) {
            $meses = (int)$periodo;
        } elseif (preg_match('/\d+/', $periodo, $m)) {
            $meses = (int)$m[0];
        }

        return $meses ? "Inversion a {$meses} meses" : 'Inversion';
    }

    /** GET /api/inversiones — 1=Pendiente, 2=Activa, 3=Terminada. */
    public function index(Request $request)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        $search   = trim((string) $request->input('search', ''));
        $status   = $request->input('status');
        $desde    = $request->input('desde');
        $hasta    = $request->input('hasta');
        $finDesde = $request->input('fin_desde');
        $finHasta = $request->input('fin_hasta');
        $orden    = $request->input('orden', 'fecha_inicio_desc');

        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $planHasNombre = Schema::hasColumn('inversiones', 'nombre');

        $planCols = ['id', 'periodo', 'rendimiento'];
        if ($planHasNombre) $planCols[] = 'nombre';

        $query = UserInversion::query()
            ->select('user_inversiones.*')
            ->with([
                'plan' => fn($q) => $q->select($planCols),
                'caja:id_caja,nombre',
            ])
            ->where('user_inversiones.id_cliente', $cliente->id)
            ->when($search !== '', function ($q) use ($search, $planHasNombre) {
                $q->where(function ($qq) use ($search, $planHasNombre) {
                    $qq->where('user_inversiones.id', $search)
                        ->orWhere('user_inversiones.inversion', 'like', "%{$search}%")
                        ->orWhereHas('plan', function ($qp) use ($search, $planHasNombre) {
                            $qp->where('periodo', 'like', "%{$search}%");

                            if ($planHasNombre) {
                                $qp->orWhere('nombre', 'like', "%{$search}%");
                            }
                        });
                });
            })
            ->when(in_array($status, ['1', '2', '3'], true), fn($q) => $q->where('user_inversiones.status', (int)$status))
            ->when($desde, fn($q) => $q->whereDate('user_inversiones.fecha_inicio', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('user_inversiones.fecha_inicio', '<=', $hasta))
            ->when($finDesde, fn($q) => $q->whereDate('user_inversiones.fecha_fin', '>=', $finDesde))
            ->when($finHasta, fn($q) => $q->whereDate('user_inversiones.fecha_fin', '<=', $finHasta));

        $query = match ($orden) {
            'monto_asc'         => $query->orderBy('user_inversiones.inversion', 'asc'),
            'monto_desc'        => $query->orderBy('user_inversiones.inversion', 'desc'),
            'fecha_inicio_asc'  => $query->orderBy('user_inversiones.fecha_inicio', 'asc'),
            'fecha_inicio_desc' => $query->orderBy('user_inversiones.fecha_inicio', 'desc'),
            'fecha_fin_asc'     => $query->orderBy('user_inversiones.fecha_fin', 'asc'),
            'fecha_fin_desc'    => $query->orderBy('user_inversiones.fecha_fin', 'desc'),
            default             => $query->orderBy('user_inversiones.fecha_inicio', 'desc'),
        };

        $paginator = $query->paginate($perPage)->appends($request->query());

        $label = fn(int $s) => match ($s) {
            1 => 'Pendiente',
            2 => 'Activa',
            3 => 'Terminada',
            default => 'Desconocido',
        };

        $fmtDate = function ($v) {
            if (!$v) return null;
            if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d');
            try { return Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable $e) { return (string)$v; }
        };

        $fmtDateTime = function ($v) {
            if (!$v) return null;
            if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d H:i:s');
            try { return Carbon::parse($v)->format('Y-m-d H:i:s'); } catch (\Throwable $e) { return (string)$v; }
        };

        $hasPaymentMethod   = Schema::hasColumn('user_inversiones', 'payment_method');
        $hasPaymentStatus   = Schema::hasColumn('user_inversiones', 'payment_status');
        $hasStripeSessionId = Schema::hasColumn('user_inversiones', 'stripe_session_id');
        $hasStripePiId      = Schema::hasColumn('user_inversiones', 'stripe_payment_intent_id');
        $hasStripePaidAt    = Schema::hasColumn('user_inversiones', 'stripe_paid_at');

        $items = $paginator->getCollection()->map(function (UserInversion $u) use (
            $label, $fmtDate, $fmtDateTime,
            $hasPaymentMethod, $hasPaymentStatus, $hasStripeSessionId, $hasStripePiId, $hasStripePaidAt
        ) {
            $view = $this->viewAccrual($u);

            $capitalActualBD = $u->capital_actual !== null ? (float)$u->capital_actual : null;
            $interesBD       = $u->interes_acumulado !== null ? (float)$u->interes_acumulado : null;

            return [
                'id'                       => $u->id,
                'id_cliente'               => $u->id_cliente,
                'id_activo'                => $u->id_activo,
                'cantidad'                 => (float)$u->inversion,
                'rendimiento'              => $u->rendimiento !== null ? (float)$u->rendimiento : null,

                'capital_actual'           => $capitalActualBD,
                'rendimiento_generado'     => $interesBD,
                'interes_acumulado'        => $interesBD,

                'capital_actual_hoy'       => $view['capital_actual_hoy'],
                'rendimiento_generado_hoy' => $view['rendimiento_generado_hoy'],
                'interes_acumulado_hoy'    => $view['interes_acumulado_hoy'],

                'tiempo'                   => $u->tiempo !== null ? (int)$u->tiempo : null,
                'status'                   => (int)$u->status,
                'status_label'             => $label((int)$u->status),

                'fecha_solicitud'          => $fmtDate($u->fecha_solicitud),
                'fecha_inicio'             => $fmtDate($u->fecha_inicio),
                'fecha_fin'                => $fmtDate($u->getRawOriginal('fecha_fin')),
                'fecha_ultimo_calculo'     => $fmtDate($u->fecha_ultimo_calculo),
                'ultimo_calculo'           => $fmtDate($u->fecha_ultimo_calculo),

                'id_caja'                  => $u->id_caja,
                'caja'                     => $u->caja ? [
                    'id_caja' => $u->caja->id_caja,
                    'nombre'  => $u->caja->nombre,
                ] : null,

                'plan'                     => $u->plan ? [
                    'id'          => $u->plan->id,
                    'label'       => $this->planLabel($u->plan),
                    'periodo'     => $u->plan->periodo,
                    'rendimiento' => $u->plan->rendimiento,
                    'nombre'      => $u->plan->nombre ?? null,
                ] : null,

                'payment_method'           => $hasPaymentMethod ? ($u->payment_method ?? null) : null,
                'payment_status'           => $hasPaymentStatus ? ($u->payment_status ?? null) : null,
                'stripe_status'            => $hasPaymentStatus ? ($u->payment_status ?? null) : null,

                'stripe_session_id'        => $hasStripeSessionId ? ($u->stripe_session_id ?? null) : null,
                'stripe_payment_intent_id' => $hasStripePiId ? ($u->stripe_payment_intent_id ?? null) : null,
                'stripe_paid_at'           => $hasStripePaidAt ? $fmtDateTime($u->stripe_paid_at) : null,

                'created_at'               => $fmtDate($u->created_at),
                'updated_at'               => $fmtDate($u->updated_at),
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
     * POST /api/inversiones — crea solicitud PENDIENTE (status=1).
     * - Si viene pay_method=stripe|saldo: aqui NO mandamos correo al admin.
     *   (Stripe lo manda el webhook cuando payment_status=paid; saldo lo activa pay-saldo)
     */
    public function store(Request $request)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        if (!$request->has('cantidad')) {
            if ($request->has('monto')) $request->merge(['cantidad' => $request->input('monto')]);
            if ($request->has('cantidad_inversion')) $request->merge(['cantidad' => $request->input('cantidad_inversion')]);
        }

        $request->validate([
            'id_activo'  => ['required', 'exists:inversiones,id'],
            'cantidad'   => ['required'],
            'tiempo'     => ['nullable', 'integer', 'min:1', 'max:360'],
            'pay_method' => ['nullable', 'in:stripe,saldo'],
        ]);

        $payMethod = $request->input('pay_method'); // stripe | saldo | null

        $raw  = trim((string)$request->input('cantidad'));
        $norm = str_replace([' ', '$'], '', $raw);
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $norm)) {
            $norm = str_replace('.', '', $norm);
            $norm = str_replace(',', '.', $norm);
        } else {
            $norm = str_replace(',', '', $norm);
        }

        $monto = (float)$norm;
        if ($monto <= 0) {
            return response()->json(['ok' => false, 'error' => 'La cantidad debe ser mayor a 0.'], 422);
        }

        $plan = Inversion::findOrFail($request->input('id_activo'));
        $tasa = (float)($plan->rendimiento ?? 0);

        $defaultCajaId = null;
        if (Schema::hasColumn('user_inversiones', 'id_caja')) {
            $defaultCajaId = (int)config('app.caja_pendientes_id', 1);
            if ($defaultCajaId <= 0) {
                $defaultCajaId = Caja::query()
                    ->when(Schema::hasColumn('cajas', 'estado'), fn($q) => $q->where('estado', 'abierta'))
                    ->value('id_caja') ?? Caja::query()->value('id_caja');
            }
            if (!$defaultCajaId) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No hay caja por defecto para registrar la inversion. Configure app.caja_pendientes_id o cree una caja.',
                ], 422);
            }
        }

        $planHasNombre = Schema::hasColumn('inversiones', 'nombre');
        $planCols = ['id', 'periodo', 'rendimiento'];
        if ($planHasNombre) $planCols[] = 'nombre';

        $inv = DB::transaction(function () use ($cliente, $request, $monto, $tasa, $defaultCajaId, $payMethod, $planCols) {
            $payload = [
                'id_cliente'           => $cliente->id,
                'id_activo'            => (int)$request->input('id_activo'),
                'fecha_solicitud'      => now(),
                'fecha_inicio'         => null,
                'fecha_fin'            => null,
                'inversion'            => $monto,
                'rendimiento'          => $tasa,
                'rendimiento_generado' => 0,
                'interes_acumulado'    => 0,
                'capital_actual'       => null,
                'fecha_ultimo_calculo' => null,
                'tiempo'               => $request->input('tiempo') ? (int)$request->input('tiempo') : null,
                'status'               => 1,
                'id_caja'              => $defaultCajaId,
            ];

            if ($payMethod && Schema::hasColumn('user_inversiones', 'payment_method')) {
                $payload['payment_method'] = $payMethod;
            }
            if ($payMethod && Schema::hasColumn('user_inversiones', 'payment_status')) {
                $payload['payment_status'] = 'pending';
            }

            $nuevo = UserInversion::create($payload);

            return $nuevo->load([
                'plan' => fn($q) => $q->select($planCols),
                'caja:id_caja,nombre',
            ]);
        });

        if (!$payMethod) {
            try {
                $adminEmail = trim((string) config('services.admin.email'));
                if ($adminEmail !== '') {
                    Mail::to($adminEmail)->send(new NuevaInversionSolicitudMail($inv, $cliente));
                }
            } catch (\Throwable $e) {
                Log::error('Error enviando correo de nueva inversion', [
                    'inversion_id' => $inv->id ?? null,
                    'cliente_id'   => $cliente->id ?? null,
                    'ex'           => $e->getMessage(),
                ]);
            }
        }

        $clienteNombre = trim(sprintf('%s %s', (string)($cliente->nombre ?? ''), (string)($cliente->apellido ?? '')));
        $titulo = 'Nueva solicitud de inversión';
        $mensaje = $clienteNombre !== '' ? "Cliente {$clienteNombre} creó una solicitud." : 'Se creó una nueva solicitud.';
        $url = route('user_inversiones.show', $inv);

        User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
            $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
        });

        $fmtDate = function ($v) {
            if (!$v) return null;
            if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d');
            try { return Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable $e) { return (string)$v; }
        };

        $fmtDateTime = function ($v) {
            if (!$v) return null;
            if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d H:i:s');
            try { return Carbon::parse($v)->format('Y-m-d H:i:s'); } catch (\Throwable $e) { return (string)$v; }
        };

        return response()->json([
            'ok'      => true,
            'message' => 'Solicitud de inversion creada en estado pendiente.',
            'inversion' => [
                'id'                   => $inv->id,
                'id_cliente'           => $inv->id_cliente,
                'id_activo'            => $inv->id_activo,
                'cantidad'             => (float)$inv->inversion,
                'rendimiento'          => (float)$inv->rendimiento,
                'rendimiento_generado' => (float)($inv->interes_acumulado ?? 0),
                'interes_acumulado'    => (float)($inv->interes_acumulado ?? 0),
                'capital_actual'       => $inv->capital_actual !== null ? (float)$inv->capital_actual : null,
                'fecha_ultimo_calculo' => $fmtDate($inv->fecha_ultimo_calculo),
                'ultimo_calculo'       => $fmtDate($inv->fecha_ultimo_calculo),
                'tiempo'               => $inv->tiempo !== null ? (int)$inv->tiempo : null,
                'status'               => (int)$inv->status,
                'fecha_solicitud'      => $fmtDate($inv->fecha_solicitud),
                'fecha_inicio'         => $fmtDate($inv->fecha_inicio),
                'fecha_fin'            => $fmtDate($inv->getRawOriginal('fecha_fin')),
                'id_caja'              => $inv->id_caja,
                'caja'                 => $inv->caja ? [
                    'id_caja' => $inv->caja->id_caja,
                    'nombre'  => $inv->caja->nombre,
                ] : null,

                'plan'                 => $inv->plan ? [
                    'id'          => $inv->plan->id,
                    'label'       => $this->planLabel($inv->plan),
                    'periodo'     => $inv->plan->periodo,
                    'rendimiento' => $inv->plan->rendimiento,
                    'nombre'      => $inv->plan->nombre ?? null,
                ] : null,

                'payment_method'           => Schema::hasColumn('user_inversiones', 'payment_method') ? ($inv->payment_method ?? null) : null,
                'payment_status'           => Schema::hasColumn('user_inversiones', 'payment_status') ? ($inv->payment_status ?? null) : null,
                'stripe_status'            => Schema::hasColumn('user_inversiones', 'payment_status') ? ($inv->payment_status ?? null) : null,
                'stripe_session_id'        => Schema::hasColumn('user_inversiones', 'stripe_session_id') ? ($inv->stripe_session_id ?? null) : null,
                'stripe_payment_intent_id' => Schema::hasColumn('user_inversiones', 'stripe_payment_intent_id') ? ($inv->stripe_payment_intent_id ?? null) : null,
                'stripe_paid_at'           => Schema::hasColumn('user_inversiones', 'stripe_paid_at') ? $fmtDateTime($inv->stripe_paid_at) : null,
            ],
        ], 201);
    }

    /** DELETE /api/inversiones/{id} — solo si esta Pendiente (1) y sin movimientos. */
    public function destroy(Request $request, int $id)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        $inv = UserInversion::where('id', $id)
            ->where('id_cliente', $cliente->id)
            ->first();

        if (!$inv) {
            return response()->json(['ok' => false, 'error' => 'Inversion no encontrada.'], 404);
        }

        if ((int)$inv->status !== 1) {
            return response()->json([
                'ok'    => false,
                'error' => 'Solo puedes eliminar inversiones en estado Pendiente.',
            ], 409);
        }

        $tieneMov = MovimientoCaja::where('origen_id', $inv->id)->exists();
        if (!$tieneMov) {
            $tieneMov = MovimientoCaja::where('descripcion', 'like', "%inversion #{$inv->id}%")->exists();
        }
        if ($tieneMov) {
            return response()->json([
                'ok'    => false,
                'error' => 'La inversion esta vinculada a movimientos de caja y no puede eliminarse.',
            ], 409);
        }

        $inv->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Inversion eliminada.',
        ], 200);
    }

    /** GET /api/inversiones/planes */
    public function planes(Request $request)
    {
        $soloActivas = filter_var($request->input('solo_activas', '1'), FILTER_VALIDATE_BOOLEAN);

        $q = Inversion::query()
            ->when($soloActivas, fn($qq) => $qq->where('status', 1))
            ->orderBy('periodo');

        $cols = ['id', 'periodo', 'rendimiento', 'status'];
        if (Schema::hasColumn('inversiones', 'nombre')) $cols[] = 'nombre';

        if (Schema::hasColumn('inversiones', 'monto_minimo')) $cols[] = 'monto_minimo';
        if (Schema::hasColumn('inversiones', 'monto_maximo')) $cols[] = 'monto_maximo';
        if (Schema::hasColumn('inversiones', 'monto_min'))     $cols[] = 'monto_min';
        if (Schema::hasColumn('inversiones', 'monto_max'))     $cols[] = 'monto_max';

        $planes = $q->get($cols)->map(function ($p) {
            return [
                'id'           => $p->id,
                'label'        => $this->planLabel($p),
                'periodo'      => $p->periodo,
                'rendimiento'  => $p->rendimiento,
                'status'       => (int)($p->status ?? 0),
                'nombre'       => $p->nombre ?? null,
                'monto_minimo' => isset($p->monto_minimo) ? (float)$p->monto_minimo : (isset($p->monto_min) ? (float)$p->monto_min : null),
                'monto_maximo' => isset($p->monto_maximo) ? (float)$p->monto_maximo : (isset($p->monto_max) ? (float)$p->monto_max : null),
            ];
        });

        return response()->json([
            'ok'   => true,
            'data' => $planes,
        ]);
    }
}
