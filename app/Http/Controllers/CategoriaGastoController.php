<?php
// app/Http/Controllers/CategoriaGastoController.php

namespace App\Http\Controllers;

use App\Models\CategoriaGasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriaGastoController extends Controller
{
    public function index(Request $request)
    {
        $q = CategoriaGasto::query()->with('usuario');

        if ($s = trim((string)$request->get('q', ''))) {
            $q->where('nombre', 'like', "%{$s}%");
        }

        $categorias = $q->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        $isPanel = $request->boolean('panel') || $request->header('X-Panel') === '1';
        if ($isPanel) {
            return view('categoria-gastos._panel', compact('categorias'));
        }

        return view('categoria-gastos.index', compact('categorias'));
    }

    public function create()
    {
        // Ya no necesitamos sucursales
        return view('categoria-gastos.create');
    }

    public function store(Request $request)
    {
        $u = Auth::user();

        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_gasto,nombre',
        ]);

        $data['id_usuario'] = $u->id_usuario ?? $u->id;

        CategoriaGasto::create($data);

        return redirect()
            ->route('categoria-gastos.index')
            ->with('success', 'Categoría de gasto creada correctamente.');
    }

    public function show(string $id)
    {
        $categoria = CategoriaGasto::query()
            ->with('usuario')
            ->where('id_cat_gasto', $id)
            ->firstOrFail();

        return view('categoria-gastos.show', ['categoria' => $categoria]);
    }

    public function edit(string $id)
    {
        $categoria = CategoriaGasto::query()
            ->where('id_cat_gasto', $id)
            ->firstOrFail();

        // Ya no necesitamos sucursales
        return view('categoria-gastos.edit', compact('categoria'));
    }

    public function update(Request $request, string $id)
    {
        $u = Auth::user();

        $categoria = CategoriaGasto::query()
            ->where('id_cat_gasto', $id)
            ->firstOrFail();

        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_gasto,nombre,' . $categoria->id_cat_gasto . ',id_cat_gasto',
        ]);

        $data['id_usuario'] = $u->id_usuario ?? $u->id;

        $categoria->update($data);

        return redirect()
            ->route('categoria-gastos.index')
            ->with('success', 'Categoría de gasto actualizada correctamente.');
    }

    public function destroy(string $id)
    {
        $categoria = CategoriaGasto::query()
            ->where('id_cat_gasto', $id)
            ->firstOrFail();

        $categoria->delete();

        return redirect()
            ->route('categoria-gastos.index')
            ->with('success', 'Categoría de gasto eliminada correctamente.');
    }
}
