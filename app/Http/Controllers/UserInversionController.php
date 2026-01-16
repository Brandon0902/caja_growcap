<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CategoriaGasto;
use App\Models\CategoriaIngreso;
use App\Models\Cliente;
use App\Models\Inversion;
use App\Models\MovimientoCaja;
use App\Models\UserInversion;
use App\Services\ProveedorResolver;
use App\Services\VisibilityScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// ✅ NUEVOS MAILS
use App\Mail\InversionActivadaAdminMail;
use App\Mail\InversionActivadaClienteMail;

class UserInversionController extends Controller
{
    /** @var ProveedorResolver */
    protected $proveedorResolver;

    public function __construct(ProveedorResolver $proveedorResolver)
    {
        $this->proveedorResolver = $proveedorResolver;

        $readPerms = 'user_inversiones.ver|user_inversiones.ver_asignadas';
        $this->middleware("permission:$readPerms")->only(['index','show','create','store','edit','update']);
    }

    private function getUserSucursalIds(): array
    {
        $u = Auth::user();

        $ids = DB::table('usuario_sucursal_acceso')
            ->where('usuario_id', $u->id_usuario ?? $u->id)
            ->pluck('id_sucursal')
            ->filter()
            ->map(fn($x)=>(int)$x)
            ->all();

        if (!empty($u->id_sucursal)) {
            $ids[] = (int)$u->id_sucursal;
        }

        return array_values(array_unique($ids));
    }

    private function mustLimitBySucursal(): bool
    {
        return Auth::user()->can('user_inversiones.ver_asignadas');
    }

    private function assertVisibleInversion(UserInversion $inv): void
    {
        if (!$this->mustLimitBySucursal()) return;

        $inv->loadMissing('cliente:id,id_sucursal');
        $ids = $this->getUserSucursalIds();

        if (!in_array((int)$inv->cliente->id_sucursal, !empty($ids) ? $ids : [-1], true)) {
            abort(403, 'No tienes permiso para ver/operar esta inversión (otra sucursal).');
        }
    }

    private function assertCajaAllowed(int $idCaja): void
    {
        $ok = VisibilityScope::cajas(
            Caja::query()->where('id_caja', $idCaja),
            Auth::user()
        )->exists();

        if (!$ok) abort(403, 'No puedes usar una caja fuera de tu alcance.');
    }

    private function accrueUntil(UserInversion $inv, ?Carbon $hasta = null, bool $compuesto = true): void
    {
        if ((int)$inv->status !== 2) return;

        $hasta = ($hasta ?: now())->copy()->startOfDay();

        if (empty($inv->ultimo_calculo) || empty($inv->capital_actual)) return;

        $desde = Carbon::parse($inv->ultimo_calculo)->startOfDay();
        $dias  = max(0, $desde->diffInDays($hasta));
        if ($dias === 0) return;

        $tasaAnual = (float)($inv->rendimiento ?? 0) / 100.0;
        $r_dia     = $tasaAnual / 365.0;

        $capital = (float)$inv->capital_actual;
        $ganado  = 0.0;

        if ($compuesto) {
            $capital_nuevo = $capital * pow(1 + $r_dia, $dias);
            $ganado        = $capital_nuevo - $capital;
            $capital       = $capital_nuevo;
        } else {
            $ganado = $capital * $r_dia * $dias;
        }

        $inv->update([
            'capital_actual'       => $capital,
            'rendimiento_generado' => (float)$inv->rendimiento_generado + $ganado,
            'ultimo_calculo'       => $hasta->toDateString(),
        ]);
        $inv->refresh();
    }

    // ✅ Calcula fecha_fin con base en el plan (periodo) + fallback a tiempo
    private function calcularFechaFin(UserInversion $inv): ?string
    {
        $inv->loadMissing('plan');

        if (!$inv->plan) return null;
        if (empty($inv->fecha_inicio)) return null;

        $inicio = Carbon::parse($inv->fecha_inicio)->startOfDay();

        $periodoRaw = (string)($inv->plan->periodo ?? '');
        $periodo    = mb_strtolower(trim($periodoRaw));

        // Extraer número (ej: "2 meses", "6 meses", "1 año", "12")
        preg_match('/(\d+)/', $periodo, $m);
        $n = isset($m[1]) ? (int)$m[1] : 0;

        // Fallback: si periodo viene vacío/raro, usar tiempo (si existe)
        if ($n <= 0 && !empty($inv->tiempo)) {
            $n = (int)$inv->tiempo;
        }

        if ($n <= 0) {
            // Último fallback: 1 mes
            $n = 1;
        }

        // Detectar unidad (años)
        if (
            str_contains($periodo, 'año') || str_contains($periodo, 'anio') ||
            str_contains($periodo, 'años') || str_contains($periodo, 'anios')
        ) {
            return $inicio->copy()->addYears($n)->toDateString();
        }

        // default meses
        return $inicio->copy()->addMonthsNoOverflow($n)->toDateString();
    }

    /** INDEX */
    public function index(Request $request)
    {
        $search     = trim($request->input('search', ''));
        $status     = $request->input('status');
        $desde      = $request->input('desde');
        $hasta      = $request->input('hasta');
        $fin_desde  = $request->input('fin_desde');
        $fin_hasta  = $request->input('fin_hasta');
        $orden      = $request->input('orden', 'fecha_inicio_desc');

        $query = UserInversion::query()
            ->select('user_inversiones.*')
            ->with([
                'cliente:id,nombre,apellido,email,id_sucursal',
                'plan:id,periodo,rendimiento',
            ])
            ->when($this->mustLimitBySucursal(), function ($q) {
                $ids = $this->getUserSucursalIds();
                $q->whereHas('cliente', function ($qc) use ($ids) {
                    $qc->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
                });
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->whereHas('cliente', function ($qc) use ($search) {
                        $qc->where('nombre', 'like', "%{$search}%")
                           ->orWhere('apellido', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhere('user_inversiones.id', $search)
                    ->orWhere('user_inversiones.inversion', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['1','2','3'], true), fn($q) => $q->where('user_inversiones.status', (int)$status))
            ->when($desde, fn($q) => $q->whereDate('user_inversiones.fecha_inicio', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('user_inversiones.fecha_inicio', '<=', $hasta))
            ->when($fin_desde, fn($q) => $q->whereDate('user_inversiones.fecha_fin', '>=', $fin_desde))
            ->when($fin_hasta, fn($q) => $q->whereDate('user_inversiones.fecha_fin', '<=', $fin_hasta));

        $query = match ($orden) {
            'monto_asc'         => $query->orderBy('user_inversiones.inversion', 'asc'),
            'monto_desc'        => $query->orderBy('user_inversiones.inversion', 'desc'),
            'fecha_inicio_asc'  => $query->orderBy('user_inversiones.fecha_inicio', 'asc'),
            'fecha_inicio_desc' => $query->orderBy('user_inversiones.fecha_inicio', 'desc'),
            'fecha_fin_asc'     => $query->orderBy('user_inversiones.fecha_fin', 'asc'),
            'fecha_fin_desc'    => $query->orderBy('user_inversiones.fecha_fin', 'desc'),
            default             => $query->orderBy('user_inversiones.fecha_inicio', 'desc'),
        };

        $inversiones = $query->paginate(15)->withQueryString();

        $statusOptions = [
            null => 'Todos',
            1 => 'Pendiente',
            2 => 'Activa',
            3 => 'Inactiva',
        ];

        return view('adminuserinversiones.index', compact(
            'inversiones', 'search', 'status', 'statusOptions',
            'desde', 'hasta', 'fin_desde', 'fin_hasta', 'orden'
        ));
    }

    public function show(UserInversion $inversion)
    {
        $this->assertVisibleInversion($inversion);

        $inversion->load(['cliente', 'plan', 'caja']);
        $this->accrueUntil($inversion, now(), true);

        $statusOptions = [1 => 'Pendiente', 2 => 'Activa', 3 => 'Inactiva'];

        return view('adminuserinversiones.show', compact('inversion', 'statusOptions'));
    }

    public function create()
    {
        $clientesQ = Cliente::orderBy('nombre')->select('id','nombre','apellido','email','id_sucursal');
        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            $clientesQ->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
        }
        $clientes = $clientesQ->get(['id','nombre','apellido','email']);

        $planes = Inversion::where('status', 1)->get();

        $cajasQuery = VisibilityScope::cajas(Caja::query()->orderBy('nombre'), Auth::user());
        if (Schema::hasColumn('cajas','estado')) $cajasQuery->where('estado','abierta');
        $cajas = $cajasQuery->get();

        return view('adminuserinversiones.create', compact('clientes', 'planes', 'cajas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_cliente'   => 'required|exists:clientes,id',
            'id_activo'    => 'required|exists:inversiones,id',
            'fecha_inicio' => 'required|date',
            'inversion'    => 'required|numeric|min:0.01',
            'id_caja'      => 'required|exists:cajas,id_caja',
        ]);

        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            $ok  = Cliente::whereKey($request->id_cliente)
                ->whereIn('id_sucursal', !empty($ids) ? $ids : [-1])
                ->exists();
            if (!$ok) return back()->withErrors(['id_cliente' => 'Cliente fuera de tu alcance.'])->withInput();
        }

        $this->assertCajaAllowed((int)$request->id_caja);

        $plan = Inversion::findOrFail($request->id_activo);
        $tasa = (float)($plan->rendimiento ?? 0);

        $inv = new UserInversion([
            'id_cliente'           => (int)$request->id_cliente,
            'id_activo'            => (int)$request->id_activo,
            'fecha_solicitud'      => now(),
            'fecha_inicio'         => $request->fecha_inicio,
            'fecha_fin'            => null, // pendiente hasta activación
            'inversion'            => (float)$request->inversion,
            'capital_actual'       => null,
            'ultimo_calculo'       => null,
            'rendimiento'          => $tasa,
            'rendimiento_generado' => 0,
            'status'               => 1,
            'id_usuario'           => Auth::id(),
            'id_caja'              => (int)$request->id_caja,
        ]);

        $inv->save();

        return redirect()->route('user_inversiones.index')->with('success', 'Solicitud de inversión creada correctamente.');
    }

    public function edit(UserInversion $inversion)
    {
        $this->assertVisibleInversion($inversion);

        $this->accrueUntil($inversion, now(), true);

        $inversion->load(['cliente', 'plan', 'caja']);

        $statusOptions = [1 => 'Pendiente', 2 => 'Activa', 3 => 'Inactiva'];

        $cajasQuery = VisibilityScope::cajas(Caja::query()->orderBy('nombre'), Auth::user());
        if (Schema::hasColumn('cajas','estado')) $cajasQuery->where('estado','abierta');
        $cajas = $cajasQuery->get();

        return view('adminuserinversiones.edit', compact('inversion','statusOptions','cajas'));
    }

    public function update(Request $request, UserInversion $inversion)
    {
        $this->assertVisibleInversion($inversion);

        $data = $request->validate([
            'status'   => 'required|in:1,2,3',
            'nota'     => 'nullable|string',
            'id_caja'  => 'nullable|exists:cajas,id_caja',
        ]);

        $newStatus = (int)$data['status'];
        $oldStatus = (int)$inversion->status;

        if (in_array($newStatus, [2, 3], true)) {
            $idCaja = $data['id_caja'] ?? $inversion->id_caja;
            if (empty($idCaja)) {
                return back()->withErrors(['id_caja' => 'Debes seleccionar una caja para este estado.'])->withInput();
            }
            $this->assertCajaAllowed((int)$idCaja);
        }

        $enviarCorreoActivada = false;

        DB::transaction(function () use ($data, $newStatus, $oldStatus, $inversion, &$enviarCorreoActivada) {

            $inversion->update([
                'status'          => $newStatus,
                'nota'            => $data['nota'] ?? null,
                'fecha_respuesta' => now(),
                'id_caja'         => $data['id_caja'] ?? $inversion->id_caja,
            ]);

            $inversion->refresh();

            // ✅ Transición a ACTIVA
            if ($oldStatus !== 2 && $newStatus === 2) {

                if (empty($inversion->fecha_inicio)) {
                    $inversion->update(['fecha_inicio' => now()->toDateString()]);
                    $inversion->refresh();
                }

                // ✅ Guardar fecha_fin en BD según periodo del plan
                $fechaFin = $this->calcularFechaFin($inversion);

                $inversion->update([
                    'capital_actual'       => (float)$inversion->inversion,
                    'ultimo_calculo'       => Carbon::parse($inversion->fecha_inicio)->toDateString(),
                    'rendimiento_generado' => (float)($inversion->rendimiento_generado ?? 0),
                    'fecha_fin'            => $fechaFin, // ✅ YA SE GUARDA (modelo fillable)
                ]);

                $inversion->refresh();

                // ✅ Movimiento de caja (egreso)
                $this->descontarDeCaja($inversion);

                $enviarCorreoActivada = true;
            }

            // ✅ pasar a INACTIVA
            if ($oldStatus !== 3 && $newStatus === 3) {
                $this->accrueUntil($inversion, now(), true);
                $this->ingresarRendimientoEnCaja($inversion);
            }
        });

        if ($enviarCorreoActivada) {
            try {
                $inversion->loadMissing(['cliente', 'plan', 'caja']);

                $adminEmail = trim((string) config('services.admin.email'));
                if ($adminEmail !== '') {
                    Mail::to($adminEmail)
                        ->send(new InversionActivadaAdminMail($inversion));
                }

                if (!empty($inversion->cliente?->email)) {
                    Mail::to($inversion->cliente->email)
                        ->send(new InversionActivadaClienteMail($inversion));
                }
            } catch (\Throwable $e) {
                Log::error('Error enviando correo de inversión activada', [
                    'inversion_id' => $inversion->id ?? null,
                    'cliente_id'   => $inversion->id_cliente ?? null,
                    'ex'           => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('user_inversiones.index')->with('success','Inversión actualizada correctamente.');
    }

    // =========================================================
    // MOVIMIENTOS
    // =========================================================

    protected function descontarDeCaja(UserInversion $inversion): void
    {
        $exists = MovimientoCaja::where('origen_id', $inversion->id)
            ->where('tipo_mov', 'Egreso')
            ->exists();
        if ($exists) return;

        $caja = Caja::where('id_caja', $inversion->id_caja)->lockForUpdate()->first();
        if (!$caja) throw new \RuntimeException('No se encontró la caja asociada a la inversión.');

        $ultimoMov     = $caja->movimientos()->latest('fecha')->first();
        $saldoAnterior = $ultimoMov ? (float)$ultimoMov->monto_posterior : (float)$caja->saldo_inicial;

        $monto          = (float)$inversion->inversion;
        $saldoPosterior = $saldoAnterior - $monto;

        $cat = CategoriaGasto::firstOrCreate(
            ['nombre' => 'Inversiones'],
            ['id_usuario' => 1]
        );

        $proveedorId = $this->proveedorResolver->ensureFromCliente($inversion->id_cliente);

        $fechaMov = !empty($inversion->fecha_inicio)
            ? Carbon::parse($inversion->fecha_inicio)
            : now();

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'tipo_mov'        => 'Egreso',

            'id_cat_ing'      => null,
            'id_sub_ing'      => null,
            'id_cat_gasto'    => $cat->id_cat_gasto ?? null,
            'id_sub_gasto'    => null,

            'proveedor_id'    => $proveedorId,
            'origen_id'       => $inversion->id,

            'monto'           => $monto,
            'fecha'           => $fechaMov,
            'descripcion'     => "Desembolso inversión #{$inversion->id}",
            'monto_anterior'  => $saldoAnterior,
            'monto_posterior' => $saldoPosterior,
            'id_usuario'      => Auth::id(),
        ]);

        $caja->update(['saldo_final' => $saldoPosterior]);
    }

    protected function ingresarRendimientoEnCaja(UserInversion $inversion): void
    {
        $exists = MovimientoCaja::where('origen_id', $inversion->id)
            ->where('tipo_mov', 'Ingreso')
            ->exists();
        if ($exists) return;

        $caja = Caja::where('id_caja', $inversion->id_caja)->lockForUpdate()->first();
        if (!$caja) throw new \RuntimeException('No se encontró la caja asociada a la inversión.');

        $ultimoMov     = $caja->movimientos()->latest('fecha')->first();
        $saldoAnterior = $ultimoMov ? (float)$ultimoMov->monto_posterior : (float)$caja->saldo_inicial;

        $monto = (float)$inversion->inversion + (float)($inversion->rendimiento_generado ?? 0);
        $saldoPosterior = $saldoAnterior + $monto;

        $cat = CategoriaIngreso::firstOrCreate(
            ['nombre' => 'Inversiones'],
            ['id_usuario' => 1]
        );

        $proveedorId = $this->proveedorResolver->ensureFromCliente($inversion->id_cliente);

        $fechaMov = !empty($inversion->getRawOriginal('fecha_fin'))
            ? Carbon::parse($inversion->getRawOriginal('fecha_fin'))
            : now();

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'tipo_mov'        => 'Ingreso',

            'id_cat_ing'      => $cat->id_cat_ing ?? null,
            'id_sub_ing'      => null,
            'id_cat_gasto'    => null,
            'id_sub_gasto'    => null,

            'proveedor_id'    => $proveedorId,
            'origen_id'       => $inversion->id,

            'monto'           => $monto,
            'fecha'           => $fechaMov,
            'descripcion'     => "Cobro inversión #{$inversion->id}",
            'monto_anterior'  => $saldoAnterior,
            'monto_posterior' => $saldoPosterior,
            'id_usuario'      => Auth::id(),
        ]);

        $caja->update(['saldo_final' => $saldoPosterior]);
    }
}
