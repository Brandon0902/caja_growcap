<?php
// app/Http/Controllers/MensajeController.php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MensajeController extends Controller
{
    /** Lista */
    public function index()
    {
        $mensajes = Mensaje::with('cliente')
            ->orderByDesc('id')
            ->paginate(15);

        return view('mensajes.index', compact('mensajes'));
    }

    /** Form crear */
    public function create()
    {
        $clientes = Cliente::where('status', 1)->orderBy('nombre')->get();
        return view('mensajes.create', compact('clientes'));
    }

    /** Guardar */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo'        => 'required|integer',
            'id_cliente'  => 'nullable|integer|exists:clientes,id',
            'asunto'      => 'required|string|max:255',
            'cuerpo'      => 'required|string',
            'img'         => 'nullable|image|max:2048',
            'fecha_envio' => 'nullable|date',
            'status'      => 'required|in:0,1',
        ]);

        // Imagen (opcional)
        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('', 'mensajes');
        }

        // FECHA: si no viene, usar now(); jamás enviar '' a MySQL
        $fechaEnvio = $request->filled('fecha_envio')
            ? Carbon::parse($request->input('fecha_envio'))
            : null;

        // ---- MAPEO a columnas reales de la tabla ----
        $data['nombre']      = $data['asunto'];      // asunto -> nombre
        $data['descripcion'] = $data['cuerpo'];      // cuerpo -> descripcion
        unset($data['asunto'], $data['cuerpo'], $data['fecha_envio']);
        // ---------------------------------------------

        $data['id_usuario'] = Auth::id();
        $data['fecha']      = ($fechaEnvio?->format('Y-m-d H:i:s')) ?? now();

        Mensaje::create($data);

        return redirect()
            ->route('mensajes.index')
            ->with('success', 'Mensaje creado correctamente.');
    }

    /** Ver */
    public function show(Mensaje $mensaje)
    {
        return view('mensajes.show', compact('mensaje'));
    }

    /** Form editar */
    public function edit(Mensaje $mensaje)
    {
        $clientes = Cliente::where('status', 1)->orderBy('nombre')->get();
        return view('mensajes.edit', compact('mensaje', 'clientes'));
    }

    /** Actualizar */
    public function update(Request $request, Mensaje $mensaje)
    {
        $data = $request->validate([
            'tipo'        => 'required|integer',
            'id_cliente'  => 'nullable|integer|exists:clientes,id',
            'asunto'      => 'required|string|max:255',
            'cuerpo'      => 'required|string',
            'img'         => 'nullable|image|max:2048',
            'fecha_envio' => 'nullable|date',
            'status'      => 'required|in:0,1',
        ]);

        // Imagen nueva: borrar anterior y guardar la nueva
        if ($request->hasFile('img')) {
            if ($mensaje->img) {
                Storage::disk('mensajes')->delete($mensaje->img);
            }
            $data['img'] = $request->file('img')->store('', 'mensajes');
        }

        // FECHA: si viene vacía, conservar la anterior
        $fechaEnvio = $request->filled('fecha_envio')
            ? Carbon::parse($request->input('fecha_envio'))
            : null;

        // ---- MAPEO a columnas reales de la tabla ----
        $data['nombre']      = $data['asunto'];      // asunto -> nombre
        $data['descripcion'] = $data['cuerpo'];      // cuerpo -> descripcion
        unset($data['asunto'], $data['cuerpo'], $data['fecha_envio']);
        // ---------------------------------------------

        $data['id_usuario'] = Auth::id();
        $data['fecha_edit'] = now();
        $data['fecha']      = ($fechaEnvio?->format('Y-m-d H:i:s')) ?: ($mensaje->fecha ?? now());

        $mensaje->update($data);

        return redirect()
            ->route('mensajes.index')
            ->with('success', 'Mensaje actualizado correctamente.');
    }

    /** Eliminar */
    public function destroy(Mensaje $mensaje)
    {
        if ($mensaje->img) {
            Storage::disk('mensajes')->delete($mensaje->img);
        }
        $mensaje->delete();

        return redirect()
            ->route('mensajes.index')
            ->with('success', 'Mensaje eliminado.');
    }

    /** Ver imagen */
    public function viewImage(Mensaje $mensaje)
    {
        abort_unless($mensaje->img, 404);
        return Storage::disk('mensajes')->response($mensaje->img);
    }
}
