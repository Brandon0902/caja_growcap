<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\UserInversion;
use Illuminate\Http\Request;

class UserInversionController extends Controller
{
    /**
     * Mostrar listado de clientes con botón “Ver Inversiones”.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $query = Cliente::query();
        // Si quieres filtrar sólo activos:
        // $query->where('status', 1);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%");
            });
        }

        $clientes = $query
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('adminuserinversiones.index', compact('clientes', 'search'));
    }

    /**
     * Mostrar sólo las inversiones del cliente seleccionado.
     */
    public function show($idCliente)
    {
        $cliente = Cliente::findOrFail($idCliente);

        $inversiones = UserInversion::where('id_cliente', $idCliente)
            ->orderByDesc('fecha_inicio')
            ->paginate(15);

        return view('adminuserinversiones.show', compact('cliente', 'inversiones'));
    }
}
