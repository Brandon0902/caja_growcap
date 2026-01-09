<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use Illuminate\Http\Request;

class PresupuestoController extends Controller
{
    /**
     * Mostrar formulario con todos los meses, años y fuentes,
     * pudiendo definir por cada fuente si es Presupuesto o Meta.
     */
    public function index(Request $request)
    {
        $mes = (int) $request->query('mes', now()->month);
        $año = (int) $request->query('año', now()->year);

        // Fuentes disponibles (ajústalas a tu gusto)
        $fuentes = [
            'Movimientos Caja',
            'Depósitos',
            'Retiros',
            'Ahorros',
            'Inversiones',
            'Préstamos',
            'Abonos',
        ];

        // Nombres de meses
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio',   7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        // Cargar registros existentes por mes/año
        $presupuestos = Presupuesto::where('mes', $mes)
            ->where('año', $año)
            ->get()
            ->keyBy('fuente');

        // Tipos disponibles
        $tipos = ['Presupuesto', 'Meta'];

        // Tipos por defecto cuando aún no existe registro guardado
        $tiposPorDefecto = [
            'Movimientos Caja' => 'Meta',
            'Ahorros'          => 'Meta',
            // Las demás fuentes quedarán como "Presupuesto" por defecto
        ];

        return view('presupuestos.index', compact(
            'mes', 'año', 'fuentes', 'presupuestos', 'meses', 'tipos', 'tiposPorDefecto'
        ));
    }

    /**
     * Crear o actualizar en bloque.
     * Recibe arreglos alineados: fuente[], tipo[], monto[].
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'mes'        => 'required|integer|min:1|max:12',
            'año'        => 'required|integer',
            'fuente'     => 'required|array',
            'fuente.*'   => 'required|string',
            'monto'      => 'required|array',
            'monto.*'    => 'nullable|numeric',
            'tipo'       => 'required|array',
            'tipo.*'     => 'required|string|in:Presupuesto,Meta',
        ]);

        foreach ($data['fuente'] as $i => $f) {
            $monto = $data['monto'][$i] ?? 0;
            $tipo  = $data['tipo'][$i];

            Presupuesto::updateOrCreate(
                ['fuente' => $f, 'mes' => $data['mes'], 'año' => $data['año']],
                ['monto'  => $monto, 'tipo' => $tipo]
            );
        }

        return redirect()
            ->route('presupuestos.index', ['mes' => $data['mes'], 'año' => $data['año']])
            ->with('success', 'Metas/Presupuestos guardados correctamente.');
    }
}
