<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class ClienteMailService
{
    /**
     * Resuelve el cliente autenticado (Sanctum) desde un Request.
     * Útil en controladores de la API.
     */
    public function clienteFromRequest(Request $request): Cliente
    {
        $u = auth('sanctum')->user() ?? $request->user();

        // Token directo de cliente
        if ($u instanceof Cliente) {
            return $u;
        }

        // Caso: el token pertenece a otra tabla pero tiene id_cliente
        if ($u && isset($u->id_cliente) && $u->id_cliente) {
            $c = Cliente::find($u->id_cliente);
            if ($c) {
                return $c;
            }
        }

        throw new AuthenticationException('El token no corresponde a un cliente.');
    }

    /**
     * Cargar un cliente por ID o devolver null si no existe.
     * Útil cuando solo tienes el id_cliente en la BD.
     */
    public function clienteFromId(int $clienteId): ?Cliente
    {
        return Cliente::find($clienteId);
    }

    /**
     * Nombre completo del cliente (con fallback).
     */
    public function nombreCompleto(Cliente $cliente): string
    {
        $nombre = trim((string)($cliente->nombre ?? ''));
        $ap     = trim((string)($cliente->apellido ?? ''));

        $full = trim($nombre . ' ' . $ap);

        return $full !== '' ? $full : 'Cliente ' . $cliente->id;
    }

    /**
     * Paquete básico de datos para usar en cualquier correo.
     */
    public function mailData(Cliente $cliente): array
    {
        return [
            'id'              => (int) $cliente->id,
            'nombre'          => (string) ($cliente->nombre ?? ''),
            'apellido'        => (string) ($cliente->apellido ?? ''),
            'nombre_completo' => $this->nombreCompleto($cliente),
            'email'           => (string) ($cliente->email ?? ''),
            'codigo_cliente'  => (string) ($cliente->codigo_cliente ?? ''),
            'user'            => (string) ($cliente->user ?? ''),
        ];
    }
}
