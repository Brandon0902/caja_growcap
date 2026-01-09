<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * =============================
     *  FLUJO POR TOKEN (Bearer)
     *  Rutas en routes/api.php:
     *    POST /auth/login       -> login()
     *    POST /auth/logout      -> logout()
     *    POST /auth/logout-all  -> logoutAll()
     *    GET  /me               -> me()
     * =============================
     */

    /**
     * POST /auth/login
     * Autentica contra "clientes" y emite un token Bearer (Sanctum).
     * Acepta en "email" un email, user o codigo_cliente.
     * Opcionales:
     *  - device: nombre del dispositivo (default: "cliente-web")
     *  - single: bool para revocar tokens previos del usuario
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'string', 'max:255'], // email | user | codigo_cliente
            'password' => ['required', 'string'],
            'device'   => ['nullable', 'string', 'max:100'],
            'single'   => ['nullable', 'boolean'],
        ]);

        $login = $data['email'];

        /** @var Cliente|null $cliente */
        $cliente = Cliente::query()
            ->where('email', $login)
            ->orWhere('user', $login)
            ->orWhere('codigo_cliente', $login)
            ->first();

        if (! $cliente) {
            throw ValidationException::withMessages([
                'email' => ['Cliente no encontrado.'],
            ]);
        }

        // =============================
        // Validación de contraseña
        // - bcrypt/argon (nuevo)
        // - legacy (sistema viejo): SHA1(MD5(password))
        // - (opcional) texto plano
        // =============================
        $plain  = (string) $data['password'];
        $stored = strtolower(trim((string) ($cliente->pass ?? '')));

        $valid = false;

        // 1) Hash moderno (bcrypt/argon)
        if (
            str_starts_with($stored, '$2y$') ||
            str_starts_with($stored, '$2a$') ||
            str_starts_with($stored, '$2b$') ||
            str_starts_with($stored, '$argon2i$') ||
            str_starts_with($stored, '$argon2id$')
        ) {
            $valid = Hash::check($plain, $stored);
        }
        // 2) Legacy: SHA1(MD5(password)) -> 40 hex
        elseif (preg_match('/^[a-f0-9]{40}$/', $stored)) {
            $valid = hash_equals($stored, sha1(md5($plain)));

            // ✅ si fue válido, migramos a bcrypt para ya no batallar
            if ($valid) {
                $cliente->pass = Hash::make($plain);
                $cliente->save();
            }
        }
        // 3) (Opcional) Texto plano heredado (si existiera)
        elseif ($stored !== '' && hash_equals($stored, $plain)) {
            $valid = true;

            // ✅ migrar a bcrypt también
            $cliente->pass = Hash::make($plain);
            $cliente->save();
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son válidas.'],
            ]);
        }

        // Bloqueo por estado (1 = activo)
        if ((int) $cliente->status !== 1) {
            throw ValidationException::withMessages([
                'email' => ['El cliente está inactivo.'],
            ]);
        }

        // Opcional: “una sesión por dispositivo/usuario”
        if (!empty($data['single'])) {
            $cliente->tokens()->delete();
        }

        // Scopes/habilidades opcionales para el token
        $abilities = ['access-api'];

        // Dispositivo nominal
        $device = $data['device'] ?? 'cliente-web';

        // Crear token personal
        $newToken = $cliente->createToken($device, $abilities);

        // TTL (segundos) si definiste SANCTUM_EXPIRATION (minutos) en .env
        $expiresIn = config('sanctum.expiration')
            ? (int) config('sanctum.expiration') * 60
            : null;

        return response()->json([
            'token_type'   => 'Bearer',
            'access_token' => $newToken->plainTextToken,
            'expires_in'   => $expiresIn,
            'user'         => $this->userPayload($cliente),
        ]);
    }

    /**
     * GET /me
     * Devuelve el usuario autenticado (vía Bearer).
     */
    public function me(Request $request)
    {
        /** @var Cliente|null $c */
        $c = $request->user();

        if (! $c) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        return response()->json([
            'status' => 'ok',
            'user'   => $this->userPayload($c),
        ]);
    }

    /**
     * POST /auth/logout
     * Revoca el token actual (Bearer).
     */
    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'status'  => 'ok',
            'message' => 'Sesión cerrada.',
        ]);
    }

    /**
     * POST /auth/logout-all
     * Revoca todos los tokens del usuario (cierra sesiones en todos los dispositivos).
     */
    public function logoutAll(Request $request)
    {
        $request->user()?->tokens()->delete();

        return response()->json([
            'status'  => 'ok',
            'message' => 'Todos los dispositivos cerrados.',
        ]);
    }

    /**
     * Mapea el payload de usuario para el frontend.
     */
    protected function userPayload(Cliente $c): array
    {
        return [
            'id'             => $c->id,
            'nombre'         => $c->nombre,
            'apellido'       => $c->apellido,
            'email'          => $c->email,
            'codigo_cliente' => $c->codigo_cliente,
            'user'           => $c->user,
            'tipo'           => $c->tipo,
            'status'         => $c->status,
            'id_sucursal'    => $c->id_sucursal ?? null,
        ];
    }
}
