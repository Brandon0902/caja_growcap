<?php

namespace App\Http\Controllers;

use App\Models\Ahorro;
use Illuminate\Http\Request;

class AhorroController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $ahorros = Ahorro::orderByDesc('id')->paginate(15);
        return view('adminahorros.index', compact('ahorros'));
    }

    public function create()
    {
        return view('adminahorros.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:255',
            'meses_minimos' => 'required|integer|min:0',
            'monto_minimo'  => 'required|numeric|min:0',
            'tipo_ahorro'   => 'required|string|max:50', // categoría
            'tasa_vigente'  => 'required|numeric|min:0',
        ]);

        Ahorro::create($data);

        return redirect()
            ->route('ahorros.index')
            ->with('success', 'Ahorro creado correctamente.');
    }

    public function show(Ahorro $ahorro)
    {
        return view('adminahorros.show', compact('ahorro'));
    }

    public function edit(Ahorro $ahorro)
    {
        return view('adminahorros.edit', compact('ahorro'));
    }

    public function update(Request $request, Ahorro $ahorro)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:255',
            'meses_minimos' => 'required|integer|min:0',
            'monto_minimo'  => 'required|numeric|min:0',
            'tipo_ahorro'   => 'required|string|max:50', // categoría
            'tasa_vigente'  => 'required|numeric|min:0',
        ]);

        $ahorro->update($data);

        return redirect()
            ->route('ahorros.index')
            ->with('success', 'Ahorro actualizado correctamente.');
    }

    public function destroy(Ahorro $ahorro)
    {
        $ahorro->delete();

        return redirect()
            ->route('ahorros.index')
            ->with('success', 'Ahorro eliminado correctamente.');
    }
}
