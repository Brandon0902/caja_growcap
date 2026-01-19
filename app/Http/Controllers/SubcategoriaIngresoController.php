<?php
// app/Http/Controllers/SubcategoriaIngresoController.php

namespace App\Http\Controllers;

use App\Models\SubcategoriaIngreso;
use App\Models\CategoriaIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SubcategoriaIngresoController extends Controller
{
    public function index()
    {
        $subs = SubcategoriaIngreso::with(['categoria','usuario'])
               ->orderBy('nombre')
               ->paginate(15);

        return view('subcategoria-ingresos.index', compact('subs'));
    }

    public function create()
    {
        $cats = CategoriaIngreso::orderBy('nombre')->get();
        return view('subcategoria-ingresos.create', compact('cats'));
    }

    public function store(Request $request)
    {
        if ($request->missing('id_cat_ing')) {
            $request->merge([
                'id_cat_ing' => $request->input('categoria_id')
                    ?? $request->input('categoriaIngresoId'),
            ]);
        }

        $tabla = (new SubcategoriaIngreso)->getTable();
        $data = $request->validate([
            'id_cat_ing' => ['required', Rule::exists('categorias_ingreso', 'id_cat_ing')],
            'nombre'     => ['required', 'string', 'max:255', Rule::unique($tabla, 'nombre')],
        ]);

        $data['id_usuario'] = auth()->user()->id_usuario ?? Auth::id();

        try {
            SubcategoriaIngreso::create($data);
        } catch (\Throwable $exception) {
            Log::error('Error creating subcategoria ingreso.', [
                'payload' => $request->all(),
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors('No se pudo crear la subcategoría de ingreso.');
        }

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
        if ($request->missing('id_cat_ing')) {
            $request->merge([
                'id_cat_ing' => $request->input('categoria_id')
                    ?? $request->input('categoriaIngresoId'),
            ]);
        }

        $tabla = (new SubcategoriaIngreso)->getTable();
        $data = $request->validate([
            'id_cat_ing' => ['required', Rule::exists('categorias_ingreso', 'id_cat_ing')],
            'nombre'     => [
                'required',
                'string',
                'max:255',
                Rule::unique($tabla, 'nombre')
                    ->ignore($subcategoriaIngreso->id_sub_ing, 'id_sub_ing'),
            ],
        ]);

        $data['id_usuario'] = auth()->user()->id_usuario ?? Auth::id();

        try {
            $subcategoriaIngreso->update($data);
        } catch (\Throwable $exception) {
            Log::error('Error updating subcategoria ingreso.', [
                'subcategoria_ingreso_id' => $subcategoriaIngreso->id_sub_ing,
                'payload' => $request->all(),
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors('No se pudo actualizar la subcategoría de ingreso.');
        }

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