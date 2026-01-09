<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class MisDatosApiController extends Controller
{
    /* -----------------------------------------------------------------
     | Util: primera columna existente en una tabla
     * -----------------------------------------------------------------*/
    protected function pickCol(string $table, array $candidates): ?string
    {
        if (!Schema::hasTable($table)) return null;
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }

    /* -----------------------------------------------------------------
     | Resolver cliente según usuario autenticado
     * -----------------------------------------------------------------*/
    protected function resolveClienteForAuth(): ?Cliente
    {
        $user = Auth::user();
        if (!$user) return null;

        // Si el tokenable YA es Cliente, úsalo directo
        if ($user instanceof Cliente) {
            return $user;
        }

        $tbl = (new Cliente)->getTable();

        // Empate por user_id / id_usuario si existe en clientes
        if ($col = $this->pickCol($tbl, ['user_id', 'id_usuario'])) {
            if ($cli = Cliente::where($col, $user->id)->first()) return $cli;
        }

        // Fallback por email si existe en clientes
        if (Schema::hasColumn($tbl, 'email') && !empty($user->email)) {
            if ($cli = Cliente::where('email', $user->email)->first()) return $cli;
        }

        // Último recurso: buscar por id
        return Cliente::find($user->id);
    }

    /* -----------------------------------------------------------------
     | GET /api/cliente/mis-datos
     * -----------------------------------------------------------------*/
    public function me(Request $request)
    {
        $reqId   = $request->header('X-Request-ID', (string) Str::uuid());
        $cliente = $this->resolveClienteForAuth();

        if (!$cliente) {
            Log::warning('API MisDatos me: cliente no encontrado', [
                'req_id'  => $reqId,
                'user_id' => (is_object($request->user()) ? ($request->user()->id ?? null) : null),
            ]);
            return response()->json(['message' => 'Cliente no encontrado.', 'req_id' => $reqId], 404);
        }

        $cliente->load(['userData.estado:id,nombre', 'userData.municipio:id,nombre', 'userData']);
        $ud = $cliente->userData;

        return response()->json([
            'req_id'  => $reqId,
            'cliente' => [
                'id'       => $cliente->id,
                'nombre'   => $cliente->nombre,
                'apellido' => $cliente->apellido,
                'email'    => $cliente->email,
                'telefono' => $cliente->telefono ?? null,
            ],
            'user_data' => $ud ? [
                'id'                        => $ud->id,
                'id_estado'                 => $ud->id_estado,
                'estado_nombre'             => optional($ud->estado)->nombre,
                'id_municipio'              => $ud->id_municipio,
                'municipio_nombre'          => optional($ud->municipio)->nombre,
                'rfc'                       => $ud->rfc,
                'direccion'                 => $ud->direccion,
                'colonia'                   => $ud->colonia,
                'cp'                        => $ud->cp,
                'beneficiario'              => $ud->beneficiario,
                'beneficiario_telefono'     => $ud->beneficiario_telefono,
                'beneficiario_02'           => $ud->beneficiario_02,
                'beneficiario_telefono_02'  => $ud->beneficiario_telefono_02,
                'banco'                     => $ud->banco,
                'cuenta'                    => $ud->cuenta,
                'nip'                       => $ud->nip,
                'fecha_alta'                => optional($ud->fecha_alta)->toDateString(),
                'fecha_modificacion'        => optional($ud->fecha_modificacion)->toDateString(),
                'status'                    => $ud->status,
                'porcentaje_1'              => $ud->porcentaje_1,
                'porcentaje_2'              => $ud->porcentaje_2,
                'fecha_ingreso'             => optional($ud->fecha_ingreso)->toDateString(),
            ] : null,
        ]);
    }

    /* -----------------------------------------------------------------
     | PUT/PATCH /api/cliente/mis-datos
     * -----------------------------------------------------------------*/
    public function upsert(Request $request)
    {
        $reqId   = $request->header('X-Request-ID', (string) Str::uuid());
        $cliente = $this->resolveClienteForAuth();

        if (!$cliente) {
            Log::warning('API MisDatos upsert: cliente no encontrado', [
                'req_id'  => $reqId,
                'user_id' => (is_object($request->user()) ? ($request->user()->id ?? null) : null),
            ]);
            return response()->json(['message' => 'Cliente no encontrado.', 'req_id' => $reqId], 404);
        }

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

            // ✅ NIP: exactamente 4 dígitos (y solo se permite setear si aún no existe)
            'nip'                      => ['nullable','string','regex:/^\d{4}$/'],

            'fecha_alta'               => 'nullable|date',
            'fecha_modificacion'       => 'nullable|date',
            'status'                   => 'nullable|in:0,1',
            'porcentaje_1'             => 'nullable|numeric|min:0|max:100',
            'porcentaje_2'             => 'nullable|numeric|min:0|max:100',
            'fecha_ingreso'            => 'nullable|date',
        ];

        $data = $request->validate($rules);

        // Validación de porcentajes (si alguno viene, deben sumar 100)
        if ($request->filled('porcentaje_1') || $request->filled('porcentaje_2')) {
            $sum = (float) $request->input('porcentaje_1', 0) + (float) $request->input('porcentaje_2', 0);
            if (abs($sum - 100.0) > 0.0001) {
                Log::info('API MisDatos upsert: porcentajes inválidos', ['req_id' => $reqId, 'sum' => $sum]);
                return response()->json([
                    'errors' => ['porcentaje_2' => ['La suma de porcentajes debe ser 100 %.']],
                    'req_id' => $reqId,
                ], 422);
            }
        }

        try {
            $user   = $request->user(); // Sanctum tokenable
            $userId = is_object($user)
                ? (method_exists($user, 'getKey') ? $user->getKey() : ($user->id ?? null))
                : null;

            if ($userId) {
                $data['id_usuario'] = $userId;
            } elseif (!isset($data['id_usuario']) && isset($cliente->id_usuario)) {
                $data['id_usuario'] = $cliente->id_usuario;
            }

            // ✅ Regla: si ya existe NIP, no se permite cambiar (ni re-enviar)
            $cliente->load('userData');
            $existingNip = trim((string) optional($cliente->userData)->nip);

            if ($existingNip !== '') {
                if ($request->filled('nip')) {
                    Log::info('API MisDatos upsert: intento de modificar NIP bloqueado', [
                        'req_id'     => $reqId,
                        'id_cliente' => $cliente->id,
                    ]);

                    return response()->json([
                        'errors' => ['nip' => ['Ya existe un NIP registrado. Por seguridad no puedes modificarlo desde aquí.']],
                        'req_id' => $reqId,
                    ], 422);
                }

                // por si acaso viene en payload sin filled (o por manipulación)
                unset($data['nip']);
            }

            // Log seguro (enmascarar datos sensibles)
            $masked = $data;
            if (isset($masked['nip']))    $masked['nip']    = '****';
            if (isset($masked['cuenta'])) $masked['cuenta'] = '***masked***';

            Log::info('API MisDatos upsert: updateOrCreate start', [
                'req_id'     => $reqId,
                'id_cliente' => $cliente->id,
                'payload'    => $masked,
            ]);

            $cliente->userData()->updateOrCreate(
                ['id_cliente' => $cliente->id],
                $data
            );

            Log::info('API MisDatos upsert: ok', ['req_id' => $reqId, 'id_cliente' => $cliente->id]);

            // Responder igual que GET
            $cliente->load(['userData.estado:id,nombre','userData.municipio:id,nombre','userData']);
            $ud = $cliente->userData;

            return response()->json([
                'req_id'  => $reqId,
                'cliente' => [
                    'id'       => $cliente->id,
                    'nombre'   => $cliente->nombre,
                    'apellido' => $cliente->apellido,
                    'email'    => $cliente->email,
                    'telefono' => $cliente->telefono ?? null,
                ],
                'user_data' => $ud ? [
                    'id'                        => $ud->id,
                    'id_estado'                 => $ud->id_estado,
                    'estado_nombre'             => optional($ud->estado)->nombre,
                    'id_municipio'              => $ud->id_municipio,
                    'municipio_nombre'          => optional($ud->municipio)->nombre,
                    'rfc'                       => $ud->rfc,
                    'direccion'                 => $ud->direccion,
                    'colonia'                   => $ud->colonia,
                    'cp'                        => $ud->cp,
                    'beneficiario'              => $ud->beneficiario,
                    'beneficiario_telefono'     => $ud->beneficiario_telefono,
                    'beneficiario_02'           => $ud->beneficiario_02,
                    'beneficiario_telefono_02'  => $ud->beneficiario_telefono_02,
                    'banco'                     => $ud->banco,
                    'cuenta'                    => $ud->cuenta,
                    'nip'                       => $ud->nip,
                    'fecha_alta'                => optional($ud->fecha_alta)->toDateString(),
                    'fecha_modificacion'        => optional($ud->fecha_modificacion)->toDateString(),
                    'status'                    => $ud->status,
                    'porcentaje_1'              => $ud->porcentaje_1,
                    'porcentaje_2'              => $ud->porcentaje_2,
                    'fecha_ingreso'             => optional($ud->fecha_ingreso)->toDateString(),
                ] : null,
            ]);

        } catch (\Throwable $e) {
            Log::error('API MisDatos upsert exception', [
                'req_id'     => $reqId,
                'id_cliente' => $cliente->id ?? null,
                'ex'         => $e,
            ]);

            return response()->json([
                'message' => 'Error interno al guardar los datos.',
                'req_id'  => $reqId,
            ], 500);
        }
    }

    /* -----------------------------------------------------------------
     | POST /api/cliente/mis-datos/password
     | - Cambiar contraseña del cliente autenticado (Sanctum)
     | ✅ USA CAMPO REAL: clientes.pass
     * -----------------------------------------------------------------*/
    public function updatePassword(Request $request)
    {
        $reqId   = $request->header('X-Request-ID', (string) Str::uuid());
        $cliente = $this->resolveClienteForAuth();

        if (!$cliente) {
            return response()->json(['message' => 'No autenticado.', 'req_id' => $reqId], 401);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $current = $cliente->pass ?? null;

        if (!$current) {
            return response()->json([
                'errors' => ['current_password' => ['No hay contraseña registrada para este usuario.']],
                'req_id' => $reqId,
            ], 422);
        }

        // Compatibilidad: si está hasheada => Hash::check; si es legado en texto plano => comparación directa
        $isHashed = is_string($current) && (
            str_starts_with($current, '$2y$') ||
            str_starts_with($current, '$argon2i$') ||
            str_starts_with($current, '$argon2id$')
        );

        $ok = $isHashed
            ? Hash::check($data['current_password'], $current)
            : hash_equals((string) $current, (string) $data['current_password']);

        if (!$ok) {
            return response()->json([
                'errors' => ['current_password' => ['La contraseña actual no es correcta.']],
                'req_id' => $reqId,
            ], 422);
        }

        try {
            // ✅ Recomendado: guardar hasheada
            // ⚠️ Si tu login todavía compara texto plano, ajusta login a Hash::check()
            $cliente->pass = Hash::make($data['password']);
            $cliente->save();

            Log::info('API MisDatos updatePassword: ok', [
                'req_id'     => $reqId,
                'cliente_id' => $cliente->id,
            ]);

            return response()->json([
                'message' => 'Contraseña actualizada correctamente.',
                'req_id'  => $reqId,
            ]);

        } catch (\Throwable $e) {
            Log::error('API MisDatos updatePassword exception', [
                'req_id' => $reqId,
                'ex'     => $e,
            ]);

            return response()->json([
                'message' => 'Error interno al actualizar la contraseña.',
                'req_id'  => $reqId,
            ], 500);
        }
    }

    /* -----------------------------------------------------------------
     | GET /api/cliente/cat/estados
     * -----------------------------------------------------------------*/
    public function estados()
    {
        return response()->json(
            Estado::when(Schema::hasColumn('estados', 'status'), fn($q) => $q->where('status', 1))
                ->select('id','nombre')
                ->orderBy('nombre')
                ->get()
        );
    }

    /* -----------------------------------------------------------------
     | GET /api/cliente/cat/estados/{idEstado}/municipios
     * -----------------------------------------------------------------*/
    public function municipios($idEstado)
    {
        return response()->json(
            Municipio::where('id_estado', $idEstado)
                ->select('id','nombre')
                ->orderBy('nombre')
                ->get()
        );
    }
}
