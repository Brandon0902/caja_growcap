<?php

namespace App\Http\Controllers;

use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreguntaController extends Controller
{
    public function index()
    {
        $preguntas = Pregunta::orderByDesc('id')->paginate(15);
        return view('preguntas.index', compact('preguntas'));
    }

    public function create()
    {
        return view('preguntas.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pregunta'  => 'required|string|max:255',
            'respuesta' => 'nullable|string',
            'categoria' => 'required|string|max:50',
            'img'       => 'nullable|image|max:2048',
            'status'    => 'required|in:0,1',
        ]);

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('preguntas', 'public');
        }

        $data['id_usuario'] = Auth::id();
        $data['fecha']      = now();

        Pregunta::create($data);

        return redirect()
            ->route('preguntas.index')
            ->with('success', 'Pregunta creada correctamente.');
    }

    public function show(Pregunta $pregunta)
    {
        return view('preguntas.show', compact('pregunta'));
    }

    public function edit(Pregunta $pregunta)
    {
        return view('preguntas.edit', compact('pregunta'));
    }

    public function update(Request $request, Pregunta $pregunta)
    {
        $data = $request->validate([
            'pregunta'  => 'required|string|max:255',
            'respuesta' => 'nullable|string',
            'categoria' => 'required|string|max:50',
            'img'       => 'nullable|image|max:2048',
            'status'    => 'required|in:0,1',
        ]);

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('preguntas', 'public');
        }

        $pregunta->update($data);

        return redirect()
            ->route('preguntas.index')
            ->with('success', 'Pregunta actualizada correctamente.');
    }

    public function destroy(Pregunta $pregunta)
    {
        $pregunta->delete();

        return redirect()
            ->route('preguntas.index')
            ->with('success', 'Pregunta eliminada correctamente.');
    }
}
