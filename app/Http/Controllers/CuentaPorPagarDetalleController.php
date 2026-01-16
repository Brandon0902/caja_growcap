<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Sucursal;
use App\Models\MovimientoCaja;
use App\Models\CuentaPorPagar;
use App\Models\CuentaPorPagarDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Services\VisibilityScope;
use App\Services\OperacionRecipientsService;
use App\Mail\CuentaPorPagarPagoNotificacionMail;
use App\Mail\CuentaPorPagarPagoAdminMail;

class CuentaPorPagarDetalleController extends Controller
{
    /**
     * Index GLOBAL de abonos (detalles) de TODAS las cuentas por pagar.
     * Aplica el filtro de visibilidad por sucursal (ver_asignadas / ver_sucursal).
     */
    public function index(Request $request)
    {
        // Base + relaciones
        $base = CuentaPorPagarDetalle::query()
            ->with([
                'cuenta:id_cuentas_por_pagar,descripcion,id_sucursal,proveedor_id,monto_total,estado',
                'cuenta.sucursal:id_sucursal,nombre',
                'cuenta.proveedor:id_proveedor,nombre',
                'caja:id_caja,nombre',
            ]);

        // Visibilidad por sucursal para ABONOS (hace join a cuentas_por_pagar)
        $base = VisibilityScope::cuentasPagarDetalles($base, Auth::user());

        // Evitar colisi¨®n de columnas "estado" (cuenta vs detalle)
        $base->select('cuentas_por_pagar_detalles.*');

        // -------- Filtros comunes de UI --------
        if ($request->filled('sucursal_id')) {
            $base->whereHas('cuenta', function ($q) use ($request) {
                $q->where('id_sucursal', $request->sucursal_id);
            });
        }

        if ($request->filled('desde')) {
            $base->whereDate('cuentas_por_pagar_detalles.fecha_pago', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $base->whereDate('cuentas_por_pagar_detalles.fecha_pago', '<=', $request->hasta);
        }

        if ($request->filled('q')) {
            $term = '%' . trim($request->q) . '%';
            $base->where(function ($qq) use ($term) {
                $qq->whereHas('cuenta.proveedor', fn ($p) => $p->where('nombre', 'like', $term))
                   ->orWhereHas('cuenta', fn ($c) => $c->where('descripcion', 'like', $term))
                   ->orWhere('cuentas_por_pagar_detalles.comentario', 'like', $term);
            });
        }

        // Lista (filtro por estado solo afecta la tabla de detalles)
        $list = clone $base;
        if ($request->filled('estado')) {
            $list->where('cuentas_por_pagar_detalles.estado', $request->estado);
        }

        // -------- Resumen --------
        $abonosTotal = (clone $list)->count();

        $saldoVencido = (clone $base)
            ->where('cuentas_por_pagar_detalles.estado', 'vencido')
            ->sum('cuentas_por_pagar_detalles.monto_pago');

        $montoPagado = (clone $base)
            ->where('cuentas_por_pagar_detalles.estado', 'pagado')
            ->sum('cuentas_por_pagar_detalles.monto_pago');

        $abonosPorPagar = (clone $base)
            ->whereIn('cuentas_por_pagar_detalles.estado', ['pendiente', 'vencido'])
            ->sum('cuentas_por_pagar_detalles.monto_pago');

        $resumen = [
            'abonos'           => (int) $abonosTotal,
            'saldo_vencido'    => (float) $saldoVencido,
            'monto_pagado'     => (float) $montoPagado,
            'abonos_por_pagar' => (float) $abonosPorPagar,
        ];

        $detalles = $list->orderByDesc('cuentas_por_pagar_detalles.fecha_pago')
            ->paginate(25)
            ->withQueryString();

        $sucursales = Sucursal::orderBy('nombre')->get(['id_sucursal','nombre']);
        $cajas      = Caja::orderBy('nombre')->get(['id_caja','nombre']);

        return view('cuentas_por_pagar.detalles.index', compact('detalles', 'sucursales', 'resumen', 'cajas'));
    }

    public function create(CuentaPorPagar $cuentas_por_pagar)
    {
        $cajas = Caja::all();
        return view('cuentas_por_pagar.detalles.create', compact('cuentas_por_pagar', 'cajas'));
    }

    public function store(Request $request, CuentaPorPagar $cuentas_por_pagar, OperacionRecipientsService $recipients)
    {
        $data = $request->validate([
            'numero_pago'      => 'required|integer|min:1',
            'fecha_pago'       => 'required|date',
            'saldo_inicial'    => 'required|numeric|min:0',
            'amortizacion_cap' => 'required|numeric|min:0',
            'pago_interes'     => 'required|numeric|min:0',
            'monto_pago'       => 'required|numeric|min:0',
            'saldo_restante'   => 'required|numeric|min:0',
            'estado'           => 'required|in:pendiente,pagado,vencido',
            'caja_id'          => 'nullable|exists:cajas,id_caja',
            'comentario'       => 'nullable|string|max:255',
            'semana'           => 'nullable|integer|min:1',
        ]);

        $data['cuenta_id'] = $cuentas_por_pagar->id_cuentas_por_pagar;
        $detalle = CuentaPorPagarDetalle::create($data);

        if ($data['estado'] === 'pagado' && $data['caja_id']) {
            $caja = Caja::findOrFail($data['caja_id']);

            $montoAnterior  = $caja->saldo_final;
            $caja->decrement('saldo_final', $data['monto_pago']);
            $montoPosterior = $montoAnterior - $data['monto_pago'];

            MovimientoCaja::create([
                'id_caja'         => $caja->id_caja,
                'tipo_mov'        => 'Egreso',
                'monto'           => $data['monto_pago'],
                'fecha'           => now(),
                'descripcion'     => "Pago abono #{$detalle->numero_pago} de cuenta {$cuentas_por_pagar->id_cuentas_por_pagar}",
                'monto_anterior'  => $montoAnterior,
                'monto_posterior' => $montoPosterior,
                'id_usuario'      => auth()->id(),
            ]);


                    $adminEmail = trim((string) config('services.admin.email'));
                    if ($adminEmail !== '') {
                        Mail::to($adminEmail)->send(new CuentaPorPagarPagoAdminMail(
                            $cuentas_por_pagar,
                            $detalle,
                            $caja,
                            $actor,
                            'pagado'
                        ));
                    }
            // 7¼3 ENVIAR CORREO (cuando se paga)
            try {
                $actor = Auth::user();

                // Cargar relaciones para el correo
                $detalle->load(['cuenta.sucursal', 'cuenta.proveedor', 'caja']);
                $cuentas_por_pagar->loadMissing(['sucursal', 'proveedor']);

                $sucursalId = (int) ($cuentas_por_pagar->id_sucursal ?? 0);

                if ($sucursalId > 0 && $actor) {
                    $to = $recipients->forSucursalAndActor($sucursalId, $actor);

                    if (!empty($to)) {
                        Mail::to($to)->send(new CuentaPorPagarPagoNotificacionMail(
                            $cuentas_por_pagar,
                            $detalle,
                            $caja,
                            $actor,
                            'pagado'
                        ));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar mail de pago CxP (store): '.$e->getMessage(), [
                    'cuenta_id'  => $cuentas_por_pagar->id_cuentas_por_pagar ?? null,
                    'detalle_id' => $detalle->id ?? null,
                ]);
            }
        }

        return redirect()
            ->route('cuentas-por-pagar.show', $cuentas_por_pagar)
            ->with('success', 'Pago agregado correctamente.');
    }

    public function edit(CuentaPorPagarDetalle $detalle)
    {
        $cajas = Caja::all();
        return view('cuentas_por_pagar.detalles.edit', compact('detalle', 'cajas'));
    }

    public function update(Request $request, CuentaPorPagarDetalle $detalle, OperacionRecipientsService $recipients)
    {
        // 7¼3 Guard para evitar mandar correo duplicado si ya era pagado
        $wasPagado = ((string) $detalle->estado === 'pagado');

        $data = $request->validate([
            'numero_pago'      => 'required|integer|min:1',
            'fecha_pago'       => 'required|date',
            'saldo_inicial'    => 'required|numeric|min:0',
            'amortizacion_cap' => 'required|numeric|min:0',
            'pago_interes'     => 'required|numeric|min:0',
            'monto_pago'       => 'required|numeric|min:0',
            'saldo_restante'   => 'required|numeric|min:0',
            'estado'           => 'required|in:pendiente,pagado,vencido',
            'caja_id'          => 'nullable|exists:cajas,id_caja',
            'comentario'       => 'nullable|string|max:255',
            'semana'           => 'nullable|integer|min:1',
        ]);

        $detalle->update($data);

        if ($data['estado'] === 'pagado' && $data['caja_id']) {
            $caja = Caja::findOrFail($data['caja_id']);

            $montoAnterior  = $caja->saldo_final;
            $caja->decrement('saldo_final', $data['monto_pago']);
            $montoPosterior = $montoAnterior - $data['monto_pago'];

            MovimientoCaja::create([
                'id_caja'         => $caja->id_caja,
                'tipo_mov'        => 'Egreso',
                'monto'           => $data['monto_pago'],
                'fecha'           => now(),
                'descripcion'     => "Pago abono #{$detalle->numero_pago} de cuenta {$detalle->cuenta_id}",
                'monto_anterior'  => $montoAnterior,
                'monto_posterior' => $montoPosterior,
                'id_usuario'      => auth()->id(),
            ]);


                        $adminEmail = trim((string) config('services.admin.email'));
                        if ($adminEmail !== '') {
                            Mail::to($adminEmail)->send(new CuentaPorPagarPagoAdminMail(
                                $cuenta,
                                $detalle,
                                $caja,
                                $actor,
                                'pagado'
                            ));
                        }
            // 7¼3 ENVIAR CORREO SOLO SI ANTES NO ERA PAGADO
            if (!$wasPagado) {
                try {
                    $actor = Auth::user();

                    $detalle->load(['cuenta.sucursal', 'cuenta.proveedor', 'caja']);
                    $cuenta = $detalle->cuenta; // relaci¨®n

                    $sucursalId = (int) ($cuenta->id_sucursal ?? 0);

                    if ($sucursalId > 0 && $actor) {
                        $to = $recipients->forSucursalAndActor($sucursalId, $actor);

                        if (!empty($to)) {
                            Mail::to($to)->send(new CuentaPorPagarPagoNotificacionMail(
                                $cuenta,
                                $detalle,
                                $caja,
                                $actor,
                                'pagado'
                            ));
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo enviar mail de pago CxP (update): '.$e->getMessage(), [
                        'cuenta_id'  => $detalle->cuenta_id ?? null,
                        'detalle_id' => $detalle->id ?? null,
                    ]);
                }
            }
        }

        return redirect()
            ->route('cuentas-por-pagar.show', $detalle->cuenta)
            ->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(CuentaPorPagarDetalle $detalle)
    {
        $cuenta = $detalle->cuenta;
        $detalle->delete();

        return redirect()
            ->route('cuentas-por-pagar.show', $cuenta)
            ->with('success', 'Pago eliminado correctamente.');
    }
}
