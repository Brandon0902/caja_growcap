<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\UserData;
use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserDataController extends Controller
{
    /**
     * Listado de todos los registros.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $query  = UserData::with(['cliente','estado','municipio']);

        if ($search) {
            $query->whereHas('cliente', fn($q) =>
                $q->where('nombre','like',"%{$search}%")
                  ->orWhere('apellido','like',"%{$search}%")
            );
        }

        $datos = $query->orderByDesc('id')
                       ->paginate(15)
                       ->withQueryString();

        return view('user_data.index', compact('datos','search'));
    }

    /**
     * Formulario para crear.
     */
    public function create()
    {
        $clientes   = Cliente::where('status',1)->pluck('nombre','id');
        $estados    = Estado::where('status',1)->pluck('nombre','id');
        $municipios = collect();

        return view('user_data.create', compact('clientes','estados','municipios'));
    }

    /**
     * Almacenar nuevo registro.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cliente'               => 'required|exists:clientes,id',
            'id_estado'                => 'nullable|exists:estados,id',
            'rfc'                      => 'nullable|string|max:255',
            'direccion'                => 'nullable|string|max:255',
            'id_municipio'             => 'nullable|exists:municipios,id',
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
        ]);

        // Validación extra: suma exacta de porcentajes
        if (($request->input('porcentaje_1',0) + $request->input('porcentaje_2',0)) !== 100) {
            return back()
                ->withErrors(['porcentaje_2' => 'La suma de porcentajes debe ser exactamente 100%.'])
                ->withInput();
        }

        $data['id_usuario'] = Auth::id();
        UserData::create($data);

        return redirect()
            ->route('user_data.index')
            ->with('success','Datos de cliente creados correctamente.');
    }

    /**
     * Formulario para editar.
     */
    public function edit(UserData $userData)
{
    $userData->load(['cliente','estado','municipio','laboral']);

    $clientes   = Cliente::where('status',1)->pluck('nombre','id');
    $estados    = Estado ::where('status',1)->pluck('nombre','id');
    $municipios = $userData->id_estado
        ? Municipio::where('id_estado',$userData->id_estado)->pluck('nombre','id')
        : collect();

    // Ahora traemos id, nombre, dirección y teléfono
    $empresas = Empresa::select('id','nombre','direccion','telefono')->get();

    return view('user_data.edit', compact(
        'userData','clientes','estados','municipios','empresas'
    ));
}

    /**
     * Actualizar registro (y opcionalmente la contraseña de Cliente).
     */
    /**
 * Actualizar registro (y, si se envía, la contraseña del cliente).
 */
    public function update(Request $request, UserData $userData)
    {
        $tab = $request->input('tab', 'general');

        // ————————————— TAB “Acceso” —————————————
        if ($tab === 'acceso') {
            $data = $request->validate([
                'pass' => 'required|string|min:8|confirmed',
            ]);

            $cliente = $userData->cliente;
            $cliente->pass       = Hash::make($data['pass']);
            $cliente->fecha_edit = now();
            $cliente->save();

            return redirect()
                ->route('user_data.edit', [
                    'userData' => $userData->id,
                    'tab'      => 'acceso',
                ])
                ->with('success', 'Contraseña actualizada correctamente.');
        }

        // ————————————— Otras pestañas —————————————
        $data = $request->validate([
            'id_cliente'               => 'required|exists:clientes,id',
            'id_estado'                => 'nullable|exists:estados,id',
            'rfc'                      => 'nullable|string|max:255',
            'direccion'                => 'nullable|string|max:255',
            'id_municipio'             => 'nullable|exists:municipios,id',
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
        ]);

        // validación extra: porcentajes sumen 100%
        if (($request->input('porcentaje_1', 0) + $request->input('porcentaje_2', 0)) !== 100) {
            return back()
                ->withErrors(['porcentaje_2' => 'La suma de porcentajes debe ser exactamente 100%.'])
                ->withInput();
        }

        $userData->fill($data);
        $userData->id_usuario = Auth::id();
        $userData->save();

        return redirect()
            ->route('user_data.edit', [
                'userData' => $userData->id,
                'tab'      => $tab,
            ])
            ->with('success', 'Datos actualizados correctamente.');
    }

    /**
     * Actualiza solamente la sección “Documentos”.
     */
    public function updateDocumentos(Request $request, UserData $userData)
    {
        $data = $request->validate([
            'documento_01'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'documento_02'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'documento_02_02' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'documento_03'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'documento_04'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'documento_05'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'fecha'           => 'nullable|date',
        ]);

        // crea o recupera el Documento relacionado
        $doc = Documento::updateOrCreate(
            ['id_cliente' => $userData->id_cliente],
            ['id_usuario' => Auth::id()]
        );

        // almacena cada archivo
        foreach ([
            'documento_01','documento_02','documento_02_02',
            'documento_03','documento_04','documento_05'
        ] as $field) {
            if ($request->hasFile($field)) {
                $doc->$field = $request
                    ->file($field)
                    ->store('documentos', 'public');
            }
        }

        // asigna fecha si fue enviada
        if (! empty($data['fecha'])) {
            $doc->fecha = $data['fecha'];
        }

        $doc->save();

        return back()
            ->with('success', 'Documentos actualizados correctamente.');
    }



    /**
     * Desactivar registro.
     */
    public function destroy(UserData $userData)
    {
        $userData->update([
            'status'     => 0,
            'id_usuario' => Auth::id(),
        ]);

        return redirect()
            ->route('user_data.index')
            ->with('success','Datos de cliente desactivados correctamente.');
    }

    /**
     * Ver un registro.
     */
    public function show(UserData $userData)
    {
        $userData->load(['cliente','estado','municipio']);
        return view('user_data.show', compact('userData'));
    }
}
