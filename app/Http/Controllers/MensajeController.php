<?php
// app/Http/Controllers/MensajeController.php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MensajeController extends Controller
{
    public function index()
    {
        $mensajes = Mensaje::with('cliente')
            ->orderByDesc('id')
            ->paginate(15);

        return view('mensajes.index', compact('mensajes'));
    }

    public function create()
    {
        $clientes = Cliente::where('status',1)->orderBy('nombre')->get();
        return view('mensajes.create', compact('clientes'));
    }

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

        // manejo de imagen
        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('', 'mensajes');
        }

        $data['id_usuario'] = Auth::id();
        $data['fecha']      = $data['fecha_envio'] ?? now();
        unset($data['fecha_envio']);

        Mensaje::create($data);

        return redirect()->route('mensajes.index')
                         ->with('success','Mensaje creado correctamente.');
    }

    public function show(Mensaje $mensaje)
    {
        return view('mensajes.show', compact('mensaje'));
    }

    public function edit(Mensaje $mensaje)
    {
        $clientes = Cliente::where('status',1)->orderBy('nombre')->get();
        return view('mensajes.edit', compact('mensaje','clientes'));
    }

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

        // imagen nueva: borrar la antigua
        if ($request->hasFile('img')) {
            if ($mensaje->img) {
                Storage::disk('mensajes')->delete($mensaje->img);
            }
            $data['img'] = $request->file('img')->store('', 'mensajes');
        }

        $data['id_usuario'] = Auth::id();
        $data['fecha_edit'] = now();
        $data['fecha']      = $data['fecha_envio'] ?? $mensaje->fecha;
        unset($data['fecha_envio']);

        $mensaje->update($data);

        return back()->with('success','Mensaje actualizado correctamente.');
    }

    public function destroy(Mensaje $mensaje)
    {
        if ($mensaje->img) {
            Storage::disk('mensajes')->delete($mensaje->img);
        }
        $mensaje->delete();

        return redirect()->route('mensajes.index')
                         ->with('success','Mensaje eliminado.');
    }

    public function viewImage(Mensaje $mensaje)
    {
        abort_unless($mensaje->img, 404);
        return Storage::disk('mensajes')->response($mensaje->img);
    }
}
