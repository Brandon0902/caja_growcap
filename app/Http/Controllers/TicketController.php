<?php
// app/Http/Controllers/TicketController.php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with('cliente')
                         ->orderByDesc('id')
                         ->paginate(15);

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        $clientes = Cliente::where('status', 1)
                           ->orderBy('nombre')
                           ->get();

        return view('tickets.create', compact('clientes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'area'             => 'required|string|max:255',
            'id_cliente'       => 'nullable|integer|exists:clientes,id',
            'asunto'           => 'required|string|max:255',
            'mensaje'          => 'required|string',
            'adjunto'          => 'nullable|file|max:4096',
            'fecha_seguimiento'=> 'nullable|date',
            'fecha_cierre'     => 'nullable|date',
            'status'           => 'required|in:0,1,2',
        ]);

        // manejo de adjunto
        if ($request->hasFile('adjunto')) {
            $data['adjunto'] = $request->file('adjunto')->store('', 'tickets');
        }

        $data['id_usuario'] = Auth::id();
        $data['fecha']      = now();

        Ticket::create($data);

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket creado correctamente.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['cliente', 'respuestas']);
        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        $clientes = Cliente::where('status', 1)
                           ->orderBy('nombre')
                           ->get();

        return view('tickets.edit', compact('ticket','clientes'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'area'             => 'required|string|max:255',
            'id_cliente'       => 'nullable|integer|exists:clientes,id',
            'asunto'           => 'required|string|max:255',
            'mensaje'          => 'required|string',
            'adjunto'          => 'nullable|file|max:4096',
            'fecha_seguimiento'=> 'nullable|date',
            'fecha_cierre'     => 'nullable|date',
            'status'           => 'required|in:0,1,2',
        ]);

        if ($request->hasFile('adjunto')) {
            if ($ticket->adjunto) {
                Storage::disk('tickets')->delete($ticket->adjunto);
            }
            $data['adjunto'] = $request->file('adjunto')->store('', 'tickets');
        }

        $data['id_usuario'] = Auth::id();
        $data['fecha_edit'] = now();

        $ticket->update($data);

        return back()->with('success', 'Ticket actualizado correctamente.');
    }

    public function destroy(Ticket $ticket)
    {
        if ($ticket->adjunto) {
            Storage::disk('tickets')->delete($ticket->adjunto);
        }
        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket eliminado correctamente.');
    }

    /**
     * Descargar o mostrar inline el adjunto.
     */
    public function downloadAttachment(Ticket $ticket)
    {
        abort_unless($ticket->adjunto, 404);
        return Storage::disk('tickets')->response($ticket->adjunto);
    }
}
