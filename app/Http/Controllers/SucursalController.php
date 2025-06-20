<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    /**
     * Display a listing of the sucursales.
     */
    public function index(Request $request)
    {
        // Lista de gerentes activos, para el filtro
        $gerentes = User::where('rol', 'gerente')
                        ->where('activo', true)
                        ->orderBy('name', 'asc')
                        ->get();

        // Traer todas las sucursales con paginación, incluyendo datos de gerente y creador
        $sucursales = Sucursal::with(['gerente', 'creador'])
                            ->orderBy('nombre')
                            ->paginate(15);

        return view('sucursales.index', compact('sucursales', 'gerentes'));
    }

    /**
     * Show the form for creating a new sucursal.
     */
    public function create()
    {
        // Listar usuarios con rol 'gerente' y activo = true para asignarlos
        $gerentes = User::where('rol', 'gerente')
                        ->where('activo', true)
                        ->orderBy('name', 'asc')    // <-- cambio aquí
                        ->get();

        return view('sucursales.create', compact('gerentes'));
    }

    /**
     * Store a newly created sucursal in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'              => 'required|string|max:255|unique:sucursales,nombre',
            'direccion'           => 'required|string|max:500',
            'telefono'            => 'required|string|max:20',
            'gerente_id'          => 'required|integer|exists:usuarios,id_usuario',
            'politica_crediticia' => 'nullable|string',
            'acceso_activo'       => 'required|boolean',
        ]);

        $data['id_usuario'] = auth()->user()->id_usuario;

        Sucursal::create($data);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal creada correctamente.');
    }

    /**
     * Display the specified sucursal.
     */
    public function show(string $id)
    {
        $sucursal = Sucursal::with(['gerente', 'creador', 'cajas'])
                             ->findOrFail($id);

        return view('sucursales.show', compact('sucursal'));
    }

    /**
     * Show the form for editing the specified sucursal.
     */
    public function edit(string $id)
    {
        $sucursal = Sucursal::findOrFail($id);

        $gerentes = User::where('rol', 'gerente')
                        ->where('activo', true)
                        ->orderBy('name', 'asc')    // <-- y aquí
                        ->get();

        return view('sucursales.edit', compact('sucursal', 'gerentes'));
    }

    /**
     * Update the specified sucursal in storage.
     */
    public function update(Request $request, string $id)
    {
        $sucursal = Sucursal::findOrFail($id);

        $data = $request->validate([
            'nombre'              => 'required|string|max:255|unique:sucursales,nombre,' 
                                     . $sucursal->id_sucursal . ',id_sucursal',
            'direccion'           => 'required|string|max:500',
            'telefono'            => 'required|string|max:20',
            'gerente_id'          => 'required|integer|exists:usuarios,id_usuario',
            'politica_crediticia' => 'nullable|string',
            'acceso_activo'       => 'required|boolean',
        ]);

        $data['id_usuario'] = auth()->user()->id_usuario;
        $sucursal->update($data);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal actualizada correctamente.');
    }

    /**
     * Remove (deactivate) the specified sucursal from storage.
     */
    public function destroy(string $id)
    {
        $sucursal = Sucursal::findOrFail($id);
        $sucursal->update(['acceso_activo' => false]);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal desactivada correctamente.');
    }

        public function toggle(Sucursal $sucursal)
    {
        $sucursal->update([
            'acceso_activo' => ! $sucursal->acceso_activo,
        ]);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Estado de la sucursal actualizado correctamente.');
    }
}
