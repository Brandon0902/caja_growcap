<?php
// app/Http/Controllers/TicketRespuestaController.php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketRespuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketRespuestaController extends Controller
{
    /**
     * Almacena una respuesta en un ticket y actualiza su estado.
     */
    public function store(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'respuesta' => 'required|string',
            'parent_id' => 'nullable|integer|exists:ticket_respuestas,id',
            'status'    => 'required|in:0,1,2',
        ]);

        // 1) Crear la respuesta
        $respuesta = new TicketRespuesta([
            'ticket_id'  => $ticket->id,
            'parent_id'  => $data['parent_id'] ?? null,
            'id_cliente' => $ticket->id_cliente,
            'id_usuario' => Auth::id(),
            'respuesta'  => $data['respuesta'],
            'fecha'      => now(),
        ]);
        $respuesta->save();

        // 2) Actualizar el estado del ticket
        $ticket->status = $data['status'];
        $ticket->save();

        return back()->with('success', 'Respuesta agregada y estado actualizado.');
    }

    /**
     * Elimina una respuesta.
     */
    public function destroy(TicketRespuesta $respuesta)
    {
        $respuesta->delete();

        return back()->with('success', 'Respuesta eliminada correctamente.');
    }
}
