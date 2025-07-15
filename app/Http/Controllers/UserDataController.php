<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\UserData;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserDataController extends Controller
{
    /*------------------------------------------------------------------
     | LISTADO
     *------------------------------------------------------------------*/
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $clientes = Cliente::with('userData')
            ->when($search, fn ($q) =>
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
            )
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('user_data.index', compact('clientes', 'search'));
    }

    /*------------------------------------------------------------------
     | FORMULARIO GET
     *------------------------------------------------------------------*/
    // ...

public function form(Cliente $cliente)
{
    $userData = $cliente->userData
              ?? new UserData(['id_cliente' => $cliente->id]);

    $estados = Estado::where('status', 1)
                     ->orderBy('nombre')
                     ->pluck('nombre', 'id');

    /* Estado actualmente seleccionado (viejo input o dato guardado) */
    $selectedEstado = old('id_estado', $userData->id_estado);

    /* Municipios únicamente del estado seleccionado */
    $municipios = $selectedEstado
        ? Municipio::where('id_estado', $selectedEstado)
                   ->orderBy('nombre')
                   ->pluck('nombre' , 'id')
        : collect();                       // vacío si aún no se eligió estado

    // empresas para otras pestañas
    $empresas = Empresa::select('id','nombre','direccion','telefono')->get();

    return view('user_data.form', compact(
        'cliente', 'userData',
        'estados', 'municipios',
        'empresas'
    ));
}


    /*------------------------------------------------------------------
     | GUARDAR / ACTUALIZAR  POST
     *------------------------------------------------------------------*/
    public function save(Request $request, Cliente $cliente)
    {
        /* pestaña activa */
        $activeTab = $request->input('tab', 'general');

        /* reglas */
        $rules = [
            'id_estado'                => 'nullable|exists:estados,id',
            'id_municipio'             => 'nullable|exists:municipios,id',
            'rfc'                      => 'nullable|string|max:255',
            'direccion'                => 'nullable|string|max:255',
            'colonia'                  => 'nullable|string|max:255',
            'cp'                       => 'nullable|string|max:20',
            'beneficiario'             => 'nullable|string|max:255',
            'beneficiario_telefono'    => 'nullable|string|max:50',
            'beneficiario_02'          => 'nullable|string|max:255',
            'beneficiario_telefono_02' => 'nullable|string|max:50',
            'banco'                    => 'nullable|string|max:255',
            'cuenta'                   => 'nullable|string|max:255',
            'nip'                      => 'nullable|string|min:4|max:4',
            'fecha_alta'               => 'nullable|date',
            'fecha_modificacion'       => 'nullable|date',
            'status'                   => 'nullable|in:0,1',
            'porcentaje_1'             => 'nullable|numeric|min:0|max:100',
            'porcentaje_2'             => 'nullable|numeric|min:0|max:100',
            'fecha_ingreso'            => 'nullable|date',
        ];

        $data = $request->validate($rules);

        /* verificación de porcentajes sólo en la pestaña Beneficiarios */
        if ($activeTab === 'beneficiarios') {
            $p1 = (float) $request->input('porcentaje_1', 0);
            $p2 = (float) $request->input('porcentaje_2', 0);

            if (($p1 + $p2) !== 100) {
                return back()
                    ->withErrors(['porcentaje_2' => 'La suma de porcentajes debe ser 100 %.'])
                    ->withInput();
            }
        }

        /* guardar */
        $data['id_usuario'] = Auth::id();

        $cliente->userData()->updateOrCreate(
            ['id_cliente' => $cliente->id],
            $data
        );

        /* regresar a la misma pestaña */
        return redirect()
            ->route('clientes.datos.form', ['cliente' => $cliente, 'tab' => $activeTab])
            ->with('success', 'Datos guardados correctamente.');
    }
}
