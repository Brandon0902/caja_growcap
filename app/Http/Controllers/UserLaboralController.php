<?php

namespace App\Http\Controllers;

use App\Models\UserData;
use App\Models\UserLaboral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserLaboralController extends Controller
{
    /**
     * Almacenar un nuevo registro laboral para un cliente.
     */
    public function store(Request $request, UserData $userData)
    {
        $data = $request->validate([
            'empresa_id'          => 'nullable|exists:empresas,id',
            'direccion'           => 'required|string|max:255',
            'telefono'            => 'nullable|string|max:50',
            'puesto'              => 'nullable|string|max:100',
            'salario_mensual'     => 'required|numeric|min:0',
            'tipo_salario'        => 'required|in:Asalariado,Independiente,No hay datos',
            'estado_salario'      => 'required|in:Estable,Variable,Inestable',
            'tipo_salario_valor'  => 'required|integer|min:0|max:255',
            'recurrencia_pago'    => 'required|in:Semanal,Quincenal,Mensual',
            'recurrencia_valor'   => 'required|integer|min:0|max:255',
            'fecha_registro'      => 'nullable|date',
        ]);

        $data['id_cliente'] = $userData->id_cliente;
        $data['id_usuario'] = Auth::id();
        $data['fecha']      = $data['fecha_registro'] ?? now();

        UserLaboral::create($data);

        return redirect()
            ->route('user_data.edit', ['userData' => $userData->id, 'tab' => 'laborales'])
            ->with('success', 'Datos laborales guardados correctamente.');
    }

    /**
     * Actualizar un registro laboral existente.
     */
    public function update(Request $request, UserData $userData, UserLaboral $laboral)
    {
        $data = $request->validate([
            'empresa_id'          => 'nullable|exists:empresas,id',
            'direccion'           => 'required|string|max:255',
            'telefono'            => 'nullable|string|max:50',
            'puesto'              => 'nullable|string|max:100',
            'salario_mensual'     => 'required|numeric|min:0',
            'tipo_salario'        => 'required|in:Asalariado,Independiente,No hay datos',
            'estado_salario'      => 'required|in:Estable,Variable,Inestable',
            'tipo_salario_valor'  => 'required|integer|min:0|max:255',
            'recurrencia_pago'    => 'required|in:Semanal,Quincenal,Mensual',
            'recurrencia_valor'   => 'required|integer|min:0|max:255',
            'fecha_registro'      => 'nullable|date',
        ]);

        $data['id_usuario'] = Auth::id();
        $data['fecha']      = $data['fecha_registro'] ?? $laboral->fecha;

        $laboral->update($data);

        return redirect()
            ->route('user_data.edit', ['userData' => $userData->id, 'tab' => 'laborales'])
            ->with('success', 'Datos laborales actualizados correctamente.');
    }

    /**
     * Eliminar un registro laboral.
     */
    public function destroy(UserData $userData, UserLaboral $laboral)
    {
        $laboral->delete();

        return redirect()
            ->route('user_data.edit', ['userData' => $userData->id, 'tab' => 'laborales'])
            ->with('success', 'Registro laboral eliminado.');
    }
}
