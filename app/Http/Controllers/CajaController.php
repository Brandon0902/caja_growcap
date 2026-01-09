<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\VisibilityScope;

class CajaController extends Controller
{
    /**
     * Listado de cajas (visibilidad por sucursal) + saldo.
     */
    public function index(Request $request)
    {
        $u = Auth::user();

        // 1) Lista de sucursales que este usuario puede ver (para el combo)
        $sucursales = VisibilityScope::sucursales(Sucursal::query(), $u)
            ->orderBy('nombre')
            ->get();

        $allowedSucursalIds = $sucursales->pluck('id_sucursal')->map(fn($v) => (int)$v)->all();

        // 2) Query base de cajas (con visibilidad)
        $q = Caja::query()->with(['sucursal', 'responsable', 'creador']);
        $q = VisibilityScope::cajas($q, $u);

        // 3) Filtro por sucursal (AHORA SÍ)
        $sid = $request->filled('sucursal_id') ? (int) $request->get('sucursal_id') : null;
        if ($sid) {
            // Evita que filtren una sucursal fuera del alcance (si el usuario está limitado)
            if (!in_array($sid, $allowedSucursalIds, true)) {
                // Si tiene cajas.ver (sin límite) pero no sale en $sucursales por alguna razón,
                // igual no lo dejamos filtrar a algo "desconocido".
                return back()->withErrors([
                    'sucursal_id' => 'No tienes acceso a esa sucursal.',
                ]);
            }

            $q->where('id_sucursal', $sid);
        }

        // 4) Búsqueda
        if ($s = trim($request->get('search', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('nombre', 'like', "%{$s}%");
            });
        }

        // 5) Sumas para saldo
        $q->withSum(['movimientos as ingresos_sum' => function ($qq) {
                $qq->where('tipo_mov', 'Ingreso');
            }], 'monto')
          ->withSum(['movimientos as egresos_sum' => function ($qq) {
                $qq->where('tipo_mov', 'Egreso');
            }], 'monto');

        $cajas = $q->orderBy('fecha_apertura', 'desc')
            ->paginate(15)
            ->appends($request->only('search', 'sucursal_id'));

        // saldo_actual calculado
        $cajas->getCollection()->transform(function ($c) {
            $ing = (float) ($c->ingresos_sum ?? 0);
            $egr = (float) ($c->egresos_sum ?? 0);
            $c->saldo_actual = ($c->estado === 'cerrada' && !is_null($c->saldo_final))
                ? (float) $c->saldo_final
                : (float) $c->saldo_inicial + $ing - $egr;
            return $c;
        });

        return view('cajas.index', compact('cajas', 'sucursales'));
    }

    public function show(string $id)
    {
        $u = Auth::user();

        $q = Caja::query()->with(['sucursal','responsable','creador','movimientos']);
        $q = VisibilityScope::cajas($q, $u);

        $caja = $q->where('id_caja', $id)->firstOrFail();

        return view('cajas.show', compact('caja'));
    }

    public function create()
    {
        $u = Auth::user();

        $sucursales = VisibilityScope::sucursales(Sucursal::query(), $u)
            ->orderBy('nombre')
            ->get();

        $allowedSucursalIds = $sucursales->pluck('id_sucursal')->all();

        $rolesElegibles = ['cobrador', 'gerente', 'admin'];

        $usuarios = User::where('activo', true)
            ->whereIn('rol', $rolesElegibles)
            ->whereIn('id_sucursal', $allowedSucursalIds)
            ->with('sucursal')
            ->orderBy('name')
            ->get();

        return view('cajas.create', compact('sucursales', 'usuarios'));
    }

    public function store(Request $request)
    {
        $u = Auth::user();

        $data = $request->validate([
            'id_sucursal'    => 'required|exists:sucursales,id_sucursal',
            'nombre'         => 'required|string|max:255',
            'responsable_id' => 'required|exists:usuarios,id_usuario',
            'fecha_apertura' => 'required|date',
            'saldo_inicial'  => 'required|numeric',
            'fecha_cierre'   => 'nullable|date',
            'saldo_final'    => 'nullable|numeric',
            'estado'         => 'required|in:abierta,cerrada',
            'acceso_activo'  => 'boolean',
        ]);

        $allowedSucursalIds = VisibilityScope::sucursales(Sucursal::query(), $u)
            ->pluck('id_sucursal');

        if (! $allowedSucursalIds->contains((int) $data['id_sucursal'])) {
            return back()->withErrors([
                'id_sucursal' => 'No puedes crear cajas en una sucursal fuera de tu alcance.',
            ])->withInput();
        }

        $responsable = User::where('id_usuario', $data['responsable_id'])->first();
        if (! $responsable || ! $allowedSucursalIds->contains((int) $responsable->id_sucursal)) {
            return back()->withErrors([
                'responsable_id' => 'El responsable debe pertenecer a una sucursal dentro de tu alcance.',
            ])->withInput();
        }

        $data['id_usuario'] = $u->id_usuario ?? $u->id;
        $data['acceso_activo'] = $request->boolean('acceso_activo');

        Caja::create($data);

        return redirect()->route('cajas.index')->with('success', 'Caja creada exitosamente.');
    }

    public function edit(string $id)
    {
        $u = Auth::user();

        $q = Caja::query()->with('sucursal');
        $q = VisibilityScope::cajas($q, $u);

        $caja = $q->where('id_caja', $id)->firstOrFail();

        $sucursales = VisibilityScope::sucursales(Sucursal::query(), $u)
            ->orderBy('nombre')
            ->get();

        $allowedSucursalIds = $sucursales->pluck('id_sucursal')->all();

        $rolesElegibles = ['cobrador', 'gerente', 'admin'];
        $usuarios = User::where('activo', true)
            ->whereIn('rol', $rolesElegibles)
            ->whereIn('id_sucursal', $allowedSucursalIds)
            ->with('sucursal')
            ->orderBy('name')
            ->get();

        return view('cajas.edit', compact('caja', 'sucursales', 'usuarios'));
    }

    public function update(Request $request, string $id)
    {
        $u = Auth::user();

        $q = Caja::query();
        $q = VisibilityScope::cajas($q, $u);
        $caja = $q->where('id_caja', $id)->firstOrFail();

        $data = $request->validate([
            'id_sucursal'    => 'required|exists:sucursales,id_sucursal',
            'nombre'         => 'required|string|max:255',
            'responsable_id' => 'required|exists:usuarios,id_usuario',
            'fecha_apertura' => 'required|date',
            'saldo_inicial'  => 'required|numeric',
            'estado'         => 'required|in:abierta,cerrada',
            'acceso_activo'  => 'boolean',
            'fecha_cierre'   => 'nullable|date',
            'saldo_final'    => 'nullable|numeric',
        ]);

        $allowedSucursalIds = VisibilityScope::sucursales(Sucursal::query(), $u)
            ->pluck('id_sucursal');

        if (! $allowedSucursalIds->contains((int) $data['id_sucursal'])) {
            return back()->withErrors([
                'id_sucursal' => 'No puedes mover la caja a una sucursal fuera de tu alcance.',
            ])->withInput();
        }

        $responsable = User::where('id_usuario', $data['responsable_id'])->first();
        if (! $responsable || ! $allowedSucursalIds->contains((int) $responsable->id_sucursal)) {
            return back()->withErrors([
                'responsable_id' => 'El responsable debe pertenecer a una sucursal dentro de tu alcance.',
            ])->withInput();
        }

        $data['id_usuario'] = $u->id_usuario ?? $u->id;
        $data['acceso_activo'] = $request->boolean('acceso_activo');

        $caja->update($data);

        return redirect()->route('cajas.index')->with('success', 'Caja actualizada exitosamente.');
    }

    public function destroy(string $id)
    {
        $u = Auth::user();

        $q = Caja::query();
        $q = VisibilityScope::cajas($q, $u);

        $caja = $q->where('id_caja', $id)->firstOrFail();
        $caja->delete();

        return redirect()->route('cajas.index')->with('success', 'Caja eliminada exitosamente.');
    }

    public function toggle(string $id)
    {
        $u = Auth::user();

        $q = Caja::query();
        $q = VisibilityScope::cajas($q, $u);

        $caja = $q->where('id_caja', $id)->firstOrFail();

        $caja->update([
            'acceso_activo' => ! (bool) $caja->acceso_activo,
        ]);

        return redirect()->route('cajas.index')->with('success', 'Estado de la caja actualizado correctamente.');
    }
}
