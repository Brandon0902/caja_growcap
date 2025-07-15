<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Retiro;
use App\Models\RetiroAhorro;
use Illuminate\Http\Request;

class RetiroController extends Controller
{
    /**
     * Mostrar listado de clientes activos.
     */
    public function index()
    {
        $clientes = Cliente::where('status', 1)
                           ->paginate(15);

        return view('retiros.index', compact('clientes'));
    }

    /**
     * Mostrar retiros de un cliente.
     */
    public function show(Cliente $cliente)
    {
        $retirosInv = Retiro::where('id_cliente', $cliente->id)->get();
        $retirosAh  = RetiroAhorro::where('id_cliente', $cliente->id)->get();

        return view('retiros.show', compact('cliente','retirosInv','retirosAh'));
    }

    /**
     * Actualizar un retiro de inversión.
     */
    public function updateInversion(Request $request, Retiro $retiro)
    {
        $data = $request->validate([
            'tipo'     => 'required|string',
            'cantidad' => 'required|numeric',
            'status'   => 'required|in:0,1,2,3',
        ]);

        // Asegurar entero
        $data['status'] = (int) $data['status'];

        $retiro->update($data);

        return back()->with('success', 'Retiro de inversión actualizado.');
    }

    /**
     * Actualizar un retiro de ahorro.
     */
    public function updateAhorro(Request $request, RetiroAhorro $retiroAhorro)
    {
        $data = $request->validate([
            'tipo'     => 'required|string',
            'cantidad' => 'required|numeric',
            'status'   => 'required|in:0,1,2,3',
        ]);

        $data['status'] = (int) $data['status'];

        $retiroAhorro->update($data);

        return back()->with('success', 'Retiro de ahorro actualizado.');
    }
}
