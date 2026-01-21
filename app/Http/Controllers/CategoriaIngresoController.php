<?php
// app/Http/Controllers/CategoriaIngresoController.php

namespace App\Http\Controllers;

use App\Models\CategoriaIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoriaIngresoController extends Controller
{
    public function index(Request $request)
    {
        $q = CategoriaIngreso::query()->with('usuario');

        if ($s = trim((string)$request->get('q', ''))) {
            $q->where('nombre', 'like', "%{$s}%");
        }

        $categorias = $q->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        $isPanel = $request->boolean('panel') || $request->header('X-Panel') === '1';
        if ($isPanel) {
            return view('categoria-ingresos._panel', compact('categorias'));
        }

        return view('categoria-ingresos.index', compact('categorias'));
    }

    public function create()
    {
        // Ya no necesitamos sucursales
        return view('categoria-ingresos.create');
    }

    public function store(Request $request)
    {
        $u = Auth::user();
        $tabla = (new CategoriaIngreso)->getTable(); // "categorias_ingreso"

        $data = $request->validate([
            'nombre' => ['required','string','max:255', Rule::unique($tabla, 'nombre')],
        ]);

        $data['id_usuario'] = $u->id_usuario ?? $u->id;

        CategoriaIngreso::create($data);

        return redirect()
            ->route('categoria-ingresos.index')
            ->with('success', 'Categoría de ingreso creada correctamente.');
    }

    public function show(string $id)
    {
        $categoria = CategoriaIngreso::query()
            ->with('usuario')
            ->whereKey($id)
            ->firstOrFail();

        return view('categoria-ingresos.show', ['categoria' => $categoria]);
    }

    public function edit(string $id)
    {
        $categoria = CategoriaIngreso::query()
            ->whereKey($id)
            ->firstOrFail();

        // Ya no necesitamos sucursales
        return view('categoria-ingresos.edit', compact('categoria'));
    }

    public function update(Request $request, string $id)
    {
        $u = Auth::user();

        $categoria = CategoriaIngreso::query()
            ->whereKey($id)
            ->firstOrFail();

        $tabla = (new CategoriaIngreso)->getTable();

        $data = $request->validate([
            'nombre' => [
                'required','string','max:255',
                Rule::unique($tabla, 'nombre')->ignore($categoria->getKey(), $categoria->getKeyName()),
            ],
        ]);

        $data['id_usuario'] = $u->id_usuario ?? $u->id;

        $categoria->update($data);

        return redirect()
            ->route('categoria-ingresos.index')
            ->with('success', 'Categoría de ingreso actualizada correctamente.');
    }

    public function destroy(string $id)
    {
        $categoria = CategoriaIngreso::query()
            ->whereKey($id)
            ->firstOrFail();

        $categoria->delete();

        return redirect()
            ->route('categoria-ingresos.index')
            ->with('success', 'Categoría de ingreso eliminada correctamente.');
    }
}
