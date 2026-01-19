<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CategoriaGasto;
use App\Models\CategoriaIngreso;
use App\Models\Cliente;
use App\Models\Prestamo;
use App\Models\User;            // ✅ NUEVO (usuarios)
use App\Models\UserAbono;
use App\Models\UserPrestamo;
use App\Models\MovimientoCaja;
use App\Mail\PrestamoAutorizadoAdminMail;
use App\Mail\PrestamoAutorizadoClienteMail;
use App\Notifications\NuevaSolicitudNotification;
use App\Services\ProveedorResolver;
use App\Services\VisibilityScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class UserPrestamoController extends Controller
{
    /** @var ProveedorResolver */
    protected $proveedorResolver;

    public function __construct(ProveedorResolver $proveedorResolver)
    {
        $this->proveedorResolver = $proveedorResolver;
    }

    /* ============================ Helpers de alcance ============================ */

    /** Devuelve ids de sucursales asignadas (pivote + principal). */
    protected function getUserSucursalIds(): array
    {
        $u = Auth::user();
        $ids = DB::table('usuario_sucursal_acceso')
            ->where('usuario_id', $u->id_usuario ?? $u->id)
            ->pluck('id_sucursal')
            ->filter()
            ->map(fn ($x) => (int) $x)
            ->all();

        if (!empty($u->id_sucursal)) {
            $ids[] = (int) $u->id_sucursal;
        }
        return array_values(array_unique($ids));
    }

    /** Devuelve el id_sucursal asociado al préstamo (por caja o por cliente) si se puede inferir. */
    protected function getPrestamoSucursalId(UserPrestamo $p): ?int
    {
        if (!empty($p->id_caja)) {
            $sucId = Caja::where('id_caja', $p->id_caja)->value('id_sucursal');
            if (!is_null($sucId)) return (int) $sucId;
        }
        if (!empty($p->id_cliente)) {
            $sucId = Cliente::where('id', $p->id_cliente)->value('id_sucursal');
            if (!is_null($sucId)) return (int) $sucId;
        }
        return null;
    }

    /** Autorización por registro (solo existen: ver y ver_asignadas). */
    protected function authorizePrestamoRecord(UserPrestamo $prestamo): void
    {
        $u = Auth::user();

        if ($u->can('user_prestamos.ver') || $u->can('prestamos.ver')) {
            return;
        }

        if ($u->can('user_prestamos.ver_asignadas') || $u->can('prestamos.ver_asignadas')) {
            $sid = $this->getPrestamoSucursalId($prestamo);
            if ($sid === null) {
                abort(403, 'No se pudo determinar la sucursal del préstamo.');
            }
            $ids = $this->getUserSucursalIds();
            if (in_array((int) $sid, $ids, true)) {
                return;
            }
            abort(403, 'No tienes permiso para este préstamo (otra sucursal).');
        }

        abort(403, 'No tienes permiso para operar préstamos.');
    }

    /** Valida que un CLIENTE esté dentro del alcance visible del usuario. */
    protected function assertClienteVisible(int $clienteId): void
    {
        $u = Auth::user();
        $visible = VisibilityScope::clientes(
            Cliente::query()->whereKey($clienteId),
            $u
        )->exists();

        if (!$visible) {
            abort(403, 'El cliente seleccionado no está dentro de tu alcance.');
        }
    }

    /** Valida que una CAJA esté dentro del alcance visible del usuario. */
    protected function assertCajaVisible(int $cajaId): void
    {
        $u = Auth::user();
        $visible = VisibilityScope::cajas(
            Caja::query()->where('id_caja', $cajaId),
            $u
        )->exists();

        if (!$visible) {
            abort(403, 'La caja seleccionada no está dentro de tu alcance.');
        }
    }

    /**
     * ✅ NUEVO: marcar en empleado_comisiones los últimos N abonos (N = comision_semanas del plan)
     * - requiere que el préstamo tenga id_empleado
     * - requiere que ya existan los abonos (user_abonos)
     */
    protected function marcarComisionesUltimasSemanas(UserPrestamo $prestamo): void
    {
        if (empty($prestamo->id_empleado)) {
            return; // sin empleado asignado, no hay comisión
        }

        // Tomar N desde el plan (prestamos.comision_semanas), default 1
        $plan = Prestamo::where('id_prestamo', $prestamo->id_activo)->first();
        $n = (int) ($plan->comision_semanas ?? 1);
        if ($n <= 0) $n = 1;

        // Últimos N abonos del préstamo
        $abonos = UserAbono::where('user_prestamo_id', $prestamo->id)
            ->orderByDesc('num_pago')
            ->limit($n)
            ->get();

        if ($abonos->isEmpty()) return;

        foreach ($abonos as $a) {
            DB::table('empleado_comisiones')->updateOrInsert(
                [
                    'id_empleado'      => (int) $prestamo->id_empleado,
                    'user_prestamo_id' => (int) $prestamo->id,
                    'user_abono_id'    => (int) $a->id,
                ],
                [
                    'id_cliente'  => (int) $prestamo->id_cliente,
                    'num_pago'    => $a->num_pago !== null ? (int) $a->num_pago : null,
                    'monto_abono' => (float) $a->cantidad,
                    'status'      => 0, // pendiente
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }
    }

    /* ================================== INDEX ================================== */

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status'); // 1..6 o null
        $desde  = $request->input('desde');
        $hasta  = $request->input('hasta');
        $orden  = $request->input('orden', 'fecha_desc');

        $q = UserPrestamo::query()
            ->select('user_prestamos.*')
            ->with([
                'cliente:id,nombre,apellido,email',
                'caja:id_caja,nombre',
                'empleado:id_usuario,name' // ✅ NUEVO
            ]);

        // Visibilidad por alcance
        $q = VisibilityScope::userPrestamos($q, Auth::user());

        // Filtros
        $q->when($search !== '', function ($qq) use ($search) {
                $qq->where(function ($w) use ($search) {
                    $w->whereHas('cliente', function ($qc) use ($search) {
                            $qc->where('nombre', 'like', "%{$search}%")
                               ->orWhere('apellido', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                        })
                      ->orWhere('user_prestamos.id', $search)
                      ->orWhere('user_prestamos.cantidad', 'like', "%{$search}%");
                });
            })
          ->when(in_array($status, ['1','2','3','4','5','6'], true),
                fn($qq) => $qq->where('user_prestamos.status', (int) $status))
          ->when($desde, fn($qq) => $qq->whereDate('user_prestamos.fecha_inicio', '>=', $desde))
          ->when($hasta, fn($qq) => $qq->whereDate('user_prestamos.fecha_inicio', '<=', $hasta));

        $q = match ($orden) {
            'monto_asc'  => $q->orderBy('user_prestamos.cantidad', 'asc'),
            'monto_desc' => $q->orderBy('user_prestamos.cantidad', 'desc'),
            'fecha_asc'  => $q->orderBy('user_prestamos.fecha_inicio', 'asc'),
            'fecha_desc' => $q->orderBy('user_prestamos.fecha_inicio', 'desc'),
            default      => $q->orderBy('user_prestamos.fecha_inicio', 'desc'),
        };

        $prestamos = $q->paginate(15)->withQueryString();

        $statusOptions = [
            null => 'Todos',
            1 => 'Autorizado',
            2 => 'Pendiente',
            3 => 'En revisión',
            4 => 'Rechazado',
            5 => 'Pagado',
            6 => 'Terminado',
        ];

        return view('adminuserprestamos.index', compact(
            'prestamos', 'search', 'status', 'statusOptions', 'desde', 'hasta', 'orden'
        ));
    }

    /* ================================== SHOW =================================== */

    public function show(UserPrestamo $prestamo)
    {
        $this->authorizePrestamoRecord($prestamo);

        $prestamo->load(['cliente', 'caja', 'aval', 'abonos', 'empleado:id_usuario,name']); // ✅

        $statusOptions = [
            1 => 'Autorizado',
            2 => 'Pendiente',
            3 => 'En revisión',
            4 => 'Rechazado',
            5 => 'Pagado',
            6 => 'Terminado',
        ];

        return view('adminuserprestamos.show', compact('prestamo', 'statusOptions'));
    }

    /* ================================= CREATE ================================= */

    public function create()
    {
        $u   = Auth::user();
        $ids = $this->getUserSucursalIds();

        // === Clientes
        $clientesQ = DB::table('clientes')->select(
            'id',
            'nombre',
            'apellido',
            'email',
            'id_sucursal',
            DB::raw('TRIM(CONCAT(COALESCE(nombre,""), " ", COALESCE(apellido,""))) AS nombre_full')
        );

        if ($u->can('user_prestamos.ver_asignadas') || $u->can('clientes.ver_asignadas')) {
            $clientesQ->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
        } elseif (! ($u->can('user_prestamos.ver') || $u->can('clientes.ver'))) {
            $clientesQ->whereRaw('0=1');
        }

        $clientes = $clientesQ->orderBy('nombre_full')->get([
            'id','nombre','apellido','email'
        ]);

        // === Cajas abiertas
        $cajasQ = DB::table('cajas')->select(
                'id_caja',
                'nombre',
                'id_sucursal',
                'saldo_final',
                'saldo_inicial',
                'fecha_apertura'
            )
            ->where('estado', 'abierta');

        if ($u->can('cajas.ver_asignadas') || $u->can('user_prestamos.ver_asignadas')) {
            $cajasQ->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
        } elseif (! ($u->can('cajas.ver') || $u->can('user_prestamos.ver'))) {
            $cajasQ->whereRaw('0=1');
        }

        $cajas = $cajasQ->orderBy('nombre')->get([
            'id_caja','nombre','saldo_final','saldo_inicial','fecha_apertura'
        ]);

        // === Planes activos
        $planes = Prestamo::where('status', 1)->get();

        // ✅ NUEVO: empleados cobradores (para asignar comisiones)
        $empleadosQ = User::query()
            ->select('id_usuario','name','id_sucursal','rol','activo')
            ->where('activo', 1)
            ->where('rol', 'cobrador');

        if ($u->can('user_prestamos.ver_asignadas')) {
            $empleadosQ->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
        }

        $empleados = $empleadosQ->orderBy('name')->get();

        return view('adminuserprestamos.create', compact('clientes','planes','cajas','empleados'));
    }

    /* ================================= STORE ================================== */

    /** STORE: si status=5 (Pagado) => crear abonos + egreso de caja + marcar comisiones */
    public function store(Request $request)
    {
        $rules = [
            'id_cliente'   => ['required', 'exists:clientes,id', function ($attr, $value, $fail) {
                if (UserPrestamo::where('id_cliente', $value)->whereIn('status', [2, 3])->exists()) {
                    $fail('Ya tienes una solicitud pendiente o en revisión.');
                }
            }],
            'id_activo'    => 'required|exists:prestamos,id_prestamo',
            'fecha_inicio' => 'required|date',
            'cantidad'     => 'required|numeric|min:0',
            'id_caja'      => 'required|exists:cajas,id_caja',
            'status'       => 'required|in:1,2,3,4,5,6',
            'codigo_aval'  => 'nullable|string|exists:clientes,codigo_cliente|max:50',

            // ✅ NUEVO
            'id_empleado'  => 'nullable|exists:usuarios,id_usuario',
        ];

        if (!$request->filled('codigo_aval')) {
            $rules += [
                'doc_solicitud_aval'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'doc_comprobante_domicilio' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'doc_ine_frente'            => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'doc_ine_reverso'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ];
        }

        $request->validate($rules);

        $this->assertClienteVisible((int) $request->id_cliente);
        $this->assertCajaVisible((int) $request->id_caja);

        $data = $request->only('id_cliente', 'id_activo', 'fecha_inicio', 'cantidad', 'codigo_aval', 'id_caja', 'status', 'id_empleado');

        $plan = Prestamo::findOrFail($data['id_activo']);

        // ✅ Si no mandan empleado y el usuario logueado es cobrador, lo asignamos a él.
        $idEmpleado = $data['id_empleado'] ?? null;
        $u = Auth::user();
        if (!$idEmpleado && ($u->rol ?? null) === 'cobrador') {
            $idEmpleado = $u->id_usuario;
        }

        $prestamo = new UserPrestamo([
            'id_cliente'       => $data['id_cliente'],
            'id_activo'        => $data['id_activo'],
            'fecha_solicitud'  => now(),
            'fecha_inicio'     => $data['fecha_inicio'],
            'cantidad'         => $data['cantidad'],
            'tipo_prestamo'    => $plan->periodo,
            'semanas'          => $plan->semanas,
            'interes'          => $plan->interes,
            'interes_generado' => $data['cantidad'] * $plan->interes / 100,
            'status'           => (int) $data['status'],
            'aval_status'      => 2,
            'id_usuario'       => Auth::id(),
            'id_empleado'      => $idEmpleado, // ✅ NUEVO
            'abonos_echos'     => 0,
            'num_atrasos'      => 0,
            'mora_acumulada'   => 0.00,
            'id_caja'          => $data['id_caja'],
        ]);

        if (!empty($data['codigo_aval'])) {
            $aval = Cliente::where('codigo_cliente', $data['codigo_aval'])->first();
            $prestamo->aval_id = $aval?->id;
        } else {
            foreach (['doc_solicitud_aval', 'doc_comprobante_domicilio', 'doc_ine_frente', 'doc_ine_reverso'] as $field) {
                if ($request->hasFile($field)) {
                    $prestamo->{$field} = $request->file($field)->store('prestamos/aval', 'public');
                }
            }
        }

        $prestamo->save();

        if ((int) $prestamo->status === 5) {
            $this->generarAbonosSiFaltan($prestamo);
            $this->descontarDeCaja($prestamo);

            // ✅ NUEVO: marcar últimas semanas comisionables
            $this->marcarComisionesUltimasSemanas($prestamo);
        }

        return redirect()->route('user_prestamos.index')
            ->with('success', 'Préstamo creado correctamente' . ((int) $prestamo->status === 5 ? ' (abonos generados, desembolso y comisiones marcadas).' : '.'));
    }

    /* ================================== EDIT =================================== */

    public function edit(UserPrestamo $prestamo)
    {
        $this->authorizePrestamoRecord($prestamo);

        $statusOptions = [
            1 => 'Autorizado',
            2 => 'Pendiente',
            3 => 'En revisión',
            4 => 'Rechazado',
            5 => 'Pagado',
            6 => 'Terminado',
        ];

        $u   = Auth::user();
        $ids = $this->getUserSucursalIds();

        $cajasQ = DB::table('cajas')->select(
                'id_caja',
                'nombre',
                'id_sucursal',
                'saldo_final',
                'saldo_inicial',
                'fecha_apertura'
            )
            ->where('estado', 'abierta');

        if ($u->can('cajas.ver_asignadas') || $u->can('user_prestamos.ver_asignadas')) {
            $cajasQ->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
        } elseif (! ($u->can('cajas.ver') || $u->can('user_prestamos.ver'))) {
            $cajasQ->whereRaw('0=1');
        }

        $cajas = $cajasQ->orderBy('nombre')->get([
            'id_caja','nombre','saldo_final','saldo_inicial','fecha_apertura'
        ]);

        // ✅ NUEVO: empleados cobradores
        $empleadosQ = User::query()
            ->select('id_usuario','name','id_sucursal','rol','activo')
            ->where('activo', 1)
            ->where('rol', 'cobrador');

        if ($u->can('user_prestamos.ver_asignadas')) {
            $empleadosQ->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
        }

        $empleados = $empleadosQ->orderBy('name')->get();

        return view('adminuserprestamos.edit', compact('prestamo','statusOptions','cajas','empleados'));
    }

    /* ================================= UPDATE ================================== */

    public function update(Request $request, UserPrestamo $prestamo)
    {
        $this->authorizePrestamoRecord($prestamo);

        $data = $request->validate([
            'status'      => 'required|in:1,2,3,4,5,6',
            'nota'        => 'nullable|string',
            'id_caja'     => 'required|exists:cajas,id_caja',

            // ✅ NUEVO (opcional): reasignar cobrador
            'id_empleado' => 'nullable|exists:usuarios,id_usuario',
        ]);

        $this->assertCajaVisible((int) $data['id_caja']);

        $oldStatus = (int) $prestamo->status;
        $newStatus = (int) $data['status'];

        $enviarCorreoAutorizado = false;

        $prestamo->update([
            'status'            => $newStatus,
            'nota'              => $data['nota'] ?? null,
            'aval_responded_at' => now(),
            'id_caja'           => $data['id_caja'],
            'id_empleado'       => $data['id_empleado'] ?? $prestamo->id_empleado, // ✅
        ]);

        if ($oldStatus !== 1 && $newStatus === 1) {
            $enviarCorreoAutorizado = true;
        }

        // Cambio a Pagado → abonos + egreso + comisiones
        if ($oldStatus !== 5 && $newStatus === 5) {
            $this->generarAbonosSiFaltan($prestamo);
            $this->descontarDeCaja($prestamo);

            // ✅ NUEVO
            $this->marcarComisionesUltimasSemanas($prestamo);
        }

        // Cambio a Terminado → ingreso (capital + intereses)
        if ($oldStatus !== 6 && $newStatus === 6) {
            $this->ingresarPagoEnCaja($prestamo);
        }

        if ($enviarCorreoAutorizado) {
            try {
                $prestamo->loadMissing(['cliente', 'plan', 'caja']);

                Mail::to('admingrowcap@casabarrel.com')
                    ->send(new PrestamoAutorizadoAdminMail($prestamo));

                if (!empty($prestamo->cliente?->email)) {
                    Mail::to($prestamo->cliente->email)
                        ->send(new PrestamoAutorizadoClienteMail($prestamo));
                }

                $clienteNombre = trim(sprintf(
                    '%s %s',
                    (string)($prestamo->cliente?->nombre ?? ''),
                    (string)($prestamo->cliente?->apellido ?? '')
                ));
                $titulo = 'Préstamo autorizado';
                $mensaje = $clienteNombre !== '' ? "Préstamo del cliente {$clienteNombre} fue autorizado." : 'Se autorizó un préstamo.';
                $url = route('user_prestamos.show', $prestamo);

                User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
                    $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
                });
            } catch (\Throwable $e) {
                Log::error('Error enviando correo de préstamo autorizado', [
                    'prestamo_id' => $prestamo->id ?? null,
                    'cliente_id'  => $prestamo->id_cliente ?? null,
                    'ex'          => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('user_prestamos.index')
            ->with('success', 'Préstamo actualizado correctamente.');
    }

    /* ================================ Helpers ================================== */

    protected function generarAbonosSiFaltan(UserPrestamo $prestamo): void
    {
        if (UserAbono::where('user_prestamo_id', $prestamo->id)->exists()) {
            // Si ya tienes este método en tu clase y funciona, déjalo.
            if (method_exists($this, 'recalcularCantidadesDesdeSaldo')) {
                $this->recalcularCantidadesDesdeSaldo($prestamo);
            }
            return;
        }

        $fechaInicio = Carbon::parse($prestamo->fecha_inicio)->startOfDay();

        $total   = round((float)$prestamo->cantidad + (float)$prestamo->interes_generado, 2);
        $cuotas  = max(1, (int)$prestamo->semanas);

        $cuotaBase = round($total / $cuotas, 2);
        $acumuladoPagos = 0.00;

        for ($i = 1; $i <= $cuotas; $i++) {
            $saldoAntes = round($total - $acumuladoPagos, 2);

            $montoAbono = ($i < $cuotas)
                ? $cuotaBase
                : round($total - $acumuladoPagos, 2);

            $acumuladoPagos = round($acumuladoPagos + $montoAbono, 2);

            $fechaCobro = $fechaInicio->copy()->addWeeks($i - 1)->toDateString();
            $fechaVto   = $fechaInicio->copy()->addWeeks($i)->toDateString();

            UserAbono::create([
                'user_prestamo_id'  => $prestamo->id,
                'id_cliente'        => $prestamo->id_cliente,
                'cantidad'          => $montoAbono,
                'tipo_abono'        => 'pendiente',
                'num_pago'          => $i,
                'mora_generada'     => 0,
                'saldo_restante'    => $saldoAntes,
                'fecha'             => $fechaCobro,
                'fecha_vencimiento' => $fechaVto,
                'status'            => 0,
            ]);
        }
    }

    protected function descontarDeCaja(UserPrestamo $prestamo)
    {
        $caja = Caja::find($prestamo->id_caja);
        if (!$caja) { throw new \RuntimeException('Caja no encontrada.'); }

        $ultimoMov      = $caja->movimientos()->latest('fecha')->first();
        $saldoAnterior  = $ultimoMov ? $ultimoMov->monto_posterior : $caja->saldo_inicial;
        $monto          = (float) $prestamo->cantidad;
        $saldoPosterior = $saldoAnterior - $monto;

        $catGasto = CategoriaGasto::firstOrCreate(
            ['nombre' => 'Préstamos'],
            ['id_usuario' => 1]
        );

        $proveedorId = $this->proveedorResolver->ensureFromCliente($prestamo->id_cliente);

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'tipo_mov'        => 'Egreso',
            'id_cat_gasto'    => $catGasto->id_cat_gasto,
            'id_sub_gasto'    => null,
            'proveedor_id'    => $proveedorId,
            'origen_id'       => $prestamo->id,
            'monto'           => $monto,
            'fecha'           => $prestamo->fecha_inicio ?? now(),
            'descripcion'     => "Desembolso préstamo #{$prestamo->id}",
            'monto_anterior'  => $saldoAnterior,
            'monto_posterior' => $saldoPosterior,
            'id_usuario'      => Auth::id(),
        ]);

        $caja->update(['saldo_final' => $saldoPosterior]);
    }

    protected function ingresarPagoEnCaja(UserPrestamo $prestamo)
    {
        $caja = Caja::find($prestamo->id_caja);
        if (!$caja) { throw new \RuntimeException('Caja no encontrada.'); }

        $ultimoMov      = $caja->movimientos()->latest('fecha')->first();
        $saldoAnterior  = $ultimoMov ? $ultimoMov->monto_posterior : $caja->saldo_inicial;
        $monto          = (float) $prestamo->cantidad + (float) $prestamo->interes_generado;
        $saldoPosterior = $saldoAnterior + $monto;

        $catIng = CategoriaIngreso::firstOrCreate(
            ['nombre' => 'Préstamos'],
            ['id_usuario' => 1]
        );

        $proveedorId = $this->proveedorResolver->ensureFromCliente($prestamo->id_cliente);

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'tipo_mov'        => 'Ingreso',
            'id_cat_ing'      => $catIng->id_cat_ing,
            'id_sub_ing'      => null,
            'proveedor_id'    => $proveedorId,
            'origen_id'       => $prestamo->id,
            'monto'           => $monto,
            'fecha'           => now(),
            'descripcion'     => "Cobro préstamo #{$prestamo->id}",
            'monto_anterior'  => $saldoAnterior,
            'monto_posterior' => $saldoPosterior,
            'id_usuario'      => Auth::id(),
        ]);

        $caja->update(['saldo_final' => $saldoPosterior]);
    }
}
