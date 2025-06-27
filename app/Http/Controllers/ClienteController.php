<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::orderByDesc('id')->paginate(15);
        return view('adminclientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('adminclientes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_superior'  => 'nullable|integer',
            'id_padre'     => 'nullable|integer',
            'nombre'       => 'required|string|max:255',
            'apellido'     => 'nullable|string|max:255',
            'telefono'     => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'username'     => 'nullable|string|max:255',
            'pass'         => 'nullable|string|max:255',
            'tipo'         => 'nullable|string|max:255',
            'fecha'        => 'nullable|date',
        ]);

        // ID del admin en sesión
        $adminId = session('id_admin');

        $data['id_usuario'] = $adminId;
        $data['status']     = 1;
        $data['fecha_edit'] = now();

        Cliente::create($data);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Cliente $cliente)
    {
        return view('adminclientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        return view('adminclientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'id_superior'  => 'nullable|integer',
            'id_padre'     => 'nullable|integer',
            'nombre'       => 'required|string|max:255',
            'apellido'     => 'nullable|string|max:255',
            'telefono'     => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'username'     => 'nullable|string|max:255',
            'pass'         => 'nullable|string|max:255',
            'tipo'         => 'nullable|string|max:255',
            'fecha'        => 'nullable|date',
            'status'       => 'required|integer|in:0,1',
        ]);

        $adminId = session('id_admin');

        $data['id_usuario'] = $adminId;
        $data['fecha_edit'] = now();

        $cliente->update($data);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $adminId = session('id_admin');

        $cliente->update([
            'status'     => 0,
            'id_usuario' => $adminId,
            'fecha_edit' => now(),
        ]);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente desactivado correctamente.');
    }
}
