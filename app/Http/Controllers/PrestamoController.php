<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status', '');   // '' = todos, '1' = activo, '0' = inactivo
        $desde  = $request->input('desde', '');
        $hasta  = $request->input('hasta', '');
        $orden  = $request->input('orden', 'fecha_desc');

        // Solo dos estados ahora
        $statusOptions = [
            ''  => 'Todos',
            '1' => 'Activo',
            '0' => 'Inactivo',
        ];

        $q = Prestamo::query();

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('periodo', 'like', "%{$search}%")
                  ->orWhere('semanas', 'like', "%{$search}%")
                  ->orWhere('interes', 'like', "%{$search}%")
                  ->orWhere('monto_minimo', 'like', "%{$search}%")
                  ->orWhere('monto_maximo', 'like', "%{$search}%")
                  ->orWhere('antiguedad', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && in_array($status, ['0','1'], true)) {
            $q->where('status', $status);
        }

        if ($desde) $q->whereDate('created_at', '>=', $desde);
        if ($hasta) $q->whereDate('created_at', '<=', $hasta);

        switch ($orden) {
            case 'fecha_asc':          $q->orderBy('created_at', 'asc'); break;
            case 'interes_desc':       $q->orderBy('interes', 'desc');   break;
            case 'interes_asc':        $q->orderBy('interes', 'asc');    break;
            case 'monto_maximo_desc':  $q->orderBy('monto_maximo', 'desc'); break;
            case 'monto_maximo_asc':   $q->orderBy('monto_maximo', 'asc');  break;
            default:                   $q->orderBy('created_at', 'desc');
        }

        $prestamos = $q->paginate(15)->withQueryString();

        return view('adminprestamos.index', compact(
            'prestamos', 'statusOptions', 'search', 'status', 'desde', 'hasta', 'orden'
        ));
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
        $data['status']     = 1; // Activo por defecto

        Prestamo::create($data);

        return redirect()->route('prestamos.index')
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
            'status'        => 'required|in:0,1', // ← solo 0/1
        ]);

        $data['id_usuario'] = Auth::id();
        $prestamo->update($data);

        return redirect()->route('prestamos.index')
            ->with('success','Tipo de préstamo actualizado correctamente.');
    }

    public function destroy(Prestamo $prestamo)
    {
        // “Eliminar” = inactivar (status = 0)
        $prestamo->update([
            'status'     => 0,
            'id_usuario' => Auth::id(),
        ]);

        return redirect()->route('prestamos.index')
            ->with('success','Tipo de préstamo inactivado correctamente.');
    }

    /**
     * Cambio rápido de estado (AJAX o no): 1=Activo, 0=Inactivo
     */
    public function quickStatus(Request $request, Prestamo $prestamo)
    {
        $validated = $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $prestamo->update([
            'status'     => (int)$validated['status'],
            'id_usuario' => Auth::id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => (int)$prestamo->status]);
        }

        return back()->with('success', 'Estado actualizado.');
    }
}
