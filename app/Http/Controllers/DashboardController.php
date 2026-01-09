<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ===== 1) Periodo =====
        $year   = (int) $request->input('year', now()->year);
        $months = collect((array) $request->input('months', []))
                    ->map(fn($m)=>(int)$m)->filter()->values()->all();

        $start = Carbon::create($year, 1, 1)->startOfYear();
        $end   = Carbon::create($year, 12, 31)->endOfYear();
        if (!empty($months)) {
            $start = Carbon::create($year, min($months), 1)->startOfMonth();
            $end   = Carbon::create($year, max($months), 1)->endOfMonth();
        }

        $has = fn(string $table, ?string $col=null) =>
            $col === null ? Schema::hasTable($table) : (Schema::hasTable($table) && Schema::hasColumn($table, $col));
        $money = fn(string $expr) => DB::raw("CAST(REPLACE($expr, ',', '') AS DECIMAL(15,2))");

        // ===== 2) Caja neta (como listado de Cajas) =====
        $cajaNeta = 0.0;
        if ($has('cajas')) {
            $cajas = DB::table('cajas')->select('id_caja','saldo_inicial','estado','saldo_final')->get();

            $movs = $has('movimientos_caja')
                ? DB::table('movimientos_caja')
                    ->selectRaw("id_caja,
                        SUM(CASE WHEN tipo_mov='Ingreso' THEN monto ELSE 0 END) AS ing,
                        SUM(CASE WHEN tipo_mov='Egreso'  THEN monto ELSE 0 END) AS egr")
                    ->groupBy('id_caja')
                    ->get()->keyBy('id_caja')
                : collect();

            foreach ($cajas as $c) {
                if ($c->estado === 'cerrada' && $c->saldo_final !== null) {
                    $saldo = (float) $c->saldo_final;
                } else {
                    $ing = (float) ($movs[$c->id_caja]->ing ?? 0);
                    $egr = (float) ($movs[$c->id_caja]->egr ?? 0);
                    $saldo = (float) $c->saldo_inicial + $ing - $egr;
                }
                $cajaNeta += $saldo;
            }
        }

        // ===== 3) Depósitos nuevos (status = 0) =====
        $depositosNuevos = 0;
        if ($has('user_depositos','fecha_deposito')) {
            $q = DB::table('user_depositos')->whereBetween('fecha_deposito', [$start,$end]);
            if ($has('user_depositos','status')) $q->where('status', 0);
            $depositosNuevos = (int) $q->count();
        }

        // ===== 4) Solicitudes préstamos =====
        $prestPend = 0; $prestRev = 0;
        $tablaPrest = $has('user_prestamos') ? 'user_prestamos' : ($has('prestamos') ? 'prestamos' : null);
        if ($tablaPrest) {
            if ($has($tablaPrest,'status')) {
                // 2=Pendiente, 3=En revisión (convención usada)
                $prestPend = (int) DB::table($tablaPrest)->where('status', 2)->count();
                $prestRev  = (int) DB::table($tablaPrest)->where('status', 3)->count();
            } elseif ($has($tablaPrest,'estado')) {
                $prestPend = (int) DB::table($tablaPrest)->whereIn('estado', ['Pendiente','pendiente'])->count();
                $prestRev  = (int) DB::table($tablaPrest)->whereIn('estado', ['En revisión','revision','Revisión'])->count();
            }
        }

        // ===== 5) Retiros pendientes (Inv y Ahorro) =====
        $retInvPend = 0; $retAhPend = 0; $retTotalPend = 0.0;
        if ($has('retiros','cantidad')) {
            $retInvPend = (int) DB::table('retiros')
                            ->where('status', 0)
                            ->whereBetween(DB::raw('COALESCE(fecha_transferencia, fecha_aprobacion, fecha_solicitud)'), [$start,$end])
                            ->count();
            $retTotalPend += (float) DB::table('retiros')
                                ->where('status', 0)
                                ->sum($money('cantidad'));
        }
        if ($has('retiros_ahorro','cantidad')) {
            $retAhPend = (int) DB::table('retiros_ahorro')
                            ->where('status', 0)
                            ->whereBetween(DB::raw('COALESCE(fecha_transferencia, fecha_aprobacion, fecha_solicitud)'), [$start,$end])
                            ->count();
            $retTotalPend += (float) DB::table('retiros_ahorro')
                                ->where('status', 0)
                                ->sum($money('cantidad'));
        }

        // ===== 6) Soporte =====
        $soporteMensajes = $has('mensajes') ? (int) DB::table('mensajes')->count() : 0;

        // Tickets sólo "en proceso" (status = 1). Si no hay columna, total.
        $soporteTickets = 0;
        if ($has('tickets')) {
            if ($has('tickets','status')) {
                $TICKET_EN_PROCESO = 1;
                $soporteTickets = (int) DB::table('tickets')->where('status', $TICKET_EN_PROCESO)->count();
            } else {
                $soporteTickets = (int) DB::table('tickets')->count();
            }
        }

        // ===== 7) CxP (solo vencidos) =====
        $cxpAbonosVencidos = 0; 
        $cxpSaldoVencido   = 0.0;
        if ($has('cuentas_por_pagar_detalles')) {
            $cxpAbonosVencidos = (int) DB::table('cuentas_por_pagar_detalles')
                ->where('estado','vencido')
                ->count();

            // *** CAMBIO: sumar el Monto del abono vencido ***
            if ($has('cuentas_por_pagar_detalles', 'monto_pago')) {
                // si tu campo se llama exactamente 'monto_pago'
                $cxpSaldoVencido = (float) DB::table('cuentas_por_pagar_detalles')
                    ->where('estado','vencido')
                    ->sum('monto_pago');
            } else {
                // Fallback por si el nombre difiere en alguna instalación
                $candidatas = ['monto_abono','monto_cuota','importe','monto','valor_abono','cantidad'];
                $col = collect($candidatas)->first(fn($c)=>$has('cuentas_por_pagar_detalles',$c));
                if ($col) {
                    $cxpSaldoVencido = (float) DB::table('cuentas_por_pagar_detalles')
                        ->where('estado','vencido')
                        ->sum($money($col));
                } else {
                    // último recurso: evita error y suma 0
                    $cxpSaldoVencido = 0.0;
                }
            }
        }

        // ===== 8) Abonos préstamos (SOLO vencidos) =====
        $abonosVencidosCount = 0;
        $abonosVencidosSaldo = 0.0;

        if ($has('user_abonos')) {
            // Acepta varias representaciones del estado "vencido"
            $VENCIDOS = [2, '2', 'Vencido', 'vencido', 'VENCIDO'];

            $abonosVencidosCount = (int) DB::table('user_abonos')
                ->whereIn('status', $VENCIDOS)
                ->count();

            // Construimos COALESCE dinámico con columnas disponibles
            $sumCols = [];
            if ($has('user_abonos','saldo_restante')) $sumCols[] = 'saldo_restante';
            if ($has('user_abonos','cantidad'))       $sumCols[] = 'cantidad';

            $sumExpr = '0';
            if (!empty($sumCols)) {
                $sumExpr = 'COALESCE(' . implode(',', $sumCols) . ',0)';
            }

            $abonosVencidosSaldo = (float) DB::table('user_abonos')
                ->whereIn('status', $VENCIDOS)
                ->sum(DB::raw($sumExpr));
        }

        // ===== 9) Ingresos/Egresos de caja =====
        $indMovsCaja = $has('movimientos_caja');
        $ingresosCaja = $indMovsCaja
            ? (float) DB::table('movimientos_caja')
                ->whereBetween('fecha', [$start,$end])
                ->where('tipo_mov','Ingreso')->sum('monto')
            : 0.0;

        $egresosCaja = $indMovsCaja
            ? (float) DB::table('movimientos_caja')
                ->whereBetween('fecha', [$start,$end])
                ->where('tipo_mov','Egreso')->sum('monto')
            : 0.0;

        $ingresosPeriodo = $ingresosCaja;

        $movimientosFlujo = $indMovsCaja
            ? (int) DB::table('movimientos_caja')->whereBetween('fecha', [$start,$end])->count()
            : 0;

        // ===== 10) Series para gráficas =====
        $labels = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];

        $depMes = DB::table('vw_depositos_clean')
            ->selectRaw('MONTH(fecha) m, SUM(monto) total')
            ->whereYear('fecha', $year)
            ->when(!empty($months), fn($q)=>$q->whereIn(DB::raw('MONTH(fecha)'), $months))
            ->groupBy('m')->orderBy('m')->pluck('total','m');

        $retMes = DB::table('vw_retiros_union')
            ->selectRaw('MONTH(fecha) m, SUM(monto) total')
            ->whereYear('fecha', $year)
            ->when(!empty($months), fn($q)=>$q->whereIn(DB::raw('MONTH(fecha)'), $months))
            ->groupBy('m')->orderBy('m')->pluck('total','m');

        $ingMes = DB::table('movimientos_caja')
            ->selectRaw('MONTH(fecha) m, SUM(monto) total')
            ->whereYear('fecha', $year)->where('tipo_mov','Ingreso')
            ->when(!empty($months), fn($q)=>$q->whereIn(DB::raw('MONTH(fecha)'), $months))
            ->groupBy('m')->orderBy('m')->pluck('total','m');

        $egrMes = DB::table('movimientos_caja')
            ->selectRaw('MONTH(fecha) m, SUM(monto) total')
            ->whereYear('fecha', $year)->where('tipo_mov','Egreso')
            ->when(!empty($months), fn($q)=>$q->whereIn(DB::raw('MONTH(fecha)'), $months))
            ->groupBy('m')->orderBy('m')->pluck('total','m');

        $invMes = DB::table('vw_user_inversiones_clean')
            ->selectRaw('MONTH(fecha) m, SUM(monto) total')
            ->whereYear('fecha', $year)
            ->when(!empty($months), fn($q)=>$q->whereIn(DB::raw('MONTH(fecha)'), $months))
            ->groupBy('m')->orderBy('m')->pluck('total','m');

        $serieDepositos=[]; $serieRetiros=[]; $serieIngresos=[]; $serieEgresos=[]; $serieInvMensual=[];
        for ($i=1;$i<=12;$i++) {
            if (!empty($months) && !in_array($i,$months)) {
                $serieDepositos[]=0; $serieRetiros[]=0; $serieIngresos[]=0; $serieEgresos[]=0; $serieInvMensual[]=0;
                continue;
            }
            $serieDepositos[]  = (float)($depMes[$i] ?? 0);
            $serieRetiros[]    = (float)($retMes[$i] ?? 0);
            $serieIngresos[]   = (float)($ingMes[$i] ?? 0);
            $serieEgresos[]    = (float)($egrMes[$i] ?? 0);
            $serieInvMensual[] = (float)($invMes[$i] ?? 0);
        }

        $yearsSet = collect()
            ->merge(DB::table('vw_depositos_clean')->selectRaw('DISTINCT YEAR(fecha) y')->pluck('y'))
            ->merge(DB::table('vw_retiros_union')->selectRaw('DISTINCT YEAR(fecha) y')->pluck('y'))
            ->merge(DB::table('vw_user_inversiones_clean')->selectRaw('DISTINCT YEAR(fecha) y')->pluck('y'))
            ->unique()->sort()->values();

        $depYear = DB::table('vw_depositos_clean')->selectRaw('YEAR(fecha) y, SUM(monto) total')->groupBy('y')->pluck('total','y');
        $retYear = DB::table('vw_retiros_union')->selectRaw('YEAR(fecha) y, SUM(monto) total')->groupBy('y')->pluck('total','y');
        $invYear = DB::table('vw_user_inversiones_clean')->selectRaw('YEAR(fecha) y, SUM(monto) total')->groupBy('y')->pluck('total','y');

        $labelsYears = $yearsSet->all();
        $serieDepYear=[]; $serieRetYear=[]; $serieInvYear=[];
        foreach ($labelsYears as $y) {
            $serieDepYear[] = (float)($depYear[$y] ?? 0);
            $serieRetYear[] = (float)($retYear[$y] ?? 0);
            $serieInvYear[] = (float)($invYear[$y] ?? 0);
        }

        // en esta versión no hay filtros de inversiones → false
        $invFiltersActive = false;

        return view('dashboard.index', compact(
            // periodo
            'year','months','start','end',
            // tarjetas
            'cajaNeta','depositosNuevos',
            'prestPend','prestRev',
            'retInvPend','retAhPend','retTotalPend',
            'soporteMensajes','soporteTickets',
            'cxpAbonosVencidos','cxpSaldoVencido',
            'abonosVencidosCount','abonosVencidosSaldo',
            'ingresosPeriodo','movimientosFlujo',
            // series
            'labels','serieDepositos','serieRetiros','serieIngresos','serieEgresos','serieInvMensual',
            'labelsYears','serieDepYear','serieInvYear','serieRetYear',
            'invFiltersActive'
        ));
    }
}
