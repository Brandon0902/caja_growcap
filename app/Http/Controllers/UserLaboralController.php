<?php

namespace App\Http\Controllers;

use App\Models\UserData;
use App\Models\UserLaboral;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserLaboralController extends Controller
{
    /** Mapas para valores numéricos */
    private const MAP_TIPO_SALARIO = [
        'Asalariado'    => 10,
        'Independiente' => 7,
        'No hay datos'  => 5,
    ];

    // Escala *100 para evitar floats (433 ≈ 4.33 semanas por mes)
    private const MAP_RECURR_VALOR = [
        'Semanal'   => 433,
        'Quincenal' => 200,
        'Mensual'   => 100,
    ];

    /** Helper: volver a la ficha del cliente (tab laborales) */
    private function backToFicha(UserData $userData, string $flashMsg)
    {
        return redirect()
            ->route('clientes.datos.form', [
                'cliente' => $userData->id_cliente,
                'tab'     => 'laborales',
            ])
            ->with('success', $flashMsg);
    }

    /** Crea/actualiza (upsert) el registro laboral del cliente */
    public function store(Request $request, UserData $userData)
    {
        $data = $request->validate([
            'empresa_id'      => 'nullable|exists:empresas,id',
            'puesto'          => 'nullable|string|max:100',
            'salario_mensual' => 'required|numeric|min:0',
            'tipo_salario'    => 'required|in:Asalariado,Independiente,No hay datos',
            'estado_salario'  => 'required|in:Estable,Variable,Inestable',
            'recurrencia_pago'=> 'required|in:Semanal,Quincenal,Mensual',
            'fecha_registro'  => 'nullable|date',
        ]);

        // Relleno de empresa (servidor) — no dependemos de inputs ocultos
        $direccion = null;
        $telefono  = null;
        if (!empty($data['empresa_id'])) {
            $empresa = Empresa::find($data['empresa_id']);
            if (!$empresa) {
                throw ValidationException::withMessages(['empresa_id' => 'Empresa no encontrada.']);
            }
            $direccion = $empresa->direccion;
            $telefono  = $empresa->telefono;
        }

        // Autocalcular valores numéricos
        $data['tipo_salario_valor'] = self::MAP_TIPO_SALARIO[$data['tipo_salario']];
        $data['recurrencia_valor']  = self::MAP_RECURR_VALOR[$data['recurrencia_pago']];

        $payload = $data + [
            'id_cliente'  => $userData->id_cliente,
            'id_usuario'  => Auth::id(),
            'fecha'       => $data['fecha_registro'] ?? now(),
            'direccion'   => $direccion,
            'telefono'    => $telefono,
        ];

        // Un solo laboral por cliente: upsert por id_cliente
        UserLaboral::updateOrCreate(
            ['id_cliente' => $userData->id_cliente],
            $payload
        );

        return $this->backToFicha($userData, 'Datos laborales guardados/actualizados correctamente.');
    }

    /** Actualiza (asegurando pertenencia) */
    public function update(Request $request, UserData $userData, UserLaboral $laboral)
    {
        if ((int)$laboral->id_cliente !== (int)$userData->id_cliente) {
            abort(404);
        }

        $data = $request->validate([
            'empresa_id'      => 'nullable|exists:empresas,id',
            'puesto'          => 'nullable|string|max:100',
            'salario_mensual' => 'required|numeric|min:0',
            'tipo_salario'    => 'required|in:Asalariado,Independiente,No hay datos',
            'estado_salario'  => 'required|in:Estable,Variable,Inestable',
            'recurrencia_pago'=> 'required|in:Semanal,Quincenal,Mensual',
            'fecha_registro'  => 'nullable|date',
        ]);

        // Relleno/actualización de empresa (servidor)
        $direccion = null;
        $telefono  = null;
        if (!empty($data['empresa_id'])) {
            $empresa  = Empresa::find($data['empresa_id']);
            if (!$empresa) {
                throw ValidationException::withMessages(['empresa_id' => 'Empresa no encontrada.']);
            }
            $direccion = $empresa->direccion;
            $telefono  = $empresa->telefono;
        }

        // Autocalcular valores numéricos
        $data['tipo_salario_valor'] = self::MAP_TIPO_SALARIO[$data['tipo_salario']];
        $data['recurrencia_valor']  = self::MAP_RECURR_VALOR[$data['recurrencia_pago']];

        $data['id_usuario'] = Auth::id();
        // Conserva fecha previa si no envían fecha_registro
        $data['fecha']      = $data['fecha_registro'] ?? ($laboral->fecha ?? now());

        // Asignar dirección/teléfono (pueden ser null si no hay empresa)
        $data['direccion']  = $direccion;
        $data['telefono']   = $telefono;

        $laboral->update($data);

        return $this->backToFicha($userData, 'Datos laborales actualizados correctamente.');
    }

    /** Elimina (asegurando pertenencia) */
    public function destroy(UserData $userData, UserLaboral $laboral)
    {
        if ((int)$laboral->id_cliente !== (int)$userData->id_cliente) {
            abort(404);
        }

        $laboral->delete();

        return $this->backToFicha($userData, 'Registro laboral eliminado.');
    }
}
