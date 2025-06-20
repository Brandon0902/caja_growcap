<?php
// app/Http/Controllers/MovimientoCajaController.php

namespace App\Http\Controllers;

use App\Models\MovimientoCaja;
use App\Models\Caja;
use App\Models\CategoriaIngreso;
use App\Models\SubcategoriaIngreso;
use App\Models\CategoriaGasto;
use App\Models\SubcategoriaGasto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovimientoCajaController extends Controller
{
    public function index()
    {
        // Listar últimos movimientos con paginación
        $movimientos = MovimientoCaja::with(['caja','usuario'])
                          ->orderBy('fecha','desc')
                          ->paginate(20);

        return view('movimientos-caja.index', compact('movimientos'));
    }

    public function create()
    {
        // Datos para selects
        $cajas              = Caja::orderBy('nombre')->get();
        $catsIngreso        = CategoriaIngreso::orderBy('nombre')->get();
        $subsIngreso        = SubcategoriaIngreso::orderBy('nombre')->get();
        $catsGasto          = CategoriaGasto::orderBy('nombre')->get();
        $subsGasto          = SubcategoriaGasto::orderBy('nombre')->get();
        $proveedores        = Proveedor::orderBy('nombre')->get();

        return view('movimientos-caja.create', compact(
            'cajas','catsIngreso','subsIngreso','catsGasto','subsGasto','proveedores'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_caja'        => 'required|exists:cajas,id_caja',
            'tipo_mov'       => 'required|in:ingreso,gasto',
            'id_cat_ing'     => 'required_if:tipo_mov,ingreso|exists:categoria_ingreso,id_cat_ing',
            'id_sub_ing'     => 'nullable|exists:subcategoria_ingreso,id_sub_ingreso',
            'id_cat_gasto'   => 'required_if:tipo_mov,gasto|exists:categoria_gasto,id_cat_gasto',
            'id_sub_gasto'   => 'nullable|exists:subcategoria_gasto,id_sub_gasto',
            'proveedor_id'   => 'nullable|exists:proveedores,id_proveedor',
            'monto'          => 'required|numeric|min:0.01',
            'fecha'          => 'required|date',
            'descripcion'    => 'nullable|string|max:500',
        ]);

        // Obtén el último saldo de la caja
        $caja = Caja::findOrFail($data['id_caja']);
        $ultimoMov = $caja->movimientos()->latest('fecha')->first();
        $montoAnterior = $ultimoMov 
            ? $ultimoMov->monto_posterior 
            : $caja->saldo_inicial;

        // Calcula el posterior según ingreso/gasto
        $montoPosterior = $data['tipo_mov'] === 'ingreso'
            ? $montoAnterior + $data['monto']
            : $montoAnterior - $data['monto'];

        $data['monto_anterior']   = $montoAnterior;
        $data['monto_posterior']  = $montoPosterior;
        $data['id_usuario']       = Auth::id();

        MovimientoCaja::create($data);

        // (Opcional) actualiza saldo_final en la caja
        $caja->update(['saldo_final' => $montoPosterior]);

        return redirect()
            ->route('movimientos-caja.index')
            ->with('success', 'Movimiento registrado correctamente.');
    }
}
