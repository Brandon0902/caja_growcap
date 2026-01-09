<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\VisibilityScope;

class SucursalController extends Controller
{
    /**
     * Listado de sucursales.
     */
    public function index(Request $request)
    {
        // Gerentes activos (para combos/filtros de la vista, opcional)
        $gerentes = User::where('rol', 'gerente')
            ->where('activo', true)
            ->orderBy('name', 'asc')
            ->get();

        $q = Sucursal::query()->with(['gerente', 'creador']);

        // ⬇️ aplica visibilidad por sucursal para este módulo
        $q = VisibilityScope::sucursales($q, auth()->user());

        // Buscador opcional
        if ($term = $request->get('q')) {
            $q->where(function ($w) use ($term) {
                $w->where('nombre', 'like', "%{$term}%")
                  ->orWhere('direccion', 'like', "%{$term}%")
                  ->orWhere('telefono', 'like', "%{$term}%");
            });
        }

        $sucursales = $q->orderBy('nombre')->paginate(15);

        return view('sucursales.index', compact('sucursales', 'gerentes'));
    }

    /**
     * Formulario de creación.
     */
    public function create()
    {
        $gerentes = User::where('rol', 'gerente')
            ->where('activo', true)
            ->orderBy('name', 'asc')
            ->get();

        return view('sucursales.create', compact('gerentes'));
    }

    /**
     * Guardar nueva sucursal.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'              => 'required|string|max:255|unique:sucursales,nombre',
            'direccion'           => 'required|string|max:500',
            'telefono'            => 'required|string|max:20',
            'gerente_id'          => 'required|integer|exists:usuarios,id_usuario',
            'politica_crediticia' => 'nullable|string',
        ]);

        $data['acceso_activo'] = $request->boolean('acceso_activo');
        $data['id_usuario']    = auth()->user()->id_usuario ?? auth()->id();

        Sucursal::create($data);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal creada correctamente.');
    }

    /**
     * Mostrar una sucursal (respetando visibilidad).
     */
    public function show(string $id)
    {
        $q = Sucursal::query()->with(['gerente', 'creador', 'cajas']);
        $q = VisibilityScope::sucursales($q, auth()->user());

        $sucursal = $q->where('id_sucursal', $id)->firstOrFail();

        return view('sucursales.show', compact('sucursal'));
    }

    /**
     * Formulario de edición (respetando visibilidad).
     */
    public function edit(string $id)
    {
        $q = Sucursal::query();
        $q = VisibilityScope::sucursales($q, auth()->user());

        $sucursal = $q->where('id_sucursal', $id)->firstOrFail();

        $gerentes = User::where('rol', 'gerente')
            ->where('activo', true)
            ->orderBy('name', 'asc')
            ->get();

        return view('sucursales.edit', compact('sucursal', 'gerentes'));
    }

    /**
     * Actualizar sucursal (respetando visibilidad).
     */
    public function update(Request $request, string $id)
    {
        $q = Sucursal::query();
        $q = VisibilityScope::sucursales($q, auth()->user());

        $sucursal = $q->where('id_sucursal', $id)->firstOrFail();

        $data = $request->validate([
            'nombre'              => 'required|string|max:255|unique:sucursales,nombre,' . $sucursal->id_sucursal . ',id_sucursal',
            'direccion'           => 'required|string|max:500',
            'telefono'            => 'required|string|max:20',
            'gerente_id'          => 'required|integer|exists:usuarios,id_usuario',
            'politica_crediticia' => 'nullable|string',
        ]);

        $data['acceso_activo'] = $request->boolean('acceso_activo');
        $data['id_usuario']    = auth()->user()->id_usuario ?? auth()->id();

        $sucursal->update($data);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal actualizada correctamente.');
    }

    /**
     * Desactivar sucursal (soft off) respetando visibilidad.
     */
    public function destroy(string $id)
    {
        $q = Sucursal::query();
        $q = VisibilityScope::sucursales($q, auth()->user());

        $sucursal = $q->where('id_sucursal', $id)->firstOrFail();
        $sucursal->update(['acceso_activo' => false]);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal desactivada correctamente.');
    }

    /**
     * Alternar bandera acceso_activo (respetando visibilidad).
     */
    public function toggle(string $id)
    {
        $q = Sucursal::query();
        $q = VisibilityScope::sucursales($q, auth()->user());

        $sucursal = $q->where('id_sucursal', $id)->firstOrFail();

        $sucursal->update([
            'acceso_activo' => ! (bool) $sucursal->acceso_activo,
        ]);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Estado de la sucursal actualizado correctamente.');
    }
}
