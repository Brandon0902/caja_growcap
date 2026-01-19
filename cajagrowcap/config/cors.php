<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths con CORS habilitado
    |--------------------------------------------------------------------------
    | Solo exponemos la API. Ya no usamos sanctum/csrf-cookie ni rutas de sesión.
    */
    'paths' => [
        'api/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Métodos permitidos
    |--------------------------------------------------------------------------
    */
    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Orígenes permitidos
    |--------------------------------------------------------------------------
    | Producción: dominio del cliente. (Agrega tus orígenes de dev si los usas)
    */
    'allowed_origins' => [
        'https://clientegrowcap.casabarrel.com',
        // 'http://localhost:5173',
        // 'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Headers permitidos
    |--------------------------------------------------------------------------
    | '*' incluye Authorization, Content-Type, etc.
    */
    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Headers expuestos al cliente
    |--------------------------------------------------------------------------
    | Déjalo vacío salvo que necesites leer headers concretos desde el front.
    */
    'exposed_headers' => [
        // 'Authorization',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache del preflight (segundos)
    |--------------------------------------------------------------------------
    */
    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Cookies / credenciales
    |--------------------------------------------------------------------------
    | Para Bearer tokens NO usamos cookies → false.
    */
    'supports_credentials' => false,
];
