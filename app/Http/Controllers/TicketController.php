<?php
// app/Http/Controllers/TicketController.php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $search    = trim((string) $request->get('search', ''));
        $estado    = trim((string) $request->get('estado', ''));
        $prioridad = trim((string) $request->get('prioridad', ''));

        // ✅ OJO: NO existe categoria, por eso NO va en with()
        $q = Ticket::query()
            ->with(['cliente', 'usuario']);

        // 🔎 Búsqueda
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                if (ctype_digit($search)) {
                    $w->orWhere('id', (int) $search);
                }

                $w->orWhere('asunto', 'like', "%{$search}%")
                  ->orWhere('titulo', 'like', "%{$search}%");

                $w->orWhereHas('cliente', function ($cq) use ($search) {
                    $cq->where('nombre', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });

                $w->orWhereHas('usuario', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        // 🏷️ Estado (soporta BD con 'estado' string o 'status' numérico)
        $table = (new Ticket)->getTable();
        $hasEstado = Schema::hasColumn($table, 'estado');
        $hasStatus = Schema::hasColumn($table, 'status');

        if ($estado !== '') {
            if ($hasEstado) {
                $q->where('estado', $estado);
            } elseif ($hasStatus) {
                $map = [
                    'abierto'  => 0,
                    'progreso' => 1,
                    'resuelto' => 2,
                    'cerrado'  => 2,
                ];
                if (array_key_exists($estado, $map)) {
                    $q->where('status', $map[$estado]);
                }
            }
        }

        // ⚡ Prioridad (solo filtra si existe la columna)
        $hasPrioridad = Schema::hasColumn($table, 'prioridad');
        if ($prioridad !== '' && $hasPrioridad) {
            $q->where('prioridad', $prioridad);
        }

        $tickets = $q->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $viewData = compact('tickets', 'search', 'estado', 'prioridad');

        // ✅ Respuesta para AJAX
        $isAjax = $request->ajax() || $request->boolean('ajax');
        if ($isAjax) {
            return view('tickets.partials.results', $viewData);
        }

        return view('tickets.index', $viewData);
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
        $messages = [
            'area.required'   => 'El área es obligatoria.',
            'area.max'        => 'El área no puede exceder 255 caracteres.',

            'id_cliente.integer' => 'El cliente seleccionado no es válido.',
            'id_cliente.exists'  => 'El cliente seleccionado no existe.',

            'asunto.required' => 'El asunto es obligatorio.',
            'asunto.max'      => 'El asunto no puede exceder 255 caracteres.',

            'mensaje.required'=> 'El mensaje es obligatorio.',

            'adjunto.file'    => 'El adjunto debe ser un archivo válido.',
            'adjunto.max'     => 'El adjunto no debe pesar más de 4MB.',

            'fecha_seguimiento.date' => 'La fecha de seguimiento no es válida.',
            'fecha_cierre.date'      => 'La fecha de cierre no es válida.',

            'status.required' => 'Debes seleccionar un estado.',
            'status.in'       => 'El estado seleccionado no es válido.',
        ];

        $attributes = [
            'area'              => 'área',
            'id_cliente'        => 'cliente',
            'asunto'            => 'asunto',
            'mensaje'           => 'mensaje',
            'adjunto'           => 'adjunto',
            'fecha_seguimiento' => 'fecha de seguimiento',
            'fecha_cierre'      => 'fecha de cierre',
            'status'            => 'estado',
        ];

        $data = $request->validate([
            'area'              => 'required|string|max:255',
            'id_cliente'        => 'nullable|integer|exists:clientes,id',
            'asunto'            => 'required|string|max:255',
            'mensaje'           => 'required|string',
            'adjunto'           => 'nullable|file|max:4096',
            'fecha_seguimiento' => 'nullable|date',
            'fecha_cierre'      => 'nullable|date',
            'status'            => 'required|in:0,1,2',
        ], $messages, $attributes);

        // ✅ convertir "" a null en fechas opcionales (evita SQLSTATE[22007])
        $data['fecha_seguimiento'] = $request->filled('fecha_seguimiento') ? $request->input('fecha_seguimiento') : null;
        $data['fecha_cierre']      = $request->filled('fecha_cierre') ? $request->input('fecha_cierre') : null;

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
        $ticket->load(['cliente', 'usuario', 'respuestas']); // ✅ sin categoria
        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        $clientes = Cliente::where('status', 1)
            ->orderBy('nombre')
            ->get();

        return view('tickets.edit', compact('ticket', 'clientes'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $messages = [
            'area.required'   => 'El área es obligatoria.',
            'area.max'        => 'El área no puede exceder 255 caracteres.',

            'id_cliente.integer' => 'El cliente seleccionado no es válido.',
            'id_cliente.exists'  => 'El cliente seleccionado no existe.',

            'asunto.required' => 'El asunto es obligatorio.',
            'asunto.max'      => 'El asunto no puede exceder 255 caracteres.',

            'mensaje.required'=> 'El mensaje es obligatorio.',

            'adjunto.file'    => 'El adjunto debe ser un archivo válido.',
            'adjunto.max'     => 'El adjunto no debe pesar más de 4MB.',

            'fecha_seguimiento.date' => 'La fecha de seguimiento no es válida.',
            'fecha_cierre.date'      => 'La fecha de cierre no es válida.',

            'status.required' => 'Debes seleccionar un estado.',
            'status.in'       => 'El estado seleccionado no es válido.',
        ];

        $attributes = [
            'area'              => 'área',
            'id_cliente'        => 'cliente',
            'asunto'            => 'asunto',
            'mensaje'           => 'mensaje',
            'adjunto'           => 'adjunto',
            'fecha_seguimiento' => 'fecha de seguimiento',
            'fecha_cierre'      => 'fecha de cierre',
            'status'            => 'estado',
        ];

        $data = $request->validate([
            'area'              => 'required|string|max:255',
            'id_cliente'        => 'nullable|integer|exists:clientes,id',
            'asunto'            => 'required|string|max:255',
            'mensaje'           => 'required|string',
            'adjunto'           => 'nullable|file|max:4096',
            'fecha_seguimiento' => 'nullable|date',
            'fecha_cierre'      => 'nullable|date',
            'status'            => 'required|in:0,1,2',
        ], $messages, $attributes);

        // ✅ convertir "" a null
        $data['fecha_seguimiento'] = $request->filled('fecha_seguimiento') ? $request->input('fecha_seguimiento') : null;
        $data['fecha_cierre']      = $request->filled('fecha_cierre') ? $request->input('fecha_cierre') : null;

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

    public function downloadAttachment(Ticket $ticket)
    {
        abort_unless($ticket->adjunto, 404);
        return Storage::disk('tickets')->response($ticket->adjunto);
    }
}
