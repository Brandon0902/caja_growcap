<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesClienteId;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NuevaSolicitudNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketsApiController extends Controller
{
    use ResolvesClienteId;

    // GET /api/cliente/tickets
    public function index(Request $request)
    {
        $clienteId = $this->resolveClienteId($request);

        $tickets = Ticket::with('cliente')
            ->where('id_cliente', $clienteId)
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($tickets);
    }

    // POST /api/cliente/tickets
    public function store(Request $request)
    {
        $clienteId = $this->resolveClienteId($request);

        $data = $request->validate([
            'area'              => 'required|string|max:255',
            'asunto'            => 'required|string|max:255',
            'mensaje'           => 'required|string',
            'adjunto'           => 'nullable|file|max:4096',
            'fecha_seguimiento' => 'nullable|date',
        ]);

        // Propietario del ticket: el cliente autenticado
        $data['id_cliente'] = $clienteId;

        // MUY IMPORTANTE:
        // No establecemos id_usuario cuando el creador es el CLIENTE
        // (evita llamar Auth::id() sobre modelos no-Authenticatable como Cliente)
        $data['id_usuario'] = null;

        $data['fecha']  = now();
        $data['status'] = 0;

        if ($request->hasFile('adjunto')) {
            $data['adjunto'] = $request->file('adjunto')->store('', 'tickets');
        }

        $ticket = Ticket::create($data);
        $ticket->load('cliente');

        $clienteNombre = trim(sprintf('%s %s', (string)($ticket->cliente->nombre ?? ''), (string)($ticket->cliente->apellido ?? '')));
        $titulo = 'Nuevo ticket de soporte';
        $mensaje = $clienteNombre !== '' ? "Cliente {$clienteNombre} creó un ticket." : 'Se creó un nuevo ticket.';
        $url = route('tickets.show', $ticket);

        User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
            $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
        });

        return response()->json([
            'message' => 'Ticket creado correctamente.',
            'data'    => $ticket->fresh('cliente'),
        ], 201);
    }

    // GET /api/cliente/tickets/{ticket}
    public function show(Request $request, Ticket $ticket)
    {
        $this->assertOwnsTicket($request, $ticket);

        $ticket->load('cliente');
        return response()->json($ticket);
    }

    // GET /api/cliente/tickets/{ticket}/adjunto
    public function downloadAttachment(Request $request, Ticket $ticket)
    {
        $this->assertOwnsTicket($request, $ticket);
        abort_unless($ticket->adjunto, 404);

        return Storage::disk('tickets')->response($ticket->adjunto);
    }
}
