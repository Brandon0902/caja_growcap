<?php

namespace App\Http\Controllers;

use App\Models\ConfigMora;
use Illuminate\Http\Request;

class ConfigMoraController extends Controller
{
    public function index()
    {
        $configMoras = ConfigMora::orderByDesc('id')->paginate(15);
        return view('config_mora.index', compact('configMoras'));
    }

    public function create()
    {
        return view('config_mora.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cargo_fijo'      => 'nullable|numeric|min:0',
            'porcentaje_mora' => 'nullable|numeric|min:0|max:100',
            'periodo_gracia'  => 'nullable|integer|min:0',
        ]);

        ConfigMora::create($data);

        return redirect()
            ->route('config_mora.index')
            ->with('success', 'Configuración de mora creada correctamente.');
    }

    public function show(ConfigMora $config_mora)
    {
        return view('config_mora.show', compact('config_mora'));
    }

    public function edit(ConfigMora $config_mora)
    {
        return view('config_mora.edit', compact('config_mora'));
    }

    public function update(Request $request, ConfigMora $config_mora)
    {
        $data = $request->validate([
            'cargo_fijo'      => 'nullable|numeric|min:0',
            'porcentaje_mora' => 'nullable|numeric|min:0|max:100',
            'periodo_gracia'  => 'nullable|integer|min:0',
        ]);

        $config_mora->update($data);

        return redirect()
            ->route('config_mora.index')
            ->with('success', 'Configuración de mora actualizada correctamente.');
    }

    public function destroy(ConfigMora $config_mora)
    {
        $config_mora->delete();

        return redirect()
            ->route('config_mora.index')
            ->with('success', 'Configuración de mora eliminada correctamente.');
    }
}
