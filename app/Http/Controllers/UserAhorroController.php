<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\UserAhorro;
use Illuminate\Http\Request;

class UserAhorroController extends Controller
{
    /**
     * Mostrar lista de clientes con botón “Ver Ahorros”.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $query = Cliente::query();

        // Si tu tabla `clientes` tiene un campo `status` para filtrar activos,
        // descomenta esta línea:
        $query->where('status', 1);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%");
            });
        }

        $clientes = $query
            ->orderBy('nombre')  // ordenar por nombre en lugar de 'name'
            ->paginate(15)
            ->withQueryString();

        return view('adminuserahorros.index', compact('clientes', 'search'));
    }

    /**
     * Mostrar sólo los ahorros del cliente seleccionado.
     */
    public function show($idCliente)
    {
        $cliente = Cliente::findOrFail($idCliente);

        // Con 'movimientos' eager‐loaded
        $ahorros = UserAhorro::with(['ahorro', 'movimientos'])
            ->where('id_cliente', $idCliente)
            ->orderByDesc('fecha_inicio')
            ->paginate(15);

        return view('adminuserahorros.show', compact('cliente', 'ahorros'));
    }
}
