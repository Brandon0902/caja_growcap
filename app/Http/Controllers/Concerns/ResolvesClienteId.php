<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesClienteId
{
    /**
     * Obtiene el id del cliente ligado al usuario autenticado (Sanctum).
     * Soporta distintos esquemas: cliente_id, id_cliente, relación ->cliente,
     * o si el usuario autenticado ES el modelo Cliente.
     */
    protected function resolveClienteId(Request $request): int
    {
        $u = $request->user();

        // 1) Campos directos
        $id = data_get($u, 'cliente_id')
           ?? data_get($u, 'id_cliente');

        // 2) Si el usuario autenticado es un Cliente
        if (!$id && $u && \get_class($u) === \App\Models\Cliente::class) {
            $id = $u->getKey();
        }

        // 3) Relación cliente
        if (!$id) {
            $id = data_get($u, 'cliente.id');
        }

        abort_if(!$id, 403, 'El usuario no está ligado a un cliente.');
        return (int) $id;
    }

    protected function assertOwnsTicket(Request $request, \App\Models\Ticket $ticket): void
    {
        $cid = $this->resolveClienteId($request);
        abort_if((int)$ticket->id_cliente !== $cid, 403, 'No autorizado.');
    }
}
