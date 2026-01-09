<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreditScoreService
{
    /** Versión del algoritmo */
    private const V = 1;

    /** Rango crudo del modelo por sumatoria de puntos */
    private const RAW_MIN = -5;   // puede bajar a -5 por atrasos >3
    private const RAW_MAX = 89;   // 20+8+15+20+15+11 = 89

    /** Rango de visualización UI */
    private const UI_MIN  = 300;
    private const UI_MAX  = 850;

    /** Pendiente (k) y base de la transformación lineal RAW→UI */
    private function slope(): float
    {
        return (self::UI_MAX - self::UI_MIN) / (self::RAW_MAX - self::RAW_MIN); // ~5.8510638
    }

    private function uiBase(): float
    {
        // UI = base + k * RAW, con base = UI_MIN - k * RAW_MIN
        return self::UI_MIN - $this->slope() * self::RAW_MIN;
    }

    /** Mapea puntaje crudo a escala 300–850 */
    private function toUiScore(int|float $raw): int
    {
        $k    = $this->slope();
        $base = $this->uiBase();
        $ui   = $base + $k * $raw;
        $ui   = max(self::UI_MIN, min(self::UI_MAX, $ui));
        return (int) round($ui);
    }

    /** Clasificación por umbrales de la UI */
    private function rangeFromUiScore(int $ui): string
    {
        if ($ui >= 690) return 'Bueno';
        if ($ui >= 560) return 'Regular';
        return 'Malo';
    }

    public function compute(int $clienteId): array
    {
        $atrasos      = $this->getDelayedPayments($clienteId);
        $loansNoDelay = $this->getLoansNoDelay($clienteId);
        $recSavings   = $this->getRecurringSavings($clienteId);
        $income       = $this->getIncomeData($clienteId);
        $debtRatio    = $this->getDebtRatio($clienteId);
        $monthsAntig  = $this->getSeniorityMonths($clienteId);

        // 1) Atrasos (máx 20)
        if      ($atrasos == 0) { $p1 = 20; }
        elseif  ($atrasos == 1) { $p1 = 10; }
        elseif  ($atrasos == 2) { $p1 = 5;  }
        elseif  ($atrasos == 3) { $p1 = 0;  }
        else                    { $p1 = -5; }

        // 2) Préstamos sin atraso (máx 8)
        $p2 = $loansNoDelay >= 1 ? min(4 + ($loansNoDelay - 1) * 2, 8) : 0;

        // 3) Ahorros recurrentes (máx 15)
        if      ($recSavings >= 5) { $p3 = 15; }
        elseif  ($recSavings >= 3) { $p3 = 10; }
        elseif  ($recSavings >= 1) { $p3 = 5;  }
        else                       { $p3 = 0;  }

        // 4) Estabilidad de ingresos (máx 20)
        $p4 = 0; $descI = 'Sin datos';
        if ($income) {
            if ($income['tipo'] === 'Asalariado')        { $p4 = 20; $descI = 'Ingreso estable (asalariado)'; }
            elseif ($income['tipo'] === 'Independiente') { $p4 = 10; $descI = 'Ingreso variable (independiente)'; }
            else                                         { $p4 = 5;  $descI = 'Ingreso sin datos confiables'; }
        }

        // 5) Nivel de deuda (máx 15)
        if      ($debtRatio < 30) { $p5 = 15; }
        elseif  ($debtRatio < 50) { $p5 = 10; }
        elseif  ($debtRatio < 70) { $p5 = 7;  }
        else                      { $p5 = 4;  }

        // 6) Antigüedad (máx 11)
        if      ($monthsAntig >= 24) { $p6 = 11; }
        elseif  ($monthsAntig >= 12) { $p6 = 9;  }
        elseif  ($monthsAntig >= 6)  { $p6 = 6;  }
        else                         { $p6 = 2;  }

        // Puntaje crudo y mapeado a la UI
        $scoreRaw = $p1 + $p2 + $p3 + $p4 + $p5 + $p6;  // -5 … 89
        $scoreUi  = $this->toUiScore($scoreRaw);        // 300 … 850
        $range    = $this->rangeFromUiScore($scoreUi);

        // ==== Desglose UI por factor (que suma al score UI) ====
        $k       = $this->slope();
        $uiBase  = $this->uiBase();
        $mk = function (string $title, string $detail, int $rawPts) use ($k) {
            $uiPts = round($k * $rawPts, 1); // una décima para minimizar error de redondeo
            return [
                'title'       => $title,
                'detail'      => $detail,
                'raw_points'  => $rawPts,
                'ui_points'   => $uiPts,
                // Para compatibilidad con la vista actual:
                'categoria'   => $title,
                'descripcion' => $detail,
                'puntos'      => $rawPts,
                'points'      => $uiPts,
            ];
        };

        $reasons = [
            $mk(
                'Puntualidad en pagos',
                $atrasos === 0 ? 'No tienes atrasos: esto suma confianza.' : "Tienes {$atrasos} atraso(s) reciente(s).",
                $p1
            ),
            $mk(
                'Historial de préstamos sin atrasos',
                "{$loansNoDelay} préstamo(s) terminado(s) a tiempo.",
                $p2
            ),
            $mk(
                'Ahorros con aportaciones',
                $recSavings > 0 ? "{$recSavings} producto(s) de ahorro con aportes." : 'Sin aportes de ahorro registrados.',
                $p3
            ),
            $mk('Estabilidad de ingresos', $descI, $p4),
            $mk('Uso de capacidad de pago', number_format($debtRatio, 2) . '% del ingreso destinado a deuda.', $p5),
            $mk('Antigüedad de historial', "{$monthsAntig} mes(es) usando nuestros productos.", $p6),
        ];

        // Sumas (con una décima)
        $sumUiFactors = round(array_sum(array_column($reasons, 'ui_points')), 1);
        $uiBaseRounded = round($uiBase, 1);

        return [
            'score'      => $scoreUi,     // para la UI (300–850)
            'score_raw'  => $scoreRaw,    // auditoría
            'range'      => $range,
            'reasons'    => $reasons,     // cada motivo trae raw_points y ui_points
            'ui_meta'    => [
                'base'        => $uiBaseRounded,
                'k'           => round($k, 6),
                'sum_factors' => $sumUiFactors,
                'total'       => $scoreUi,
            ],
            'raw'        => [
                'atrasos'              => $atrasos,
                'prestamos_sin_atraso' => $loansNoDelay,
                'ahorros_recurrentes'  => $recSavings,
                'ingreso_tipo'         => $income['tipo'] ?? null,
                'ingreso_mensual'      => $income['ingreso_mensual'] ?? null,
                'debt_ratio'           => $debtRatio,
                'meses_antiguedad'     => $monthsAntig,
            ],
            'version'    => self::V,
        ];
    }

    /* ====================== Helpers de datos ====================== */

    private function getDelayedPayments(int $clienteId): int
    {
        return (int) DB::table('user_prestamos')
            ->where('id_cliente', $clienteId)
            ->where('status', 5) // en curso
            ->sum('num_atrasos');
    }

    private function getLoansNoDelay(int $clienteId): int
    {
        return (int) DB::table('user_prestamos')
            ->where('id_cliente', $clienteId)
            ->where('status', 6) // terminado
            ->where('num_atrasos', 0)
            ->count();
    }

    private function getRecurringSavings(int $clienteId): int
    {
        return (int) DB::table('user_ahorro')
            ->where('id_cliente', $clienteId)
            ->where('status', '!=', 0)
            ->where(function ($q) {
                $q->where('cuota', '>', 0)->orWhereNotNull('frecuencia_pago');
            })->count();
    }

    private function getIncomeData(int $clienteId): ?array
    {
        $r = DB::table('user_laborales')
            ->where('id_cliente', $clienteId)
            ->orderByDesc('fecha')
            ->select('salario_mensual as ingreso_mensual', 'tipo_salario')
            ->first();

        if (!$r) return null;
        return [
            'ingreso_mensual' => (float) $r->ingreso_mensual,
            'tipo'            => (string) ($r->tipo_salario ?? 'No hay datos'),
        ];
    }

    private function getDebtRatio(int $clienteId): float
    {
        $prestamos = DB::table('user_prestamos')
            ->where('id_cliente', $clienteId)
            ->where('status', 5)
            ->select('id', 'cantidad', 'interes_generado')
            ->get();

        $deuda = 0.0;
        foreach ($prestamos as $p) {
            $abonosPagados = (float) DB::table('user_abonos')
                ->where('user_prestamo_id', $p->id)
                ->where('status', 1)
                ->sum('cantidad');

            $totalPrestamo = (float) $p->cantidad + (float) $p->interes_generado;
            $pendiente     = max(0.0, $totalPrestamo - $abonosPagados);
            $deuda        += $pendiente;
        }

        $ing = $this->getIncomeData($clienteId)['ingreso_mensual'] ?? 0.0;
        if ($ing <= 0) return 100.0;

        return round(($deuda / $ing) * 100, 2);
    }

    private function getSeniorityMonths(int $clienteId): int
    {
        $f = DB::table('user_inversiones')
            ->where('id_cliente', $clienteId)
            ->orderBy('fecha_inicio')
            ->value('fecha_inicio');

        if (!$f) return 0;

        $d1 = Carbon::parse($f)->startOfDay();
        $d2 = now()->startOfDay();
        return $d1->diffInMonths($d2);
    }
}
