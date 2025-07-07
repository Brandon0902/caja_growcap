<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
{
    /**
     * Mostrar listado de clientes.
     */
    public function index()
    {
        $clientes = Cliente::orderByDesc('id')->paginate(15);
        return view('adminclientes.index', compact('clientes'));
    }

    /**
     * Formulario de creación.
     */
    public function create()
    {
        return view('adminclientes.create');
    }

    /**
     * Almacenar nuevo cliente.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo_cliente' => 'required|string|max:8',
            'id_superior'    => 'nullable|integer',
            'id_padre'       => 'nullable|integer',
            'nombre'         => 'required|string|max:255',
            'apellido'       => 'nullable|string|max:255',
            'telefono'       => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'user'           => 'nullable|string|max:255',
            'pass'           => 'nullable|string|min:8|confirmed',
            'tipo'           => 'nullable|string|max:255',
            'fecha'          => 'nullable|date',
        ]);

        // Si enviaron contraseña, la hasheamos; si no, la quitamos.
        if (!empty($data['pass'])) {
            $data['pass'] = Hash::make($data['pass']);
        } else {
            unset($data['pass']);
        }

        // Campos automáticos
        $data['id_usuario']  = session('id_admin');
        $data['status']      = 1;
        $data['fecha_edit']  = now();

        Cliente::create($data);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    /**
     * Mostrar detalle de un cliente.
     */
    public function show(Cliente $cliente)
    {
        return view('adminclientes.show', compact('cliente'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Cliente $cliente)
    {
        return view('adminclientes.edit', compact('cliente'));
    }

    /**
     * Actualizar cliente existente.
     */
    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'codigo_cliente' => 'required|string|max:8',
            'id_superior'    => 'nullable|integer',
            'id_padre'       => 'nullable|integer',
            'nombre'         => 'required|string|max:255',
            'apellido'       => 'nullable|string|max:255',
            'telefono'       => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'user'           => 'nullable|string|max:255',
            'pass'           => 'nullable|string|min:8|confirmed',
            'tipo'           => 'nullable|string|max:255',
            'fecha'          => 'nullable|date',
            'status'         => 'required|integer|in:0,1',
        ]);

        if (!empty($data['pass'])) {
            $data['pass'] = Hash::make($data['pass']);
        } else {
            unset($data['pass']);
        }

        $data['id_usuario'] = session('id_admin');
        $data['fecha_edit'] = now();

        $cliente->update($data);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Desactivar cliente (soft delete).
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->update([
            'status'     => 0,
            'id_usuario' => session('id_admin'),
            'fecha_edit' => now(),
        ]);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente desactivado correctamente.');
    }
}
