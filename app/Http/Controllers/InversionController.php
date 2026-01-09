<?php

namespace App\Http\Controllers;

use App\Models\Inversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InversionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $inversiones = Inversion::with('usuario')
            ->whereIn('status', ['1', '2'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('admininversiones.index', compact('inversiones'));
    }

    public function create()
    {
        return view('admininversiones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:255',
            'periodo'       => 'required|string|max:255',
            'monto_minimo'  => 'required|numeric|min:0',
            'monto_maximo'  => 'required|numeric|gte:monto_minimo',
            'rendimiento'   => 'required|numeric|min:0',
            'fecha'         => 'required|date',
        ]);

        $data['id_usuario'] = Auth::id();
        $data['status']     = '1';
        $data['fecha_edit'] = now(); // ✅ cambio: guardar fecha_edit

        Inversion::create($data);

        return redirect()
            ->route('inversiones.index')
            ->with('success', 'Inversion creada correctamente.');
    }

    public function show(Inversion $inversion)
    {
        return view('admininversiones.show', compact('inversion'));
    }

    public function edit(Inversion $inversion)
    {
        return view('admininversiones.edit', compact('inversion'));
    }

    public function update(Request $request, Inversion $inversion)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:255',
            'periodo'       => 'required|string|max:255',
            'monto_minimo'  => 'required|numeric|min:0',
            'monto_maximo'  => 'required|numeric|gte:monto_minimo',
            'rendimiento'   => 'required|numeric|min:0',
            'fecha'         => 'required|date',
            'status'        => 'required|in:1,2',
        ]);

        $data['id_usuario'] = Auth::id();
        $data['fecha_edit'] = now(); // ✅ cambio: guardar fecha_edit

        $inversion->update($data);

        return redirect()
            ->route('inversiones.index')
            ->with('success', 'Inversion actualizada correctamente.');
    }

    /**
     * PATCH /inversiones/{inversion}/status
     * Cambio rapido (select) entre Activo/Inactivo.
     */
    public function updateStatus(Request $request, Inversion $inversion)
    {
        $data = $request->validate([
            'status' => 'required|in:1,2',
        ]);

        $inversion->update([
            'status'     => (string) $data['status'],
            'id_usuario' => Auth::id(),
            'fecha_edit' => now(), // ✅ cambio: guardar fecha_edit
        ]);

        return redirect()
            ->route('inversiones.index')
            ->with('success', ((string)$data['status'] === '1')
                ? 'Inversion activada correctamente.'
                : 'Inversion desactivada correctamente.'
            );
    }

    /**
     * DELETE /inversiones/{inversion}
     * Toggle logico (conservado para compatibilidad).
     */
    public function destroy(Inversion $inversion)
    {
        $newStatus = ((string) $inversion->status === '1') ? '2' : '1';

        $inversion->update([
            'status'     => $newStatus,
            'id_usuario' => Auth::id(),
            'fecha_edit' => now(),
        ]);

        return redirect()
            ->route('inversiones.index')
            ->with('success', $newStatus === '1'
                ? 'Inversion activada correctamente.'
                : 'Inversion desactivada correctamente.'
            );
    }

    /**
     * DELETE /inversiones/{inversion}/force
     * Borrado permanente (la X).
     */
    public function forceDestroy(Inversion $inversion)
    {
        $inversion->delete();

        return redirect()
            ->route('inversiones.index')
            ->with('success', 'Inversion eliminada permanentemente.');
    }
}
