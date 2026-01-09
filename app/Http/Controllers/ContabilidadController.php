<?php

namespace App\Http\Controllers;

use App\Models\MovimientoFinanciero;
use App\Models\Cliente;
use App\Models\Presupuesto;
use App\Models\Sucursal;
use App\Models\Caja;
use App\Models\User;

// Nombres reales de tus modelos de categorías
use App\Models\CategoriaIngreso;
use App\Models\CategoriaGasto;
use App\Models\SubcategoriaIngreso;
use App\Models\SubcategoriaGasto;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ContabilidadController extends Controller
{
    protected array $meses = [
        1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
        7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre',
    ];

    protected array $fuentes = [
        'Movimientos Caja','Depósitos','Retiros','Retiros Ahorro','Ahorros','Inversiones','Préstamos','Abonos',
    ];

    public function index(Request $request)
    {
        // ===== Catálogos para filtros =====
        $años       = range(now()->year, now()->year - 5);
        $clientes   = Cliente::orderBy('nombre')->pluck('nombre', 'id');

        // Usa la PK real del modelo User (id_usuario)
        $usuarios = User::query()
            ->orderBy('name')
            ->pluck('name', (new User)->getKeyName());

        $sucursales = Sucursal::orderBy('nombre')->pluck('nombre', 'id_sucursal');
        $cajas      = Caja::orderBy('nombre')->pluck('nombre', 'id_caja');

        // ===== Validación de filtros =====
        $data = $request->validate([
            'mes'             => ['nullable','integer','min:1','max:12'],
            'año'             => ['nullable','integer', Rule::in($años)],
            'desde'           => ['nullable','date'],
            'hasta'           => ['nullable','date','after_or_equal:desde'],
            'tipo'            => ['nullable','in:ingreso,egreso'],
            'fuente'          => ['nullable','string', Rule::in($this->fuentes)],
            'cliente_id'      => ['nullable','integer','exists:clientes,id'],
            'sucursal_id'     => ['nullable','integer','exists:sucursales,id_sucursal'],
            'caja_id'         => ['nullable','integer','exists:cajas,id_caja'],
            'usuario_id'      => ['nullable','integer','exists:usuarios,id_usuario'],
            'categoria_id'    => ['nullable','integer'],
            'subcategoria_id' => ['nullable','integer'],
        ]);

        // Normaliza rango cuando solo hay mes/año
        $mes   = $data['mes']   ?? null;
        $año   = $data['año']   ?? null;
        $desde = $data['desde'] ?? null;
        $hasta = $data['hasta'] ?? null;

        if (!$desde && !$hasta && $mes && $año) {
            $desde = Carbon::create($año, $mes, 1)->startOfDay()->toDateString();
            $hasta = Carbon::create($año, $mes, 1)->endOfMonth()->endOfDay()->toDateString();
        }

        // ===== Query a la VISTA movimientos_financieros =====
        $q = MovimientoFinanciero::query()
            ->when($desde, fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha', '<=', $hasta))
            ->when($mes && $año && !$desde && !$hasta, fn($q) => $q->whereYear('fecha', $año)->whereMonth('fecha', $mes))
            ->when(!empty($data['tipo']),            fn($q) => $q->where('tipo',         $data['tipo']))
            ->when(!empty($data['fuente']),          fn($q) => $q->where('fuente',       $data['fuente']))
            ->when(!empty($data['cliente_id']),      fn($q) => $q->where('cliente_id',   $data['cliente_id']))
            ->when(!empty($data['sucursal_id']),     fn($q) => $q->where('sucursal_id',  $data['sucursal_id']))
            ->when(!empty($data['caja_id']),         fn($q) => $q->where('caja_id',      $data['caja_id']))
            ->when(!empty($data['usuario_id']),      fn($q) => $q->where('user_id',      $data['usuario_id']))
            ->when(!empty($data['categoria_id']),    fn($q) => $q->where('categoria_id', $data['categoria_id']))
            ->when(!empty($data['subcategoria_id']), fn($q) => $q->where('subcategoria_id', $data['subcategoria_id']))
            ->orderBy('fecha');

        $movs = $q->get()->load([
            'cliente','sucursal','caja','user',
            'categoriaIngreso','categoriaGasto','subcategoriaIngreso','subcategoriaGasto',
        ]);

        // ===== KPIs =====
        $ingresos    = $movs->where('tipo', 'ingreso')->sum('monto');
        $egresos     = $movs->where('tipo', 'egreso')->sum('monto');
        $balanceNeto = $ingresos - $egresos;

        $kpi = [
            'movimientos' => $movs->count(),
            'ingresos'    => $ingresos,
            'egresos'     => $egresos,
            'balance'     => $balanceNeto,
            'cajas'       => Caja::count(),
            'categorias'  => CategoriaIngreso::count() + CategoriaGasto::count(),
            'subcats'     => SubcategoriaIngreso::count() + SubcategoriaGasto::count(),
            'usuarios'    => $movs->pluck('user_id')->filter()->unique()->count(),
        ];

        // ===== Presupuestos (si hay mes/año) =====
        $presupuestos = collect();
        if ($mes && $año) {
            $presupuestos = Presupuesto::where('mes', $mes)
                ->where('año', $año)
                ->get()
                ->keyBy('fuente');
        }

        // Tarjetas: egresos por fuente (para comparar con presupuestos)
        $gastoPorFuente = $movs->where('tipo','egreso')
            ->groupBy('fuente')
            ->map->sum('monto');

        // ===== Series para GRÁFICAS =====

        // Barra: ingresos vs egresos por día
        $porDia = $movs->groupBy(fn($m) => $m->fecha?->format('Y-m-d') ?? 'sin_fecha')->sortKeys();
        $labelsDias   = $porDia->keys()->values();
        $ingresosDia  = $porDia->map(fn($c) => $c->where('tipo','ingreso')->sum('monto'))->values();
        $egresosDia   = $porDia->map(fn($c) => $c->where('tipo','egreso')->sum('monto'))->values();

        // Helper para construir pares (ingresos/egresos) con las mismas etiquetas
        $buildDualSeries = function($ing, $egr) {
            $labels = $ing->keys()->merge($egr->keys())->unique()->values();
            $seriesIng = $labels->map(fn($l) => (float)($ing[$l] ?? 0))->values();
            $seriesEgr = $labels->map(fn($l) => (float)($egr[$l] ?? 0))->values();
            return [$labels, $seriesIng, $seriesEgr];
        };

        // Fuente
        $ingFuente = $movs->where('tipo','ingreso')->groupBy('fuente')->map->sum('monto');
        $egrFuente = $movs->where('tipo','egreso' )->groupBy('fuente')->map->sum('monto');
        [$labelsFuente, $datosFuenteIng, $datosFuenteEgr] = $buildDualSeries($ingFuente, $egrFuente);

        // Caja (nombre)
        $ingCaja = $movs->where('tipo','ingreso')->groupBy(fn($m) => optional($m->caja)->nombre ?? 'Sin caja')->map->sum('monto');
        $egrCaja = $movs->where('tipo','egreso' )->groupBy(fn($m) => optional($m->caja)->nombre ?? 'Sin caja')->map->sum('monto');
        [$labelsCaja, $datosCajaIng, $datosCajaEgr] = $buildDualSeries($ingCaja, $egrCaja);

        // Categoría (ing = categoriaIngreso, egr = categoriaGasto)
        $ingCat = $movs->where('tipo','ingreso')->groupBy(fn($m) => optional($m->categoriaIngreso)->nombre ?? 'Sin categoría')->map->sum('monto');
        $egrCat = $movs->where('tipo','egreso' )->groupBy(fn($m) => optional($m->categoriaGasto)->nombre   ?? 'Sin categoría')->map->sum('monto');
        [$labelsCat, $datosCatIng, $datosCatEgr] = $buildDualSeries($ingCat, $egrCat);

        // Subcategoría
        $ingSub = $movs->where('tipo','ingreso')->groupBy(fn($m) => optional($m->subcategoriaIngreso)->nombre ?? 'Sin subcategoría')->map->sum('monto');
        $egrSub = $movs->where('tipo','egreso' )->groupBy(fn($m) => optional($m->subcategoriaGasto)->nombre   ?? 'Sin subcategoría')->map->sum('monto');
        [$labelsSub, $datosSubIng, $datosSubEgr] = $buildDualSeries($ingSub, $egrSub);

        return view('contabilidad.index', [
            // catálogos
            'meses'      => $this->meses,
            'años'       => $años,
            'fuentes'    => $this->fuentes,
            'clientes'   => $clientes,
            'usuarios'   => $usuarios,
            'sucursales' => $sucursales,
            'cajas'      => $cajas,

            // resultados
            'movs'           => $movs,
            'kpi'            => $kpi,
            'gastoPorFuente' => $gastoPorFuente,
            'presupuestos'   => $presupuestos,

            // filtros seleccionados
            'mes'             => $mes,
            'año'             => $año,
            'desde'           => $desde,
            'hasta'           => $hasta,
            'tipo'            => $data['tipo']            ?? null,
            'fuente'          => $data['fuente']          ?? null,
            'cliente_id'      => $data['cliente_id']      ?? null,
            'sucursal_id'     => $data['sucursal_id']     ?? null,
            'caja_id'         => $data['caja_id']         ?? null,
            'usuario_id'      => $data['usuario_id']      ?? null,
            'categoria_id'    => $data['categoria_id']    ?? null,
            'subcategoria_id' => $data['subcategoria_id'] ?? null,

            // series para gráficas
            'labelsDias'      => $labelsDias,
            'ingresosDia'     => $ingresosDia,
            'egresosDia'      => $egresosDia,

            'labelsFuente'    => $labelsFuente,
            'datosFuenteIng'  => $datosFuenteIng,
            'datosFuenteEgr'  => $datosFuenteEgr,

            'labelsCaja'      => $labelsCaja,
            'datosCajaIng'    => $datosCajaIng,
            'datosCajaEgr'    => $datosCajaEgr,

            'labelsCat'       => $labelsCat,
            'datosCatIng'     => $datosCatIng,
            'datosCatEgr'     => $datosCatEgr,

            'labelsSub'       => $labelsSub,
            'datosSubIng'     => $datosSubIng,
            'datosSubEgr'     => $datosSubEgr,
        ]);
    }
}
