<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | web  -> panel/admin con sesiones contra App\Models\User
    | api  -> Bearer tokens (Sanctum) contra App\Models\Cliente
    |
    */
    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // Guard API usando Sanctum y el provider de clientes
        'api' => [
            'driver'   => 'sanctum',
            'provider' => 'clientes',
        ],

        // (Opcional) alias explícito "sanctum" apuntando a clientes
        'sanctum' => [
            'driver'   => 'sanctum',
            'provider' => 'clientes',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | "users"     -> para el backend/admin (App\Models\User)
    | "clientes"  -> para el cliente (App\Models\Cliente) **Autenticable + HasApiTokens**
    |
    */
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        'clientes' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Cliente::class,
        ],

        // Si quisieras provider por tabla:
        // 'clientes' => ['driver' => 'database', 'table' => 'clientes'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    |
    | Si más adelante implementas "olvide mi contraseña" para clientes,
    | puedes duplicar el broker apuntando a 'clientes'.
    |
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],

        // (Opcional) broker para clientes:
        // 'clientes' => [
        //     'provider' => 'clientes',
        //     'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
        //     'expire'   => 60,
        //     'throttle' => 60,
        // ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
