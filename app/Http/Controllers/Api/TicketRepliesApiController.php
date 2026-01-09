<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesClienteId;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketRepliesApiController extends Controller
{
    use ResolvesClienteId;

    // GET /api/cliente/tickets/{ticket}/replies
    public function index(Request $request, Ticket $ticket)
    {
        $this->assertOwnsTicket($request, $ticket);

        $ticket->load([
            'respuestas.cliente',
            'respuestas.usuario',
            'respuestas.children.cliente',
            'respuestas.children.usuario',
            'respuestas.children.children.cliente',
            'respuestas.children.children.usuario',
        ]);

        return response()->json([
            'ticket_id' => $ticket->id,
            'replies'   => $ticket->respuestas,
        ]);
    }
}
