<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;

class ProveedorResolver
{
    /**
     * Devuelve el id_proveedor correspondiente a un cliente.
     * Si no existe, lo crea a partir de los datos del cliente.
     */
    public function ensureFromCliente(int $clienteId): int
    {
        $cli = Cliente::findOrFail($clienteId);

        $nombreCompleto = trim(($cli->nombre ?? '') . ' ' . ($cli->apellido ?? ''));
        $email          = $cli->email ?: null;

        // Dirección: clientes -> user_data -> 'N/D'
        $direccion = $cli->direccion ?? null;

        if ($direccion === null || trim((string)$direccion) === '') {
            $direccion = DB::table('user_data')
                ->where('id_cliente', $cli->id)   // O la PK correcta del cliente
                ->value('direccion');
        }

        $direccion = trim((string)($direccion ?? ''));
        if ($direccion === '') {
            $direccion = 'N/D';
        }

        // Teléfono: clientes -> user_data -> ''
        $telefono = $cli->telefono ?? DB::table('user_data')
            ->where('id_cliente', $cli->id)
            ->value('telefono');

        $telefono = (string)($telefono ?? '');

        // 1) Intentar localizar proveedor existente
        $prov = null;
        if ($email) {
            $prov = Proveedor::where('email', $email)->first();
        }
        if (!$prov) {
            $prov = Proveedor::where('nombre', $nombreCompleto)
                ->where('telefono', $telefono)
                ->first();
        }

        // 2) Si no existe, crearlo
        if (!$prov) {
            $prov = Proveedor::create([
                'nombre'    => ($nombreCompleto !== '') ? $nombreCompleto : ($cli->nombre ?? 'Cliente'),
                'direccion' => $direccion,   // nunca null
                'telefono'  => $telefono,    // nunca null si la columna es NOT NULL
                'email'     => $email ?? '', // idem
                'contacto'  => $nombreCompleto ?: 'Contacto',
                'estado'    => 'activo',
            ]);
        }

        return (int) $prov->id_proveedor;
    }
}
