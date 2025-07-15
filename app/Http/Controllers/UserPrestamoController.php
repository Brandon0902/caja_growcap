<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\UserAbono;
use App\Models\UserPrestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Prestamo;       // Modelo de los planes (PK = id_prestamo)

class UserPrestamoController extends Controller
{
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

        return view('adminuserprestamos.index', compact('clientes','search'));
    }

    public function show($idCliente)
    {
        $cliente   = Cliente::findOrFail($idCliente);
        $prestamos = UserPrestamo::where('id_cliente', $idCliente)
                          ->orderByDesc('fecha_inicio')
                          ->paginate(15);

        $statusOptions = [
            1 => 'Autorizado',
            2 => 'Pendiente',
            3 => 'En revisión',
            4 => 'Rechazado',
            5 => 'Pagado',
            6 => 'Terminado',
        ];

        return view('adminuserprestamos.show', compact('cliente','prestamos','statusOptions'));
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nombre')->get();
        $planes   = Prestamo::where('status', 1)->get();

        return view('adminuserprestamos.create', compact('clientes','planes'));
    }



    public function store(Request $request)
    {
        // 0) Validar y bloquear duplicados
        $request->validate([
            'id_cliente' => [
                'required',
                'exists:clientes,id',
                function($attr, $value, $fail) {
                    if (UserPrestamo::where('id_cliente', $value)
                                     ->whereIn('status', [2,3])
                                     ->exists()
                    ) {
                        $fail('Ya tienes una solicitud pendiente o en revisión.');
                    }
                },
            ],
            'id_activo'     => 'required|exists:prestamos,id_prestamo',
            'fecha_inicio'  => 'required|date',
            'cantidad'      => 'required|numeric|min:0',
            'codigo_aval'   => 'nullable|string|exists:clientes,codigo_cliente|max:50',
            'doc_solicitud_aval'        => 'required_without:codigo_aval|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'doc_comprobante_domicilio' => 'required_without:codigo_aval|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'doc_ine_frente'            => 'required_without:codigo_aval|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'doc_ine_reverso'           => 'required_without:codigo_aval|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // 1) Cargar datos básicos
        $data = $request->only('id_cliente','id_activo','fecha_inicio','cantidad','codigo_aval');
        $plan = Prestamo::findOrFail($data['id_activo']);

        // 2) Crear préstamo
        $prestamo = new UserPrestamo([
            'id_cliente'       => $data['id_cliente'],
            'id_activo'        => $data['id_activo'],
            'fecha_solicitud'  => now(),
            'fecha_inicio'     => $data['fecha_inicio'],
            'cantidad'         => $data['cantidad'],
            'tipo_prestamo'    => $plan->periodo,
            'semanas'          => $plan->semanas,
            'interes'          => $plan->interes,
            'interes_generado' => $data['cantidad'] * $plan->interes / 100,
            'status'           => 3,  // En revisión
            'aval_status'      => 2,  // Pendiente de aval
            'id_usuario'       => Auth::id(),
            'abonos_echos'     => 0,
            'num_atrasos'      => 0,
            'mora_acumulada'   => 0.00,
        ]);

        // 3) Aval o documentos
        if (!empty($data['codigo_aval'])) {
            $aval = Cliente::where('codigo_cliente', $data['codigo_aval'])->first();
            $prestamo->aval_id = $aval->id;
        } else {
            foreach ([
                'doc_solicitud_aval',
                'doc_comprobante_domicilio',
                'doc_ine_frente',
                'doc_ine_reverso',
            ] as $field) {
                if ($request->hasFile($field)) {
                    $prestamo->{$field} = $request->file($field)
                                                  ->store('prestamos/aval','public');
                }
            }
        }

        // 4) Guardar préstamo
        $prestamo->save();

        // 5) Generar abonos semanales pendientes
        $fechaInicio = Carbon::parse($prestamo->fecha_inicio);
        $montoTotal  = $prestamo->cantidad + $prestamo->interes_generado;
        $numSemanas  = max(1, $prestamo->semanas);
        $pagoMinimo  = $montoTotal / $numSemanas;

        for ($i = 1; $i <= $prestamo->semanas; $i++) {
            $fechaVto  = $fechaInicio->copy()->addWeeks($i)->toDateString();
            $pagado    = $pagoMinimo * ($i - 1);
            $saldoRest = max(0, $montoTotal - $pagado);

            UserAbono::create([
                'id_prestamo'       => $prestamo->id,
                'id_cliente'        => $prestamo->id_cliente,
                'cantidad'          => 0,
                'tipo_abono'        => 'pendiente',
                'num_pago'          => $i,
                'mora_generada'     => 0,
                'saldo_restante'    => $saldoRest,
                'fecha'             => $fechaVto,
                'fecha_vencimiento' => $fechaVto,
                'status'            => 0,
            ]);
        }

        // 6) Redirigir con éxito
        return redirect()
            ->route('user_prestamos.show', $prestamo->id_cliente)
            ->with('success', 'Préstamo creado correctamente y abonos generados.');
    }



    public function edit(UserPrestamo $prestamo)
    {
        $statusOptions = [
            1 => 'Autorizado',
            2 => 'Pendiente',
            3 => 'En revisión',
            4 => 'Rechazado',
            5 => 'Pagado',
            6 => 'Terminado',
        ];

        return view('adminuserprestamos.edit',compact('prestamo','statusOptions'));
    }

    public function update(Request $request, UserPrestamo $prestamo)
    {
        $data = $request->validate([
            'status' => 'required|in:1,2,3,4,5,6',
            'nota'   => 'nullable|string',
        ]);

        $prestamo->update([
            'status'            => $data['status'],
            'nota'              => $data['nota'],
            'aval_responded_at' => now(),
        ]);

        return redirect()
            ->route('user_prestamos.show', $prestamo->id_cliente)
            ->with('success','Préstamo actualizado correctamente');
    }
}
