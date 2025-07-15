<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\UserDeposito;
use Illuminate\Http\Request;

class UserDepositoController extends Controller
{
    /**
     * Mostrar listado de clientes activos con botón a sus depósitos.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $clientes = Cliente::where('status', 1)
            ->when($search, fn($q) => $q->whereRaw("CONCAT(nombre,' ',apellido) LIKE ?", ["%{$search}%"]))
            ->orderByDesc('id')
            ->paginate(15);

        return view('depositos.index', compact('clientes', 'search'));
    }

    /**
     * Mostrar todos los depósitos de un cliente.
     */
    public function show(Cliente $cliente)
    {
        $depositos = UserDeposito::where('id_cliente', $cliente->id)->get();

        return view('depositos.show', compact('cliente', 'depositos'));
    }

    /**
     * Actualizar solo el status de un depósito.
     */
    public function update(Request $request, UserDeposito $deposito)
    {
        $data = $request->validate([
            'status' => 'required|in:0,1,2',  // 0=Pendiente,1=Aprobado,2=Rechazado
        ]);

        $deposito->update(['status' => (int)$data['status']]);

        return back()->with('success', 'Status de depósito actualizado.');
    }
}
