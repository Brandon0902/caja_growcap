<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CajaController extends Controller
{
    /**
     * Display a listing of the cajas.
     */
    public function index()
    {
        $cajas = Caja::with(['sucursal', 'responsable', 'creador'])
                    ->orderBy('fecha_apertura', 'desc')
                    ->paginate(15);

        return view('cajas.index', compact('cajas'));
    }

    public function show($id)
    {
        $caja = Caja::with(['sucursal','responsable','creador','movimientos'])
                    ->findOrFail($id);

        return view('cajas.show', compact('caja'));
    }

    /**
     * Show the form for creating a new caja.
     */
    // En App\Http\Controllers\CajaController.php

    public function create()
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        // Sólo cobradores activos
        $usuarios = User::where('activo', true)
                        ->where('rol', 'cobrador')
                        ->orderBy('name')
                        ->get();

        return view('cajas.create', compact('sucursales', 'usuarios'));
    }


    /**
     * Store a newly created caja in storage.
     */
    public function store(Request $request)
    {
       $data = $request->validate([
            'id_sucursal'    => 'required|exists:sucursales,id_sucursal',
            'nombre'         => 'required|string',
            'responsable_id' => 'required|exists:usuarios,id_usuario',
            'fecha_apertura' => 'required|date',
            'saldo_inicial'  => 'required|numeric',
            'fecha_cierre'   => 'nullable|date',
            'saldo_final'    => 'nullable|numeric',
            'estado'         => 'required|in:abierta,cerrada',
            'acceso_activo'  => 'boolean',
        ]);

        $data['id_usuario'] = Auth::id();

        Caja::create($data);

        return redirect()->route('cajas.index')
                         ->with('success', 'Caja creada exitosamente.');
    }

    /**
     * Show the form for editing the specified caja.
     */
    public function edit(string $id)
    {
        $caja = Caja::findOrFail($id);

        $sucursales = Sucursal::orderBy('nombre')->get();

        // Sólo cobradores activos, ordenados por name (no por nombre)
        $usuarios = User::where('activo', true)
                        ->orderBy('name')
                        ->get();

        return view('cajas.edit', compact('caja', 'sucursales', 'usuarios'));
    }

    /**
     * Update the specified caja in storage.
     */
    public function update(Request $request, string $id)
    {
        $caja = Caja::findOrFail($id);

       $data = $request->validate([
            'id_sucursal'    => 'required|exists:sucursales,id_sucursal',
            'nombre'         => 'required|string',
            'responsable_id' => 'required|exists:usuarios,id_usuario',
            'fecha_apertura' => 'required|date',
            'saldo_inicial'  => 'required|numeric',
            'estado'         => 'required|in:abierta,cerrada',
            'acceso_activo'  => 'boolean',
        ]);

        $data['id_usuario'] = Auth::id();

        $caja->update($data);

        return redirect()->route('cajas.index')
                         ->with('success', 'Caja actualizada exitosamente.');
    }

    /**
     * Remove the specified caja from storage.
     */
    public function destroy(string $id)
    {
        $caja = Caja::findOrFail($id);
        $caja->delete();

        return redirect()->route('cajas.index')
                         ->with('success', 'Caja eliminada exitosamente.');
    }

    public function toggle(Caja $caja)
    {
        $caja->update([
            'acceso_activo' => ! $caja->acceso_activo,
        ]);

        return redirect()
            ->route('cajas.index')
            ->with('success', 'Estado de la caja actualizado correctamente.');
    }
}
