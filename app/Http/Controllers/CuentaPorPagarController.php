<?php

namespace App\Http\Controllers;

use App\Models\CuentaPorPagar;
use App\Models\CuentaPorPagarDetalle;
use App\Models\Sucursal;
use App\Models\Caja;
use App\Models\Proveedor;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\VisibilityScope as VS;

class CuentaPorPagarController extends Controller
{
    protected function findScopedOrFail(int $id): CuentaPorPagar
    {
        $q = CuentaPorPagar::query();
        VS::cuentasPagar($q, Auth::user());
        return $q->whereKey($id)->firstOrFail();
    }

    /** ✅ Calcula fecha_vencimiento en base a emisión + periodo + #abonos */
    private function calcFechaVencimiento(string $fechaEmision, string $periodo, int $numeroAbonos): string
    {
        $f = Carbon::parse($fechaEmision);

        return match ($periodo) {
            'semanal'   => $f->addWeeks($numeroAbonos)->toDateString(),
            'quincenal' => $f->addDays(15 * $numeroAbonos)->toDateString(),
            'mensual'   => $f->addMonthsNoOverflow($numeroAbonos)->toDateString(),
            default     => $f->toDateString(),
        };
    }

    public function index(Request $request)
    {
        $estado   = trim((string)$request->get('estado', ''));
        $sucursal = (string)$request->get('sucursal', '');
        $search   = trim((string)$request->get('search', ''));

        $sucursales = Sucursal::select('id_sucursal as id', 'nombre')
            ->orderBy('Nombre', 'asc')
            ->get();

        $q = CuentaPorPagar::query();
        VS::cuentasPagar($q, Auth::user());

        $q->with(['sucursal', 'caja', 'proveedor', 'usuario'])
            ->withSum(['detalles as total_vencido' => fn($d) => $d->where('estado', 'vencido')], 'monto_pago')
            ->withSum(['detalles as monto_pagado'  => fn($d) => $d->where('estado', 'pagado')], 'monto_pago')
            ->withCount([
                'detalles as cnt_total'      => fn($d) => $d,
                'detalles as cnt_vencidos'   => fn($d) => $d->where('estado', 'vencido'),
                'detalles as cnt_pendientes' => fn($d) => $d->where('estado', 'pendiente'),
                'detalles as cnt_pagados'    => fn($d) => $d->where('estado', 'pagado'),
            ]);

        if ($sucursal !== '') $q->where('id_sucursal', $sucursal);

        if ($estado !== '') {
            if ($estado === 'vencido') {
                $q->whereHas('detalles', fn($d) => $d->where('estado', 'vencido'));
            } elseif ($estado === 'pagado') {
                $q->whereHas('detalles')
                    ->whereDoesntHave('detalles', fn($d) => $d->whereIn('estado', ['pendiente', 'vencido']));
            } elseif ($estado === 'al_corriente') {
                $q->whereDoesntHave('detalles', fn($d) => $d->where('estado', 'vencido'))
                    ->whereHas('detalles', fn($d) => $d->where('estado', 'pendiente'))
                    ->whereRaw("
                      (SELECT COALESCE(SUM(d2.monto_pago),0)
                         FROM cuentas_por_pagar_detalles d2
                        WHERE d2.cuenta_id = cuentas_por_pagar.id_cuentas_por_pagar
                          AND d2.estado = 'pagado') < cuentas_por_pagar.monto_total
                  ");
            }
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('descripcion', 'like', "%{$search}%")
                    ->orWhereHas('sucursal',  fn($s) => $s->where('nombre', 'like', "%{$search}%"))
                    ->orWhereHas('proveedor', fn($p) => $p->where('nombre', 'like', "%{$search}%"))
                    ->orWhereHas('caja',      fn($c) => $c->where('nombre', 'like', "%{$search}%"));
            });
        }

        $cuentas = $q->orderBy('fecha_emision', 'desc')->paginate(15);
        $cajas   = Caja::select('id_caja', 'nombre')->orderBy('nombre')->get();

        return view('cuentas_por_pagar.index', compact('cuentas', 'sucursales', 'cajas'));
    }

    public function vencidos(CuentaPorPagar $cuenta)
    {
        $this->findScopedOrFail($cuenta->id_cuentas_por_pagar);

        $vencidos = $cuenta->detalles()
            ->where('estado', 'vencido')
            ->orderBy('fecha_pago', 'asc')
            ->get(['id', 'numero_pago', 'fecha_pago', 'monto_pago', 'estado']);

        return view('cuentas_por_pagar._vencidos', compact('cuenta', 'vencidos'));
    }

    public function pagarDetalle(Request $request, CuentaPorPagarDetalle $detalle)
    {
        $this->findScopedOrFail($detalle->cuenta_id);

        $data = $request->validate([
            'estado'      => 'required|in:pagado',
            'monto'       => 'required|numeric|min:0.01',
            'fecha_pago'  => 'required|date',
            'caja_id'     => 'required|exists:cajas,id_caja',
            'comentario'  => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($detalle, $data) {
            $monto = (float) $data['monto'];

            $detalle->update([
                'estado'      => 'pagado',
                'caja_id'     => $data['caja_id'],
                'comentario'  => $data['comentario'] ?? null,
                'fecha_pago'  => $data['fecha_pago'],
            ]);

            $caja = Caja::findOrFail($data['caja_id']);
            $montoAnterior  = (float) $caja->saldo_final;
            $caja->decrement('saldo_final', $monto);
            $montoPosterior = $montoAnterior - $monto;

            MovimientoCaja::create([
                'id_caja'         => $caja->id_caja,
                'tipo_mov'        => 'Egreso',
                'monto'           => $monto,
                'fecha'           => $data['fecha_pago'],
                'descripcion'     => "Pago abono #{$detalle->numero_pago} de cuenta {$detalle->cuenta_id}",
                'monto_anterior'  => $montoAnterior,
                'monto_posterior' => $montoPosterior,
                'id_usuario'      => auth()->id(),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    public function pagarTotal(Request $request, CuentaPorPagar $cuenta)
    {
        $this->findScopedOrFail($cuenta->id_cuentas_por_pagar);

        $data = $request->validate([
            'caja_id'     => 'required|exists:cajas,id_caja',
            'comentario'  => 'nullable|string|max:500',
            'fecha_pago'  => 'nullable|date',
        ]);

        DB::transaction(function () use ($cuenta, $data) {
            $vencidos = $cuenta->detalles()->where('estado', 'vencido')->get();
            $total    = (float) $vencidos->sum('monto_pago');

            foreach ($vencidos as $d) {
                $d->update([
                    'estado'      => 'pagado',
                    'caja_id'     => $data['caja_id'],
                    'comentario'  => $data['comentario'] ?? null,
                    'fecha_pago'  => $data['fecha_pago'] ?? $d->fecha_pago,
                ]);
            }

            if ($total > 0) {
                $caja = Caja::findOrFail($data['caja_id']);
                $montoAnterior  = (float) $caja->saldo_final;
                $caja->decrement('saldo_final', $total);
                $montoPosterior = $montoAnterior - $total;

                MovimientoCaja::create([
                    'id_caja'         => $caja->id_caja,
                    'tipo_mov'        => 'Egreso',
                    'monto'           => $total,
                    'fecha'           => $data['fecha_pago'] ?? now(),
                    'descripcion'     => "Pago total vencido cuenta {$cuenta->id_cuentas_por_pagar}",
                    'monto_anterior'  => $montoAnterior,
                    'monto_posterior' => $montoPosterior,
                    'id_usuario'      => auth()->id(),
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function pagarTotalVencido(Request $request, CuentaPorPagar $cuenta)
    {
        return $this->pagarTotal($request, $cuenta);
    }

    public function create()
    {
        $sucursales  = Sucursal::all();
        $cajas       = Caja::all();
        $proveedores = Proveedor::all();
        return view('cuentas_por_pagar.create', compact('sucursales', 'cajas', 'proveedores'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_sucursal'       => 'required|exists:sucursales,id_sucursal',
            'id_caja'           => 'required|exists:cajas,id_caja',
            'proveedor_id'      => 'nullable|exists:proveedores,id_proveedor',
            'monto_total'       => 'required|numeric|min:0',
            'tasa_anual'        => 'required|numeric|min:0',
            'numero_abonos'     => 'required|integer|min:1',
            'periodo_pago'      => 'required|in:semanal,quincenal,mensual',
            'fecha_emision'     => 'required|date',
            // ✅ ahora nullable, se calcula solo
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_emision',
            'estado'            => 'required|in:pendiente,pagado,vencido',
            'descripcion'       => 'nullable|string',
        ]);

        $data['id_usuario'] = Auth::id();

        // ✅ calcular automáticamente
        $data['fecha_vencimiento'] = $this->calcFechaVencimiento(
            $data['fecha_emision'],
            $data['periodo_pago'],
            (int) $data['numero_abonos']
        );

        $cuenta = CuentaPorPagar::create($data);
        $this->generateAmortizacion($cuenta);

        return redirect()->route('cuentas-por-pagar.index')
            ->with('success', 'Cuenta por pagar creada correctamente.');
    }

    public function show(CuentaPorPagar $cuenta)
    {
        $cuenta = $this->findScopedOrFail($cuenta->id_cuentas_por_pagar)
            ->load(['detalles.caja', 'sucursal', 'caja', 'proveedor', 'usuario']);

        $cajas = Caja::all();
        return view('cuentas_por_pagar.show', compact('cuenta', 'cajas'));
    }

    public function edit(CuentaPorPagar $cuenta)
    {
        $cuenta      = $this->findScopedOrFail($cuenta->id_cuentas_por_pagar);
        $sucursales  = Sucursal::all();
        $cajas       = Caja::all();
        $proveedores = Proveedor::all();
        return view('cuentas_por_pagar.edit', compact('cuenta', 'sucursales', 'cajas', 'proveedores'));
    }

    public function update(Request $request, CuentaPorPagar $cuenta)
    {
        $cuenta = $this->findScopedOrFail($cuenta->id_cuentas_por_pagar);

        $data = $request->validate([
            'id_sucursal'       => 'required|exists:sucursales,id_sucursal',
            'id_caja'           => 'required|exists:cajas,id_caja',
            'proveedor_id'      => 'nullable|exists:proveedores,id_proveedor',
            'monto_total'       => 'required|numeric|min:0',
            'tasa_anual'        => 'required|numeric|min:0',
            'numero_abonos'     => 'required|integer|min:1',
            'periodo_pago'      => 'required|in:semanal,quincenal,mensual',
            'fecha_emision'     => 'required|date',
            // ✅ ahora nullable, se calcula solo
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_emision',
            'estado'            => 'required|in:pendiente,pagado,vencido',
            'descripcion'       => 'nullable|string',
        ]);

        $data['id_usuario'] = Auth::id();

        // ✅ recalcular automáticamente (siempre)
        $data['fecha_vencimiento'] = $this->calcFechaVencimiento(
            $data['fecha_emision'],
            $data['periodo_pago'],
            (int) $data['numero_abonos']
        );

        $cuenta->update($data);

        // Regenerar tabla de pagos
        $cuenta->detalles()->delete();
        $this->generateAmortizacion($cuenta);

        return redirect()->route('cuentas-por-pagar.index')
            ->with('success', 'Cuenta por pagar actualizada correctamente.');
    }

    public function destroy(CuentaPorPagar $cuenta)
    {
        $cuenta = $this->findScopedOrFail($cuenta->id_cuentas_por_pagar);
        $cuenta->delete();

        return redirect()->route('cuentas-por-pagar.index')
            ->with('success', 'Cuenta por pagar eliminada correctamente.');
    }

    protected function generateAmortizacion(CuentaPorPagar $cuenta)
    {
        $n       = $cuenta->numero_abonos;
        $saldo   = $cuenta->monto_total;
        $tasa    = $cuenta->tasa_anual / 100;
        $periodo = $cuenta->periodo_pago;
        $fecha   = Carbon::parse($cuenta->fecha_emision);

        $pagosPorAno = match ($periodo) {
            'semanal'   => 52,
            'quincenal' => 24,
            'mensual'   => 12,
        };

        $i = $tasa / $pagosPorAno;

        $M = $i == 0
            ? round($saldo / $n, 2)
            : round($saldo * ($i * pow(1 + $i, $n)) / (pow(1 + $i, $n) - 1), 2);

        DB::transaction(function () use ($n, $M, $i, &$saldo, &$fecha, $periodo, $cuenta) {
            for ($k = 1; $k <= $n; $k++) {
                $interes    = round($saldo * $i, 2);
                $amortiza   = round($M - $interes, 2);
                $nuevoSaldo = round($saldo - $amortiza, 2);

                $fecha = match ($periodo) {
                    'semanal'   => $fecha->addWeek(),
                    'quincenal' => $fecha->addDays(15),
                    'mensual'   => $fecha->addMonth(),
                };

                CuentaPorPagarDetalle::create([
                    'cuenta_id'        => $cuenta->id_cuentas_por_pagar,
                    'numero_pago'      => $k,
                    'fecha_pago'       => $fecha->toDateString(),
                    'saldo_inicial'    => $saldo,
                    'amortizacion_cap' => $amortiza,
                    'pago_interes'     => $interes,
                    'monto_pago'       => $M,
                    'saldo_restante'   => max($nuevoSaldo, 0),
                    'estado'           => 'pendiente',
                    'caja_id'          => null,
                    'comentario'       => null,
                    'semana'           => null,
                ]);

                $saldo = $nuevoSaldo;
            }
        });
    }
}
