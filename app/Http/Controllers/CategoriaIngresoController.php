<?php
// app/Http/Controllers/CategoriaIngresoController.php

namespace App\Http\Controllers;

use App\Models\CategoriaIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriaIngresoController extends Controller
{
    public function index()
    {
        $categorias = CategoriaIngreso::with('usuario')
                          ->orderBy('nombre')
                          ->paginate(15);

        return view('categoria-ingresos.index', compact('categorias'));
    }

    public function create()
    {
        return view('categoria-ingresos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:categoria_ingresos,nombre',
        ]);

        $data['id_usuario'] = Auth::id();

        CategoriaIngreso::create($data);

        return redirect()
            ->route('categoria-ingresos.index')
            ->with('success', 'Categoría de ingreso creada correctamente.');
    }

    public function show(CategoriaIngreso $categoriaIngreso)
    {
        return view('categoria-ingresos.show', ['categoria' => $categoriaIngreso]);
    }

    public function edit(CategoriaIngreso $categoriaIngreso)
    {
        return view('categoria-ingresos.edit', ['categoria' => $categoriaIngreso]);
    }

    public function update(Request $request, CategoriaIngreso $categoriaIngreso)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:categoria_ingresos,nombre,' 
                        . $categoriaIngreso->id_cat_ing . ',id_cat_ing',
        ]);

        $data['id_usuario'] = Auth::id();

        $categoriaIngreso->update($data);

        return redirect()
            ->route('categoria-ingresos.index')
            ->with('success', 'Categoría de ingreso actualizada correctamente.');
    }

    public function destroy(CategoriaIngreso $categoriaIngreso)
    {
        $categoriaIngreso->delete();

        return redirect()
            ->route('categoria-ingresos.index')
            ->with('success', 'Categoría de ingreso eliminada correctamente.');
    }
}
