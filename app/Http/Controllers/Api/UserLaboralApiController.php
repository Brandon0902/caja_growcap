<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserData;
use App\Models\UserLaboral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserLaboralApiController extends Controller
{
    protected function rules(): array
    {
        return [
            'empresa_id'          => 'nullable|exists:empresas,id',
            'direccion'           => 'nullable|string|max:255',
            'telefono'            => 'nullable|string|max:50',
            'puesto'              => 'nullable|string|max:100',
            'salario_mensual'     => 'required|numeric|min:0',
            'tipo_salario'        => 'required|in:Asalariado,Independiente,No hay datos',
            'estado_salario'      => 'required|in:Estable,Variable,Inestable',
            'tipo_salario_valor'  => 'required|integer|min:0|max:255',
            'recurrencia_pago'    => 'required|in:Semanal,Quincenal,Mensual',
            'recurrencia_valor'   => 'required|integer|min:0|max:255',
            'fecha_registro'      => 'nullable|date',
        ];
    }

    protected function assertOwner(UserData $userData, UserLaboral $laboral): void
    {
        abort_if((int)$laboral->id_cliente !== (int)$userData->id_cliente, 404);
    }

    public function index(UserData $userData)
    {
        $laboral = UserLaboral::with('empresa:id,nombre,direccion,telefono')
            ->where('id_cliente', $userData->id_cliente)
            ->first();

        return response()->json(['data' => $laboral]);
    }

    public function show(UserData $userData, UserLaboral $laboral)
    {
        $this->assertOwner($userData, $laboral);
        $laboral->load('empresa:id,nombre,direccion,telefono');

        return response()->json(['data' => $laboral]);
    }

    public function store(Request $request, UserData $userData)
    {
        $data = $request->validate($this->rules());

        if (!empty($data['empresa_id']) && (empty($data['direccion']) || empty($data['telefono']))) {
            throw ValidationException::withMessages([
                'empresa_id' => 'Selecciona una empresa válida y asegúrate de tener dirección y teléfono.',
            ]);
        }

        $payload = $data + [
            'id_cliente' => $userData->id_cliente,
            'id_usuario' => Auth::id(),
            'fecha'      => $data['fecha_registro'] ?? now(),
        ];

        $laboral = UserLaboral::updateOrCreate(
            ['id_cliente' => $userData->id_cliente],
            $payload
        );

        $laboral->load('empresa:id,nombre,direccion,telefono');

        return response()->json([
            'message' => 'Datos laborales guardados/actualizados correctamente.',
            'data'    => $laboral,
        ], 201);
    }

    public function update(Request $request, UserData $userData, UserLaboral $laboral)
    {
        $this->assertOwner($userData, $laboral);

        $data = $request->validate($this->rules());

        if (!empty($data['empresa_id']) && (empty($data['direccion']) || empty($data['telefono']))) {
            throw ValidationException::withMessages([
                'empresa_id' => 'Selecciona una empresa válida y asegúrate de tener dirección y teléfono.',
            ]);
        }

        $data['id_usuario'] = Auth::id();
        $data['fecha']      = $data['fecha_registro'] ?? ($laboral->fecha ?? now());

        $laboral->update($data);
        $laboral->load('empresa:id,nombre,direccion,telefono');

        return response()->json([
            'message' => 'Datos laborales actualizados correctamente.',
            'data'    => $laboral,
        ]);
    }

    public function destroy(UserData $userData, UserLaboral $laboral)
    {
        $this->assertOwner($userData, $laboral);
        $laboral->delete();

        return response()->json(['message' => 'Registro laboral eliminado.']);
    }
}
