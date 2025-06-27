<?php

namespace App\Http\Controllers;

use App\Models\Inversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InversionController extends Controller
{
    public function index()
    {
        $inversiones = Inversion::with('usuario')
                         ->where('status', '!=', '0')
                         ->orderByDesc('id')
                         ->paginate(15);

        return view('admininversiones.index', compact('inversiones'));
    }

    public function create()
    {
        return view('admininversiones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'periodo'        => 'required|string|max:255',
            'meses_minimos'  => 'required|integer|min:0',
            'monto_minimo'   => 'required|numeric|min:0',
            'monto_maximo'   => 'required|numeric|gte:monto_minimo',
            'rendimiento'    => 'required|numeric|min:0',
            'fecha'          => 'required|date',
        ]);

        $data['id_usuario'] = Auth::id();
        $data['status']     = '1';

        Inversion::create($data);

        return redirect()
            ->route('inversiones.index')
            ->with('success', 'Inversión creada correctamente.');
    }

    public function show(Inversion $inversion)
    {
        return view('admininversiones.show', compact('inversion'));
    }

    public function edit(Inversion $inversion)
    {
        return view('admininversiones.edit', compact('inversion'));
    }

    public function update(Request $request, Inversion $inversion)
    {
        $data = $request->validate([
            'periodo'        => 'required|string|max:255',
            'meses_minimos'  => 'required|integer|min:0',
            'monto_minimo'   => 'required|numeric|min:0',
            'monto_maximo'   => 'required|numeric|gte:monto_minimo',
            'rendimiento'    => 'required|numeric|min:0',
            'fecha'          => 'required|date',
            'status'         => 'required|in:1,2,3,4',
        ]);

        $data['id_usuario'] = Auth::id();
        $inversion->update($data);

        return redirect()
            ->route('inversiones.index')
            ->with('success', 'Inversión actualizada correctamente.');
    }

    public function destroy(Inversion $inversion)
    {
        // en lugar de borrado físico, marcamos status = 0
        $inversion->update([
            'status'     => '0',
            'id_usuario' => Auth::id(),
        ]);

        return redirect()
            ->route('inversiones.index')
            ->with('success', 'Inversión desactivada correctamente.');
    }
}