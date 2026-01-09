<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\UserAbono;
use App\Models\UserPrestamo;
use App\Models\MovimientoCaja;
use App\Models\CategoriaIngreso;
use App\Services\ProveedorResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // ✅ NUEVO

class UserAbonoController extends Controller
{
    /** Categoría de ingreso para movimientos de abonos */
    private const CAT_ABONO_PRESTAMO = 'Abonos de préstamo';

    protected ProveedorResolver $proveedorResolver;

    public function __construct(ProveedorResolver $proveedorResolver)
    {
        $this->proveedorResolver = $proveedorResolver;

        // Permitir entrar con ver O ver_asignadas en el módulo de Abonos
        $readPerms = 'adminuserabonos.ver|adminuserabonos.ver_asignadas';
        $this->middleware("permission:$readPerms")
            ->only(['index','showPrestamos','showAbonos','generalIndex']);

        $this->middleware('permission:adminuserabonos.editar')
            ->only(['edit','update','updateStatus']);
    }

    /* -------------------------- Helpers de alcance -------------------------- */

    /** IDs de sucursales asignadas al usuario (pivote + principal) */
    protected function getUserSucursalIds(): array
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

    /**
     * ¿Debo limitar por sucursal en este módulo?
     * AHORA: limita SIEMPRE que el usuario tenga ver_asignadas,
     * aunque también tenga el permiso "ver".
     */
    protected function mustLimitBySucursal(): bool
    {
        return Auth::user()->can('adminuserabonos.ver_asignadas');
    }

    /* ===================== 1) Listado de clientes ===================== */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $q = Cliente::query()->select('id','nombre','apellido','email','id_sucursal');

        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            $q->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clientes = $q->orderBy('nombre')->paginate(15)->withQueryString();

        return view('adminuserabonos.clientes.index', compact('clientes', 'search'));
    }

    /* ============== 2) Listado de préstamos por cliente ============== */
    public function showPrestamos($clienteId)
    {
        // Asegura visibilidad del cliente por sucursal, según módulo Abonos
        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            $ok = Cliente::whereKey($clienteId)
                ->whereIn('id_sucursal', !empty($ids) ? $ids : [-1])
                ->exists();
            if (!$ok) abort(403, 'No tienes permiso para ver préstamos de este cliente.');
        }

        $cliente = Cliente::findOrFail($clienteId);

        $prestamos = UserPrestamo::where('id_cliente', $clienteId)
            ->orderByDesc('fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        return view('adminuserabonos.prestamos.index', compact('cliente', 'prestamos'));
    }

    /* ============== 3) Abonos de un préstamo específico ============== */
    public function showAbonos(Request $request, $userPrestamoId)
    {
        $prestamo = UserPrestamo::with('cliente:id,id_sucursal,nombre,apellido,email')
                    ->findOrFail($userPrestamoId);

        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            if (!in_array((int)$prestamo->cliente->id_sucursal, $ids, true)) {
                abort(403, 'No tienes permiso para ver abonos de este préstamo.');
            }
        }

        $abonos = UserAbono::where('user_prestamo_id', $prestamo->id)
                    ->orderBy('num_pago')
                    ->paginate(15)
                    ->withQueryString();

        $statusOptions = [
            ''  => 'Todos',
            '0' => 'Pendiente',
            '1' => 'Pagado',
            '2' => 'Vencido',
        ];

        $status = (string) $request->input('status', '');
        $search = trim($request->input('search', ''));
        $desde  = $request->input('desde');
        $hasta  = $request->input('hasta');
        $orden  = $request->input('orden', 'recientes');

        return view('adminuserabonos.abonos.index', compact(
            'prestamo','abonos','statusOptions','status','search','desde','hasta','orden'
        ));
    }

    /* ============== 4) Cambio rápido de status de un abono ============== */
    public function updateStatus(Request $request, $abonoId)
    {
        $data = $request->validate(['status' => 'required|in:0,1,2']);

        $abono = UserAbono::with('userPrestamo.cliente')->findOrFail($abonoId);

        // Chequeo de visibilidad por sucursal en este módulo
        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            if (!in_array((int)$abono->userPrestamo->cliente->id_sucursal, $ids, true)) {
                abort(403, 'No tienes permiso para operar este abono.');
            }
        }

        $old = (int) $abono->status;
        $new = (int) $data['status'];

        DB::transaction(function () use ($abono, $old, $new) {
            $abono->status = $new;
            $abono->save();

            $this->syncMovimientoCajaAbono($abono, $old, $new);
        });

        return back()->with('success', 'Status actualizado.');
    }

    /* ============== 5) Render del modal de edición ===================== */
    public function edit(Request $request, $abonoId)
    {
        $abono = UserAbono::with('userPrestamo.cliente')->findOrFail($abonoId);

        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            if (!in_array((int)$abono->userPrestamo->cliente->id_sucursal, $ids, true)) {
                abort(403, 'No tienes permiso para editar este abono.');
            }
        }

        $isAjax = $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest';
        if (!$isAjax) {
            return redirect()
                ->route('adminuserabonos.abonos.general')
                ->with('openEditId', $abono->id);
        }

        return view('adminuserabonos.abonos.edit_modal', compact('abono'));
    }

    /* ============== 6) Guardar edición completa ======================== */
    public function update(Request $request, $abonoId)
    {
        $abono = UserAbono::with('userPrestamo.cliente')->findOrFail($abonoId);

        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            if (!in_array((int)$abono->userPrestamo->cliente->id_sucursal, $ids, true)) {
                abort(403, 'No tienes permiso para editar este abono.');
            }
        }

        $data = $request->validate([
            'tipo_abono'        => 'nullable|string|max:50',
            'fecha_vencimiento' => 'nullable|date',
            'num_pago'          => 'nullable|integer',
            'cantidad'          => 'nullable|numeric',
            'saldo_restante'    => 'nullable|numeric',
            'mora_generada'     => 'nullable|numeric',
            'fecha'             => 'nullable|date',
            'status'            => 'required|in:0,1,2',
        ]);

        $old = (int) $abono->status;

        DB::transaction(function () use ($abono, $data, $old) {
            $abono->update($data);
            $abono->refresh();

            $this->syncMovimientoCajaAbono($abono, $old, (int)$abono->status, true);
        });

        return back()->with('success', 'Abono actualizado correctamente.');
    }

    /* ============== 7) Listado general de abonos ======================= */
    public function generalIndex(Request $request)
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status');
        $desde  = $request->input('desde');
        $hasta  = $request->input('hasta');
        $orden  = $request->input('orden', 'recientes');

        $abonos = UserAbono::query()
            ->with([
                'userPrestamo:id,id_cliente',
                'userPrestamo.cliente:id,nombre,apellido,email,id_sucursal',
            ]);

        // Limitar por sucursal si el módulo Abonos está en ver_asignadas
        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            $abonos->whereHas('userPrestamo.cliente', function ($qc) use ($ids) {
                $qc->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
            });
        }

        // Filtros
        $abonos->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('id', $search)
                       ->orWhere('user_prestamo_id', $search)
                       ->orWhere('cantidad', 'like', "%{$search}%")
                       ->orWhereHas('userPrestamo.cliente', function ($qc) use ($search) {
                           $qc->where('nombre',  'like', "%{$search}%")
                              ->orWhere('apellido','like', "%{$search}%")
                              ->orWhere('email',   'like', "%{$search}%");
                       });
                });
            })
            ->when(in_array($status, ['0','1','2'], true), fn($q) => $q->where('status', (int)$status))
            ->when($desde, fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha', '<=', $hasta));

        $abonos = match ($orden) {
            'antiguos'   => $abonos->orderBy('fecha', 'asc'),
            'monto_asc'  => $abonos->orderBy('cantidad', 'asc'),
            'monto_desc' => $abonos->orderBy('cantidad', 'desc'),
            default      => $abonos->orderBy('fecha', 'desc'),
        };

        $abonos = $abonos->paginate(15)->withQueryString();

        $statusOptions = [
            ''  => 'Todos',
            '0' => 'Pendiente',
            '1' => 'Pagado',
            '2' => 'Vencido',
        ];

        return view('adminuserabonos.abonos.general_index', compact(
            'abonos', 'search', 'status', 'statusOptions', 'desde', 'hasta', 'orden'
        ));
    }

    /* ===================== Helpers de movimientos + comisiones ====================== */

    /**
     * ✅ NUEVO: sincroniza la comisión ligada a este abono si existe en empleado_comisiones.
     * - Si abono queda PAGADO (1) => comisión PAGADA (1) y fecha_pago=now()
     * - Si abono sale de PAGADO:
     *      - a PENDIENTE (0) => comisión vuelve a PENDIENTE (0)
     *      - a VENCIDO (2)   => comisión ANULADA (2)
     */
    protected function syncComisionAbono(UserAbono $abono, int $oldStatus, int $newStatus): void
    {
        // Si no existe la tabla, no hacemos nada (por seguridad)
        if (!Schema::hasTable('empleado_comisiones')) return;

        $q = DB::table('empleado_comisiones')->where('user_abono_id', (int) $abono->id);
        if (!$q->exists()) return; // este abono no es comisionable

        $hasFechaPago = Schema::hasColumn('empleado_comisiones', 'fecha_pago');
        $hasUpdatedAt = Schema::hasColumn('empleado_comisiones', 'updated_at');

        // Pagado => pagar comisión
        if ($newStatus === 1) {
            $upd = ['status' => 1];
            if ($hasFechaPago) $upd['fecha_pago'] = now();
            if ($hasUpdatedAt) $upd['updated_at'] = now();
            $q->update($upd);
            return;
        }

        // Si antes era pagado y ya no lo es => revertir / anular
        if ($oldStatus === 1 && $newStatus !== 1) {
            $nuevoStatusCom = ($newStatus === 2) ? 2 : 0;

            $upd = ['status' => $nuevoStatusCom];
            if ($hasFechaPago) $upd['fecha_pago'] = null;
            if ($hasUpdatedAt) $upd['updated_at'] = now();

            $q->update($upd);
        }
    }

    protected function syncMovimientoCajaAbono(UserAbono $abono, int $oldStatus, int $newStatus, bool $forceUpdate = false): void
    {
        if ($newStatus === 1) {
            $this->registrarAbonoEnCaja($abono, $forceUpdate);

            // ✅ NUEVO: liquidar comisión cuando se marca pagado
            $this->syncComisionAbono($abono, $oldStatus, $newStatus);
            return;
        }

        if ($oldStatus === 1 && in_array($newStatus, [0, 2], true)) {
            $this->eliminarMovimientoDeAbono($abono);

            // ✅ NUEVO: revertir/anular comisión si se quita el pagado
            $this->syncComisionAbono($abono, $oldStatus, $newStatus);
        }
    }

    protected function registrarAbonoEnCaja(UserAbono $abono, bool $forceUpdate = false): void
    {
        $catIng = CategoriaIngreso::firstOrCreate(
            ['nombre' => self::CAT_ABONO_PRESTAMO],
            ['id_usuario' => 1]
        );

        $cajaId = $this->resolveCajaId($abono);

        $movQuery = MovimientoCaja::where('tipo_mov', 'Ingreso')
            ->where('id_cat_ing', $catIng->id_cat_ing)
            ->where('origen_id', $abono->id);

        $existe = $movQuery->exists();
        if ($existe && !$forceUpdate) return;

        $caja = Caja::findOrFail($cajaId);

        $last   = $caja->movimientos()->orderByDesc('fecha')->orderByDesc('id_mov')->first();
        $antes  = $last ? $last->monto_posterior : $caja->saldo_inicial;
        $monto  = (float) $abono->cantidad + (float) ($abono->mora_generada ?? 0);
        $fecha  = $abono->fecha ?: now();
        $desc   = "Abono préstamo #{$abono->user_prestamo_id} (abono #{$abono->id})";

        if ($existe) {
            $movQuery->first()->delete();
            $last  = $caja->movimientos()->orderByDesc('fecha')->orderByDesc('id_mov')->first();
            $antes = $last ? $last->monto_posterior : $caja->saldo_inicial;
        }

        $despues = $antes + $monto;

        $prestamo     = $abono->relationLoaded('userPrestamo') ? $abono->userPrestamo : $abono->userPrestamo()->first();
        $proveedorId  = $this->proveedorResolver->ensureFromCliente($prestamo->id_cliente);

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'tipo_mov'        => 'Ingreso',
            'id_cat_ing'      => $catIng->id_cat_ing,
            'id_sub_ing'      => null,
            'id_cat_gasto'    => null,
            'id_sub_gasto'    => null,
            'proveedor_id'    => $proveedorId,
            'origen_id'       => $abono->id,
            'monto'           => $monto,
            'fecha'           => $fecha,
            'descripcion'     => $desc,
            'monto_anterior'  => $antes,
            'monto_posterior' => $despues,
            'id_usuario'      => Auth::id(),
        ]);

        $caja->update(['saldo_final' => $despues]);
    }

    protected function eliminarMovimientoDeAbono(UserAbono $abono): void
    {
        $catIng = CategoriaIngreso::where('nombre', self::CAT_ABONO_PRESTAMO)->first();
        if (!$catIng) return;

        $mov = MovimientoCaja::where('tipo_mov', 'Ingreso')
            ->where('id_cat_ing', $catIng->id_cat_ing)
            ->where('origen_id', $abono->id)
            ->first();

        if (!$mov) return;

        $caja = Caja::find($mov->id_caja);
        $mov->delete();

        if ($caja) {
            $ultimo = $caja->movimientos()->orderByDesc('fecha')->orderByDesc('id_mov')->first();
            $nuevoSaldoFinal = $ultimo ? $ultimo->monto_posterior : $caja->saldo_inicial;
            $caja->update(['saldo_final' => $nuevoSaldoFinal]);
        }
    }

    protected function resolveCajaId(UserAbono $abono): int
    {
        $prestamo = $abono->relationLoaded('userPrestamo') ? $abono->userPrestamo : $abono->userPrestamo()->first();
        if (!empty($prestamo->id_caja)) {
            return (int) $prestamo->id_caja;
        }
        return (int) (
            Caja::where('estado', 'abierta')->value('id_caja') ?? Caja::value('id_caja')
        );
    }
}
