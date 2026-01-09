<?php

namespace App\Http\Controllers;

use App\Models\SubcategoriaGasto;
use App\Models\CategoriaGasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubcategoriaGastoController extends Controller
{
    public function __construct()
    {
        // Ver listado / ver detalle
        $this->middleware('permission:subcategoria_gastos.ver')->only(['index','show']);
        // Crear
        $this->middleware('permission:subcategoria_gastos.crear')->only(['create','store']);
        // Editar
        $this->middleware('permission:subcategoria_gastos.editar')->only(['edit','update']);
        // Eliminar
        $this->middleware('permission:subcategoria_gastos.eliminar')->only(['destroy']);
    }

    public function index()
    {
        // Subcategorías son globales (no dependen de sucursal), por eso no se aplica VisibilityScope aquí.
        $subcategorias = SubcategoriaGasto::with(['categoria', 'usuario'])
            ->orderBy('nombre')
            ->paginate(15);

        $isPanel = request()->boolean('panel') || request()->header('X-Panel') === '1';
        if ($isPanel) {
            return view('subcategoria-gastos._panel', compact('subcategorias'));
        }

        return view('subcategoria-gastos.index', compact('subcategorias'));
    }

    public function create()
    {
        // Listado global de categorías (también globales)
        $categorias = CategoriaGasto::orderBy('nombre')->get();
        return view('subcategoria-gastos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $tabla = (new SubcategoriaGasto)->getTable(); // "subcategorias_gasto"

        $data = $request->validate([
            'id_cat_gasto' => ['required','exists:categorias_gasto,id_cat_gasto'],
            'nombre'       => ['required','string','max:255', Rule::unique($tabla, 'nombre')],
        ]);

        $data['id_usuario'] = auth()->user()->id_usuario ?? Auth::id();

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
        $tabla = (new SubcategoriaGasto)->getTable(); // "subcategorias_gasto"

        $data = $request->validate([
            'id_cat_gasto' => ['required','exists:categorias_gasto,id_cat_gasto'],
            'nombre'       => [
                'required','string','max:255',
                Rule::unique($tabla, 'nombre')
                    ->ignore($subcategoriaGasto->id_sub_gasto, 'id_sub_gasto'),
            ],
        ]);

        $data['id_usuario'] = auth()->user()->id_usuario ?? Auth::id();

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
