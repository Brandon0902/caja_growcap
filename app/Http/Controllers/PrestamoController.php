<?php

// app/Http/Controllers/PrestamoController.php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrestamoController extends Controller
{
    public function index()
    {
        $prestamos = Prestamo::with('usuario')
                     ->where('status','!=','0')
                     ->orderByDesc('id_prestamo')
                     ->paginate(15);

        return view('adminprestamos.index', compact('prestamos'));
    }

    public function create()
    {
        return view('adminprestamos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'periodo'       => 'required|string|max:255',
            'semanas'       => 'required|integer|min:1',
            'interes'       => 'required|numeric|min:0',
            'monto_minimo'  => 'required|numeric|min:0',
            'monto_maximo'  => 'required|numeric|gte:monto_minimo',
            'antiguedad'    => 'required|integer|min:0',
        ]);

        $data['id_usuario'] = Auth::id();
        $data['status']     = '1';

        Prestamo::create($data);

        return redirect()
            ->route('prestamos.index')
            ->with('success','Tipo de préstamo creado correctamente.');
    }

    public function show(Prestamo $prestamo)
    {
        return view('adminprestamos.show', compact('prestamo'));
    }

    public function edit(Prestamo $prestamo)
    {
        return view('adminprestamos.edit', compact('prestamo'));
    }

    public function update(Request $request, Prestamo $prestamo)
    {
        $data = $request->validate([
            'periodo'       => 'required|string|max:255',
            'semanas'       => 'required|integer|min:1',
            'interes'       => 'required|numeric|min:0',
            'monto_minimo'  => 'required|numeric|min:0',
            'monto_maximo'  => 'required|numeric|gte:monto_minimo',
            'antiguedad'    => 'required|integer|min:0',
            'status'        => 'required|in:1,2,3,4',
        ]);

        $data['id_usuario'] = Auth::id();
        $prestamo->update($data);

        return redirect()
            ->route('prestamos.index')
            ->with('success','Tipo de préstamo actualizado correctamente.');
    }

    public function destroy(Prestamo $prestamo)
    {
        // en lugar de borrar, marcamos status=0
        $prestamo->update([
            'status'     => '0',
            'id_usuario' => Auth::id(),
        ]);

        return redirect()
            ->route('prestamos.index')
            ->with('success','Tipo de préstamo desactivado correctamente.');
    }
}

