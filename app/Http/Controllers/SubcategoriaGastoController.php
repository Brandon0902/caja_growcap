<?php
// app/Http/Controllers/SubcategoriaGastoController.php

namespace App\Http\Controllers;

use App\Models\SubcategoriaGasto;
use App\Models\CategoriaGasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubcategoriaGastoController extends Controller
{
    public function index()
    {
        $subcategorias = SubcategoriaGasto::with(['categoria', 'usuario'])
                              ->orderBy('nombre')
                              ->paginate(15);

        return view('subcategoria-gastos.index', compact('subcategorias'));
    }

    public function create()
    {
        $categorias = CategoriaGasto::orderBy('nombre')->get();
        return view('subcategoria-gastos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cat_gasto' => 'required|exists:categorias_gasto,id_cat_gasto',
            'nombre'       => 'required|string|max:255|unique:subcategorias_gasto,nombre',
        ]);

        $data['id_usuario'] = Auth::id();

        SubcategoriaGasto::create($data);

        return redirect()
            ->route('subcategoria-gastos.index')
            ->with('success', 'Subcategoría de gasto creada correctamente.');
    }

    public function show(SubcategoriaGasto $subcategoriaGasto)
    {
        return view('subcategoria-gastos.show', ['subcategoria' => $subcategoriaGasto]);
    }

    public function edit(SubcategoriaGasto $subcategoriaGasto)
    {
        $categorias = CategoriaGasto::orderBy('nombre')->get();
        return view('subcategoria-gastos.edit', compact('subcategoriaGasto', 'categorias'));
    }

    public function update(Request $request, SubcategoriaGasto $subcategoriaGasto)
    {
        $data = $request->validate([
            'id_cat_gasto' => 'required|exists:categorias_gasto,id_cat_gasto',
            'nombre'       => 'required|string|max:255|unique:subcategorias_gasto,nombre,' 
                              . $subcategoriaGasto->id_sub_gasto . ',id_sub_gasto',
        ]);

        $data['id_usuario'] = Auth::id();

        $subcategoriaGasto->update($data);

        return redirect()
            ->route('subcategoria-gastos.index')
            ->with('success', 'Subcategoría de gasto actualizada correctamente.');
    }

    public function destroy(SubcategoriaGasto $subcategoriaGasto)
    {
        $subcategoriaGasto->delete();

        return redirect()
            ->route('subcategoria-gastos.index')
            ->with('success', 'Subcategoría de gasto eliminada correctamente.');
    }
}
