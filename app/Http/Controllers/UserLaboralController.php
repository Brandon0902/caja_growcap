<?php

namespace App\Http\Controllers;

use App\Models\UserData;
use App\Models\UserLaboral;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserLaboralController extends Controller
{
    private const MAP_TIPO_SALARIO = [
        'Asalariado'    => 10,
        'Independiente' => 7,
        'No hay datos'  => 5,
    ];

    private const MAP_RECURR_VALOR = [
        'Semanal'   => 433,
        'Quincenal' => 200,
        'Mensual'   => 100,
    ];

    private function backToFicha(UserData $userData, string $msg, bool $ok = true)
    {
        return redirect()
            ->route('clientes.datos.form', [
                'cliente' => $userData->id_cliente,
                'tab'     => 'laborales',
            ])
            ->with($ok ? 'success' : 'error', $msg);
    }

    /**
     * Activa logging de queries SOLO si mandas ?debug_laboral=1 (temporal).
     */
    private function maybeEnableSqlDebug(Request $request): void
    {
        if (!$request->boolean('debug_laboral')) return;

        DB::listen(function ($query) {
            Log::debug('[LABORAL][SQL]', [
                'sql'      => $query->sql,
                'bindings' => $query->bindings,
                'time_ms'  => $query->time,
            ]);
        });
    }

    public function store(Request $request, UserData $userData)
    {
        $this->maybeEnableSqlDebug($request);

        Log::info('[LABORAL][STORE] hit', [
            'userData_id'  => $userData->id,
            'id_cliente'   => $userData->id_cliente,
            'auth_id'      => Auth::id(),
            'route'        => optional($request->route())->getName(),
            'method'       => $request->method(),
        ]);

        try {
            // OJO: si esto falla, Laravel redirige con errors (no entra al insert)
            $data = $request->validate([
                'empresa_id'       => 'nullable|exists:empresas,id',
                'puesto'           => 'nullable|string|max:100',
                'salario_mensual'  => 'required|numeric|min:0',
                'tipo_salario'     => 'required|in:Asalariado,Independiente,No hay datos',
                'estado_salario'   => 'required|in:Estable,Variable,Inestable',
                'recurrencia_pago' => 'required|in:Semanal,Quincenal,Mensual',
                'fecha_registro'   => 'nullable|date',
            ]);

            Log::debug('[LABORAL][STORE] validated', $data);

            // fecha_registro es NOT NULL en tu BD -> jamás mandes null
            $data['fecha_registro'] = $data['fecha_registro'] ?: now();

            // direccion es NOT NULL -> si no hay empresa, usa ''
            $direccion = '';
            $telefono  = null;

            if (!empty($data['empresa_id'])) {
                $empresa = Empresa::find($data['empresa_id']);
                if (!$empresa) {
                    throw ValidationException::withMessages(['empresa_id' => 'Empresa no encontrada.']);
                }
                $direccion = $empresa->direccion ?? '';
                $telefono  = $empresa->telefono;
            }

            $data['tipo_salario_valor'] = self::MAP_TIPO_SALARIO[$data['tipo_salario']] ?? 0;
            $data['recurrencia_valor']  = self::MAP_RECURR_VALOR[$data['recurrencia_pago']] ?? 0;

            $payload = $data + [
                'id_cliente' => $userData->id_cliente,
                'id_usuario' => Auth::id(),
                'fecha'      => $data['fecha_registro'], // tu BD tiene default, pero lo igualamos
                'direccion'  => $direccion,
                'telefono'   => $telefono,
            ];

            Log::debug('[LABORAL][STORE] payload', $payload);

            $laboral = UserLaboral::updateOrCreate(
                ['id_cliente' => $userData->id_cliente],
                $payload
            );

            Log::info('[LABORAL][STORE] saved', [
                'laboral_id' => $laboral->id,
                'created'    => $laboral->wasRecentlyCreated,
            ]);

            return $this->backToFicha($userData, 'Datos laborales guardados/actualizados correctamente.');
        } catch (\Throwable $e) {
            Log::error('[LABORAL][STORE] ERROR', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return back()->withInput()->with('error', 'No se pudo guardar laborales. Revisa logs.');
        }
    }

    public function update(Request $request, UserData $userData, UserLaboral $laboral)
    {
        $this->maybeEnableSqlDebug($request);

        Log::info('[LABORAL][UPDATE] hit', [
            'userData_id'  => $userData->id,
            'id_cliente'   => $userData->id_cliente,
            'laboral_id'   => $laboral->id,
            'auth_id'      => Auth::id(),
            'route'        => optional($request->route())->getName(),
            'method'       => $request->method(),
        ]);

        try {
            if ((int)$laboral->id_cliente !== (int)$userData->id_cliente) {
                abort(404);
            }

            $data = $request->validate([
                'empresa_id'       => 'nullable|exists:empresas,id',
                'puesto'           => 'nullable|string|max:100',
                'salario_mensual'  => 'required|numeric|min:0',
                'tipo_salario'     => 'required|in:Asalariado,Independiente,No hay datos',
                'estado_salario'   => 'required|in:Estable,Variable,Inestable',
                'recurrencia_pago' => 'required|in:Semanal,Quincenal,Mensual',
                'fecha_registro'   => 'nullable|date',
            ]);

            Log::debug('[LABORAL][UPDATE] validated', $data);

            // NO mandes NULL si viene vacío (columna NOT NULL)
            if (empty($data['fecha_registro'])) {
                unset($data['fecha_registro']);
            }

            $direccion = '';
            $telefono  = null;

            if (!empty($data['empresa_id'])) {
                $empresa = Empresa::find($data['empresa_id']);
                if (!$empresa) {
                    throw ValidationException::withMessages(['empresa_id' => 'Empresa no encontrada.']);
                }
                $direccion = $empresa->direccion ?? '';
                $telefono  = $empresa->telefono;
            }

            $data['tipo_salario_valor'] = self::MAP_TIPO_SALARIO[$data['tipo_salario']] ?? 0;
            $data['recurrencia_valor']  = self::MAP_RECURR_VALOR[$data['recurrencia_pago']] ?? 0;

            $data['id_usuario'] = Auth::id();
            $data['fecha']      = $data['fecha_registro'] ?? ($laboral->fecha ?? now());
            $data['direccion']  = $direccion;
            $data['telefono']   = $telefono;

            Log::debug('[LABORAL][UPDATE] payload', $data);

            $laboral->update($data);

            Log::info('[LABORAL][UPDATE] saved', [
                'laboral_id' => $laboral->id,
            ]);

            return $this->backToFicha($userData, 'Datos laborales actualizados correctamente.');
        } catch (\Throwable $e) {
            Log::error('[LABORAL][UPDATE] ERROR', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return back()->withInput()->with('error', 'No se pudo actualizar laborales. Revisa logs.');
        }
    }

    public function destroy(UserData $userData, UserLaboral $laboral)
    {
        try {
            if ((int)$laboral->id_cliente !== (int)$userData->id_cliente) {
                abort(404);
            }

            $laboral->delete();

            Log::info('[LABORAL][DESTROY] deleted', [
                'laboral_id' => $laboral->id,
                'id_cliente' => $userData->id_cliente,
            ]);

            return $this->backToFicha($userData, 'Registro laboral eliminado.');
        } catch (\Throwable $e) {
            Log::error('[LABORAL][DESTROY] ERROR', [
                'message' => $e->getMessage(),
            ]);

            return $this->backToFicha($userData, 'No se pudo eliminar laborales.', false);
        }
    }
}
