<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\UserDeposito;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\CategoriaIngreso;
use App\Services\ProveedorResolver;
use App\Services\VisibilityScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

// ✅ MAILS
use Illuminate\Support\Facades\Mail;
use App\Mail\DepositoAprobadoStripeClienteMail;
use App\Mail\DepositoAprobadoStripeAdminMail;
use App\Mail\DepositoAprobadoComprobanteClienteMail;
use App\Mail\DepositoAprobadoComprobanteAdminMail;
use App\Mail\DepositoRechazadoClienteMail;
use App\Mail\DepositoRechazadoAdminMail;

class UserDepositoController extends Controller
{
    /** @var ProveedorResolver */
    protected $proveedorResolver;

    public function __construct(ProveedorResolver $proveedorResolver)
    {
        $this->proveedorResolver = $proveedorResolver;

        $this->middleware('auth');
        $this->middleware('role:admin')->only(['create','store']);
    }

    /** INDEX GENERAL */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status'); // puede venir "0" o 0
        $desde  = $request->input('desde');
        $hasta  = $request->input('hasta');
        $orden  = $request->input('orden', 'fecha_desc');

        $tbl = (new UserDeposito)->getTable(); // user_depositos

        // ✅ Evita que un JOIN (VisibilityScope) pise {$tbl}.status
        $query = UserDeposito::query()
            ->from($tbl)
            ->select("{$tbl}.*")
            ->with(['cliente:id,nombre,apellido,email', 'caja:id_caja,nombre']);

        // ⛨ Limitar por sucursal según permisos del módulo "depositos"
        $query = VisibilityScope::depositos($query, Auth::user());

        $statusIsValid = $request->filled('status') && in_array((int)$status, [0, 1, 2], true);

        $query
            ->when($search !== '', function ($q) use ($search, $tbl) {
                $q->where(function ($qq) use ($search, $tbl) {
                    $qq->whereHas('cliente', function ($qc) use ($search) {
                        $qc->where('nombre', 'like', "%{$search}%")
                           ->orWhere('apellido', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhere("{$tbl}.cantidad", 'like', "%{$search}%")
                    ->orWhere("{$tbl}.nota", 'like', "%{$search}%");

                    if (is_numeric($search)) {
                        $qq->orWhere("{$tbl}.id", (int)$search);
                    }
                });
            })
            ->when($statusIsValid, fn($q) => $q->where("{$tbl}.status", (int)$status))
            ->when($desde, fn($q) => $q->whereDate("{$tbl}.fecha_deposito", '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate("{$tbl}.fecha_deposito", '<=', $hasta));

        $query = match ($orden) {
            'monto_asc'  => $query->orderBy("{$tbl}.cantidad", 'asc'),
            'monto_desc' => $query->orderBy("{$tbl}.cantidad", 'desc'),
            'fecha_asc'  => $query->orderBy("{$tbl}.fecha_deposito", 'asc'),
            'fecha_desc' => $query->orderBy("{$tbl}.fecha_deposito", 'desc'),
            default      => $query->orderBy("{$tbl}.fecha_deposito", 'desc'),
        };

        $depositos = $query->paginate(15)->withQueryString();

        $statusOptions = [
            '' => 'Todos',
            0  => 'Pendiente',
            1  => 'Aprobado',
            2  => 'Rechazado',
        ];

        return view('depositos.index', compact('depositos', 'search', 'status', 'statusOptions', 'desde', 'hasta', 'orden'));
    }

    /** SHOW */
    public function show(UserDeposito $deposito)
    {
        $deposito->load(['cliente', 'caja']);

        $statusOptions = [
            0 => 'Pendiente',
            1 => 'Aprobado',
            2 => 'Rechazado',
        ];

        $cajas = Caja::orderBy('nombre')
            ->when(Schema::hasColumn('cajas','estado'), fn($q)=>$q->where('estado','abierta'))
            ->get(['id_caja','nombre']);

        $archivoUrl = null;
        if ($deposito->deposito) {
            $path = 'depositos/'.$deposito->deposito;
            if (Storage::disk('public')->exists($path)) {
                $archivoUrl = Storage::disk('public')->url($path);
            }
        }

        return view('depositos.show', compact('deposito','statusOptions','cajas','archivoUrl'));
    }

    /** CREATE (panel admin – emergencia) */
    public function create()
    {
        $clientes = Cliente::where('status', 1)
            ->orderBy('nombre')
            ->get(['id','nombre','apellido','email']);

        $cajasQuery = Caja::query()->orderBy('nombre');
        if (Schema::hasColumn('cajas', 'estado')) {
            $cajasQuery->where('estado', 'abierta');
        }
        $cajas = $cajasQuery->get(['id_caja','nombre']);

        return view('depositos.create', compact('clientes','cajas'));
    }

    /** STORE (panel admin – emergencia) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cliente'     => 'required|exists:clientes,id',
            'cantidad'       => 'required|numeric|min:0.01',
            'fecha_deposito' => 'required|date',
            'nota'           => 'nullable|string|max:500',
            'id_caja'        => 'required|exists:cajas,id_caja',
            'deposito'       => 'nullable|file|mimetypes:image/jpeg,image/png,application/pdf|max:4096',
        ]);

        return DB::transaction(function () use ($request, $data) {
            $filename = null;
            if ($request->hasFile('deposito') && Schema::hasColumn('user_depositos','deposito')) {
                $path = $request->file('deposito')->store('depositos', 'public');
                $filename = basename($path);
            }

            $payload = [
                'id_cliente'     => (int) $data['id_cliente'],
                'cantidad'       => (float) $data['cantidad'],
                'fecha_deposito' => $data['fecha_deposito'],
                'nota'           => $data['nota'] ?? null,
                'status'         => 1,
                'id_usuario'     => Auth::id(),
                'id_caja'        => (int) $data['id_caja'],
            ];

            if ($filename) $payload['deposito'] = $filename;
            if (Schema::hasColumn('user_depositos','es_emergencia')) $payload['es_emergencia'] = true;

            $deposito = UserDeposito::create($payload);

            $this->ingresarPagoEnCaja($deposito);

            return redirect()
                ->route('depositos.index')
                ->with('success', 'Depósito de emergencia creado, aprobado e impactado en caja.');
        });
    }

    /** UPDATE (admin aprueba/rechaza) */
    public function update(Request $request, UserDeposito $deposito)
    {
        $data = $request->validate([
            'status'  => 'required|in:0,1,2',
            'nota'    => 'nullable|string',
            'id_caja' => 'nullable|integer|exists:cajas,id_caja',
        ]);

        // Si va a aprobar, necesita caja (la nueva o la que ya trae)
        if ((int)$data['status'] === 1) {
            $idCajaForApproval = $data['id_caja'] ?? $deposito->id_caja;
            if (empty($idCajaForApproval)) {
                return back()
                    ->withErrors(['id_caja' => 'Debes seleccionar una caja para aprobar el depósito.'])
                    ->withInput();
            }
        }

        // ✅ Para notificaciones post-commit
        $notifyEvent = null;   // 'approved' | 'rejected' | null
        $notifyType  = null;   // 'stripe' | 'file' | null
        $notifyId    = null;

        try {
            DB::transaction(function () use ($deposito, $data, &$notifyEvent, &$notifyType, &$notifyId) {
                $tbl = (new UserDeposito)->getTable();

                /** @var UserDeposito $row */
                $row = UserDeposito::query()
                    ->from($tbl)
                    ->select("{$tbl}.*")
                    ->where("{$tbl}.id", $deposito->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $prevStatus = (int) $row->status;
                $newStatus  = (int) $data['status'];

                // Si viene id_caja en request, se actualiza
                if (array_key_exists('id_caja', $data) && !is_null($data['id_caja'])) {
                    $row->id_caja = (int) $data['id_caja'];
                }

                // ✅ Si el depósito viene de Stripe, solo permitir aprobar si está "paid"
                if ($newStatus === 1 && $this->isStripeDeposit($row) && !$this->isStripePaid($row)) {
                    throw ValidationException::withMessages([
                        'status' => 'Este depósito viene de Stripe pero aún no está marcado como pagado (paid). Espera el webhook o revisa el pago.',
                    ]);
                }

                $row->status = $newStatus;
                $row->nota   = $data['nota'] ?? $row->nota;
                $row->save();

                // Solo notificar si realmente cambió el status
                if ($prevStatus !== $newStatus) {
                    $type = $this->isStripeDeposit($row) ? 'stripe' : 'file';

                    if ($newStatus === 1) {
                        // ✅ Si pasa a Aprobado y antes no lo estaba -> movimiento una sola vez
                        if ($prevStatus !== 1) {
                            $exists = MovimientoCaja::where('origen_id', $row->id)
                                ->where('tipo_mov', 'Ingreso')
                                ->exists();

                            if (!$exists) {
                                $this->ingresarPagoEnCaja($row->fresh());
                            }
                        }

                        $notifyEvent = 'approved';
                        $notifyType  = $type;
                        $notifyId    = $row->id;
                    }

                    if ($newStatus === 2) {
                        $notifyEvent = 'rejected';
                        $notifyType  = $type;
                        $notifyId    = $row->id;
                    }
                }
            });
        } catch (ValidationException $ve) {
            throw $ve;
        }

        // ✅ Enviar correos FUERA de la transacción (no romper si falla mail)
        if ($notifyEvent && $notifyType && $notifyId) {
            try {
                $dep = UserDeposito::with([
                        'cliente:id,nombre,apellido,email',
                        'caja:id_caja,nombre',
                    ])->find($notifyId);

                if ($dep && $dep->cliente) {
                    $cliente = $dep->cliente;

                    // Archivo (para admin en comprobante)
                    $archivoUrl = null;
                    if (!empty($dep->deposito)) {
                        $path = 'depositos/'.$dep->deposito;
                        if (Storage::disk('public')->exists($path)) {
                            $archivoUrl = Storage::disk('public')->url($path);
                        }
                    }

                    $adminEmail = trim((string) config('services.admin.email'));

                    // ===== APROBADO =====
                    if ($notifyEvent === 'approved') {
                        if ($notifyType === 'stripe') {
                            // Cliente
                            if (!empty($cliente->email)) {
                                Mail::to($cliente->email)->send(
                                    new DepositoAprobadoStripeClienteMail($dep, $cliente)
                                );
                            }
                            // Admin
                            if ($adminEmail !== '') {
                                Mail::to($adminEmail)->send(
                                    new DepositoAprobadoStripeAdminMail($dep, $cliente)
                                );
                            }
                        } else {
                            // Comprobante
                            if (!empty($cliente->email)) {
                                Mail::to($cliente->email)->send(
                                    new DepositoAprobadoComprobanteClienteMail($dep, $cliente)
                                );
                            }
                            if ($adminEmail !== '') {
                                Mail::to($adminEmail)->send(
                                    new DepositoAprobadoComprobanteAdminMail($dep, $cliente, $archivoUrl)
                                );
                            }
                        }
                    }

                    // ===== RECHAZADO =====
                    if ($notifyEvent === 'rejected') {
                        if (!empty($cliente->email)) {
                            Mail::to($cliente->email)->send(
                                new DepositoRechazadoClienteMail($dep, $cliente)
                            );
                        }
                        if ($adminEmail !== '') {
                            Mail::to($adminEmail)->send(
                                new DepositoRechazadoAdminMail($dep, $cliente, $archivoUrl)
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Error enviando correos de depósito (update status)', [
                    'deposito_id' => $notifyId,
                    'event' => $notifyEvent,
                    'type' => $notifyType,
                    'ex' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Depósito actualizado correctamente.');
    }

    /** API: Registro desde cliente legado → deja PENDIENTE (0) */
    public function storeFromLegacy(Request $request)
    {
        try {
            $data = $request->validate([
                'id_cliente'        => 'required|integer|exists:clientes,id',
                'fecha_deposito'    => 'required|date',
                'cantidad_deposito' => 'required|string',
                'deposito'          => 'nullable|file|mimetypes:image/jpeg,image/png,application/pdf',
                'id_caja'           => 'nullable|integer|exists:cajas,id_caja',
                'nota'              => 'nullable|string',
            ]);

            $raw  = trim($data['cantidad_deposito']);
            $norm = str_replace([' ', '$'], '', $raw);
            if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $norm)) {
                $norm = str_replace('.', '', $norm);
                $norm = str_replace(',', '.', $norm);
            } else {
                $norm = str_replace(',', '', $norm);
            }
            $cantidad = (float) $norm;

            $filename = null;
            $publicUrl = null;
            if ($request->hasFile('deposito')) {
                $path = $request->file('deposito')->store('depositos', 'public');
                $filename = basename($path);
                $publicUrl = asset('storage/'.$path);
            }

            $apiUserId = (int) config('app.api_user_id', 1);

            $defaultCajaId = (int) config('app.caja_pendientes_id', 1);
            if ($defaultCajaId <= 0) {
                $defaultCajaId = \App\Models\Caja::query()
                    ->when(Schema::hasColumn('cajas','estado'), fn($q)=>$q->where('estado','abierta'))
                    ->value('id_caja') ?? \App\Models\Caja::query()->value('id_caja');
            }

            $deposito = DB::transaction(function () use ($data, $cantidad, $filename, $apiUserId, $defaultCajaId) {
                $payload = [
                    'id_cliente'     => (int) $data['id_cliente'],
                    'cantidad'       => $cantidad,
                    'fecha_deposito' => $data['fecha_deposito'],
                    'nota'           => $data['nota'] ?? null,
                    'status'         => 0,
                    'id_usuario'     => $apiUserId,
                    'id_caja'        => (int) ($data['id_caja'] ?? $defaultCajaId),
                ];

                if ($filename && Schema::hasColumn('user_depositos', 'deposito')) {
                    $payload['deposito'] = $filename;
                }

                $dep = UserDeposito::create($payload);

                return $dep->load(['cliente:id,nombre,apellido,email', 'caja:id_caja,nombre']);
            });

            return response()->json([
                'ok'       => true,
                'message'  => 'Depósito registrado en estado pendiente.',
                'deposito' => $deposito,
                'archivo'  => $publicUrl,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'       => false,
                'error'    => 'Datos inválidos',
                'detalles' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok'    => false,
                'error' => 'Error interno al registrar el depósito',
            ], 500);
        }
    }

    /** Detecta si el depósito viene de Stripe */
    protected function isStripeDeposit(UserDeposito $deposito): bool
    {
        $tbl = $deposito->getTable();

        $pm = Schema::hasColumn($tbl, 'payment_method') ? (string)($deposito->payment_method ?? '') : '';
        if (strtolower($pm) === 'stripe') return true;

        $ss = Schema::hasColumn($tbl, 'stripe_status') ? (string)($deposito->stripe_status ?? '') : '';
        if ($ss !== '') return true;

        $sess = Schema::hasColumn($tbl, 'stripe_checkout_session_id') ? (string)($deposito->stripe_checkout_session_id ?? '') : '';
        return $sess !== '';
    }

    /** Verifica si Stripe está confirmado como pagado (paid). */
    protected function isStripePaid(UserDeposito $deposito): bool
    {
        $tbl = $deposito->getTable();

        $ps = Schema::hasColumn($tbl, 'payment_status') ? strtolower((string)($deposito->payment_status ?? '')) : '';
        $ss = Schema::hasColumn($tbl, 'stripe_status') ? strtolower((string)($deposito->stripe_status ?? '')) : '';

        if (in_array($ps, ['paid', 'succeeded'], true)) return true;
        if (in_array($ss, ['paid', 'succeeded'], true)) return true;

        if (Schema::hasColumn($tbl, 'stripe_payment_intent_id')) {
            $pi = (string)($deposito->stripe_payment_intent_id ?? '');
            if (trim($pi) !== '' && $ss === 'paid') return true;
        }

        return false;
    }

    /** Crea movimiento en caja para un depósito aprobado. */
    protected function ingresarPagoEnCaja(UserDeposito $deposito): void
    {
        $exists = MovimientoCaja::where('origen_id', $deposito->id)
            ->where('tipo_mov', 'Ingreso')
            ->exists();
        if ($exists) return;

        $caja = Caja::findOrFail($deposito->id_caja);

        $last    = $caja->movimientos()->latest('fecha')->first();
        $antes   = $last ? $last->monto_posterior : $caja->saldo_inicial;
        $monto   = (float) $deposito->cantidad;
        $despues = $antes + $monto;

        $catDepositos = CategoriaIngreso::firstOrCreate(
            ['nombre' => 'Depósitos'],
            ['id_usuario' => 1]
        );

        $proveedorId = $this->proveedorResolver->ensureFromCliente($deposito->id_cliente);

        $fechaMov = $deposito->fecha_deposito;
        if (Schema::hasColumn($deposito->getTable(), 'fecha_pago') && !empty($deposito->fecha_pago)) {
            $fechaMov = $deposito->fecha_pago;
        }

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'tipo_mov'        => 'Ingreso',
            'id_cat_ing'      => $catDepositos->id_cat_ing,
            'id_sub_ing'      => null,
            'id_cat_gasto'    => null,
            'id_sub_gasto'    => null,
            'proveedor_id'    => $proveedorId,
            'origen_id'       => $deposito->id,
            'monto'           => $monto,
            'fecha'           => $fechaMov,
            'descripcion'     => "Depósito #{$deposito->id}",
            'monto_anterior'  => $antes,
            'monto_posterior' => $despues,
            'id_usuario'      => Auth::id(),
        ]);

        $caja->update(['saldo_final' => $despues]);
    }
}
