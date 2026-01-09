<?php
// app/Http/Controllers/SubcategoriaIngresoController.php

namespace App\Http\Controllers;

use App\Models\SubcategoriaIngreso;
use App\Models\CategoriaIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubcategoriaIngresoController extends Controller
{
    public function index()
    {
        $subs = SubcategoriaIngreso::with(['categoria','usuario'])
               ->orderBy('nombre')
               ->paginate(15);

        $isPanel = request()->boolean('panel') || request()->header('X-Panel') === '1';
        if ($isPanel) {
            return view('subcategoria-ingresos._panel', compact('subs'));
        }

        return view('subcategoria-ingresos.index', compact('subs'));
    }

    public function create()
    {
        $cats = CategoriaIngreso::orderBy('nombre')->get();
        return view('subcategoria-ingresos.create', compact('cats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cat_ing' => 'required|exists:categoria_ingresos,id_cat_ing',
            'nombre'     => 'required|string|max:255|unique:subcategorias_ingreso,nombre',
        ]);

        $data['id_usuario'] = Auth::id();
        SubcategoriaIngreso::create($data);

        return redirect()
            ->route('subcategoria-ingresos.index')
            ->with('success','Subcategoría de ingreso creada.');
    }

    public function show(SubcategoriaIngreso $subcategoriaIngreso)
    {
        return view('subcategoria-ingresos.show', ['sub' => $subcategoriaIngreso]);
    }

    public function edit(SubcategoriaIngreso $subcategoriaIngreso)
    {
        $cats = CategoriaIngreso::orderBy('nombre')->get();
        return view('subcategoria-ingresos.edit', compact('subcategoriaIngreso','cats'));
    }

    public function update(Request $request, SubcategoriaIngreso $subcategoriaIngreso)
    {
        $data = $request->validate([
            'id_cat_ing' => 'required|exists:categoria_ingresos,id_cat_ing',
            'nombre'     => 'required|string|max:255|unique:subcategorias_ingreso,nombre,'
                           . $subcategoriaIngreso->id_sub_ing . ',id_sub_ing',
        ]);

        $data['id_usuario'] = Auth::id();
        $subcategoriaIngreso->update($data);

        return redirect()
            ->route('subcategoria-ingresos.index')
            ->with('success','Subcategoría de ingreso actualizada.');
    }

    public function destroy(SubcategoriaIngreso $subcategoriaIngreso)
    {
        $subcategoriaIngreso->delete();

        return redirect()
            ->route('subcategoria-ingresos.index')
            ->with('success','Subcategoría de ingreso eliminada.');
    }
}
