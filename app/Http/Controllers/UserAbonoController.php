<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\UserPrestamo;
use App\Models\UserAbono;
use Illuminate\Http\Request;

class UserAbonoController extends Controller
{
    /**
     * 1) Listado de clientes con botón "Ver préstamos"
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $clientes = Cliente::when($search, fn($q) =>
                        $q->where('nombre', 'like', "%{$search}%")
                          ->orWhere('apellido', 'like', "%{$search}%")
                     )
                     ->orderBy('nombre')
                     ->paginate(15)
                     ->withQueryString();

        return view('adminuserabonos.clientes.index', compact('clientes','search'));
    }

    /**
     * 2) Listado de préstamos de un cliente dado (UserPrestamo)
     */
    public function showPrestamos($clienteId)
    {
        $cliente = Cliente::findOrFail($clienteId);

        $prestamos = UserPrestamo::where('id_cliente', $clienteId)
                         ->orderByDesc('fecha_inicio')
                         ->paginate(15);

        return view('adminuserabonos.prestamos.index', compact('cliente','prestamos'));
    }

    /**
     * 3) Listado de abonos de un préstamo de usuario dado
     */
    public function showAbonos($userPrestamoId)
    {
        // cargamos el préstamo de usuario
        $prestamo = UserPrestamo::findOrFail($userPrestamoId);

        // buscamos todos los abonos vinculados al préstamo de usuario
        $abonos = UserAbono::where('user_prestamo_id', $prestamo->id)
                    ->orderBy('num_pago')
                    ->get();

        return view('adminuserabonos.abonos.index', compact('prestamo','abonos'));
    }

    /**
     * 4) Cambio rápido de status (0=Pendiente, 1=Pagado, 2=Vencido)
     */
    public function updateStatus(Request $request, $abonoId)
    {
        $data = $request->validate([
            'status' => 'required|in:0,1,2',
        ]);

        $abono = UserAbono::findOrFail($abonoId);
        $abono->status = $data['status'];
        $abono->save();

        return back()->with('success','Status actualizado.');
    }

    /**
     * 5) Devuelve el partial HTML para el modal de edición
     */
    public function edit($abonoId)
    {
        $abono = UserAbono::findOrFail($abonoId);
        return view('adminuserabonos.abonos.edit_modal', compact('abono'));
    }

    /**
     * 6) Guarda la edición completa desde el modal
     */
    public function update(Request $request, $abonoId)
    {
        $data = $request->validate([
            'tipo_abono'        => 'nullable|string|max:50',
            'fecha_vencimiento' => 'nullable|date',
            'num_pago'          => 'nullable|integer',
            'cantidad'          => 'nullable|numeric',
            'saldo_restante'    => 'nullable|numeric',
            'mora_generada'     => 'nullable|numeric',
            'fecha'             => 'nullable|date',
            'status'            => 'required|in:0,1,2',
        ]);

        $abono = UserAbono::findOrFail($abonoId);
        $abono->update($data);

        return back()->with('success','Abono actualizado correctamente.');
    }
}
