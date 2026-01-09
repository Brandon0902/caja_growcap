<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $empresas = Empresa::orderByDesc('id')->paginate(15);
        $isPanel = $request->boolean('panel') || $request->header('X-Panel') === '1';
        if ($isPanel) {
            return view('adminempresas._panel', compact('empresas'));
        }

        return view('adminempresas.index', compact('empresas'));
    }

    public function create()
    {
        return view('adminempresas.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:255',
            'rfc'            => 'nullable|string|max:20',
            'direccion'      => 'nullable|string|max:500',
            'ciudad'         => 'nullable|string|max:100',
            'estado'         => 'nullable|string|max:100',
            'codigo_postal'  => 'nullable|string|max:20',
            'pais'           => 'required|string|max:100',
            'telefono'       => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:150',
            'estatus'        => 'required|boolean',
        ]);

        Empresa::create($data);

        return redirect()
            ->route('empresas.index')
            ->with('success', 'Empresa creada correctamente.');
    }

    public function show(Empresa $empresa)
    {
        return view('adminempresas.show', compact('empresa'));
    }

    public function edit(Empresa $empresa)
    {
        return view('adminempresas.edit', compact('empresa'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:255',
            'rfc'            => 'nullable|string|max:20',
            'direccion'      => 'nullable|string|max:500',
            'ciudad'         => 'nullable|string|max:100',
            'estado'         => 'nullable|string|max:100',
            'codigo_postal'  => 'nullable|string|max:20',
            'pais'           => 'required|string|max:100',
            'telefono'       => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:150',
            'estatus'        => 'required|boolean',
        ]);

        $empresa->update($data);

        return redirect()
            ->route('empresas.index')
            ->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Empresa $empresa)
    {
        $empresa->delete();  // o cambiar estatus si prefieres soft-delete

        return redirect()
            ->route('empresas.index')
            ->with('success', 'Empresa eliminada correctamente.');
    }
}
