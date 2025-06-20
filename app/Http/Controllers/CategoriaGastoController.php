<?php
// app/Http/Controllers/CategoriaGastoController.php

namespace App\Http\Controllers;

use App\Models\CategoriaGasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriaGastoController extends Controller
{
    public function index()
    {
        $categorias = CategoriaGasto::with('usuario')
                          ->orderBy('nombre')
                          ->paginate(15);

        return view('categoria-gastos.index', compact('categorias'));
    }

    public function create()
    {
        return view('categoria-gastos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_gasto,nombre',
        ]);

        $data['id_usuario'] = Auth::id();

        CategoriaGasto::create($data);

        return redirect()
            ->route('categoria-gastos.index')
            ->with('success', 'Categoría de gasto creada correctamente.');
    }

    public function show(CategoriaGasto $categoriaGasto)
    {
        return view('categoria-gastos.show', ['categoria' => $categoriaGasto]);
    }

    public function edit(CategoriaGasto $categoriaGasto)
    {
        return view('categoria-gastos.edit', ['categoria' => $categoriaGasto]);
    }

    public function update(Request $request, CategoriaGasto $categoriaGasto)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_gasto,nombre,' 
                        . $categoriaGasto->id_cat_gasto . ',id_cat_gasto',
        ]);

        $data['id_usuario'] = Auth::id();

        $categoriaGasto->update($data);

        return redirect()
            ->route('categoria-gastos.index')
            ->with('success', 'Categoría de gasto actualizada correctamente.');
    }

    public function destroy(CategoriaGasto $categoriaGasto)
    {
        $categoriaGasto->delete();

        return redirect()
            ->route('categoria-gastos.index')
            ->with('success', 'Categoría de gasto eliminada correctamente.');
    }
}
