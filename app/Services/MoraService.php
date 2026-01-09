<?php
// app/Services/MoraService.php

namespace App\Services;

use App\Models\UserAbono;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MoraService
{
    /**
     * Tabla real: config_mora
     * - cargo_fijo          DECIMAL(10,2)
     * - porcentaje_mora     DECIMAL(5,2|5,4)  // en BD guardas 10 = 10%
     * - periodo_gracia      INT
     *
     * Alias aquí:
     *   porcentaje_mora  AS pct_mora
     *   periodo_gracia   AS periodo_gracia_dias
     */
    public function getConfig(): ?object
    {
        $cfg = DB::table('config_mora')
            ->selectRaw('
                cargo_fijo,
                porcentaje_mora AS pct_mora,
                periodo_gracia  AS periodo_gracia_dias
            ')
            ->orderByDesc('id')
            ->first();

        return $cfg ?: null;
    }

    /** Intenta obtener la fecha de vencimiento del abono adaptable a distintos esquemas */
    private function getVencimiento(UserAbono $abono): ?Carbon
    {
        $raw = $abono->fecha_vencimiento
            ?? $abono->fecha
            ?? $abono->vencimiento
            ?? null;

        return $raw ? Carbon::parse($raw) : null;
    }

    /**
     * Ventana respecto a $asOf:
     * - tiene_vto: si el abono tiene fecha de vencimiento
     * - en_gracia: si $asOf está dentro del periodo de gracia
     * - gracia_hasta: fecha tope (inclusive) de la gracia
     */
    public function ventana(UserAbono $abono, ?Carbon $asOf = null): array
    {
        $cfg  = $this->getConfig();
        $asOf = ($asOf ?: Carbon::today())->copy()->startOfDay();

        $vto = $this->getVencimiento($abono);
        if (!$vto) {
            return [
                'tiene_vto'    => false,
                'en_gracia'    => false,
                'gracia_hasta' => null,
            ];
        }

        $graciaDias  = (int) data_get($cfg, 'periodo_gracia_dias', 0);
        $graciaHasta = $vto->copy()->addDays($graciaDias)->endOfDay();

        return [
            'tiene_vto'    => true,
            'en_gracia'    => $asOf->lte($graciaHasta),
            'gracia_hasta' => $graciaHasta,
        ];
    }

    /**
     * Mora compuesta diaria:
     *  base += cargo_fijo
     *  interes_d = round(base * pct, 2)
     *  base += interes_d
     *  mora_total += cargo_fijo + interes_d
     */
    public function calcularCompuesto(UserAbono $abono, Carbon $desde, Carbon $hasta): array
    {
        $cfg = $this->getConfig();
        if (!$cfg) return ['dias'=>0, 'mora_add'=>0.0];

        $vto = $this->getVencimiento($abono);
        if (!$vto) return ['dias'=>0, 'mora_add'=>0.0];

        $gracia = (int) data_get($cfg, 'periodo_gracia_dias', 0);
        $start  = $vto->copy()->addDays($gracia)->startOfDay();
        if ($desde->gt($start)) $start = $desde->copy()->startOfDay();

        $end = $hasta->copy()->startOfDay();
        if ($end->lte($start)) return ['dias'=>0, 'mora_add'=>0.0];

        $dias = $start->diffInDays($end);

        $fix = (float) data_get($cfg, 'cargo_fijo', 0.0);

        $pct = (float) data_get($cfg, 'pct_mora', 0.0);
        if ($pct > 1) $pct = $pct / 100.0;

        // base: capital del abono + mora acumulada actual
        $base       = (float)$abono->cantidad + (float)($abono->mora_generada ?? 0.0);
        $mora_total = 0.0;

        for ($i = 0; $i < $dias; $i++) {
            $base       = round($base + $fix, 2);
            $intDia     = round($base * $pct, 2);
            $base       = round($base + $intDia, 2);
            $mora_total = round($mora_total + $fix + $intDia, 2);
        }

        return ['dias' => $dias, 'mora_add' => $mora_total];
    }

    /**
     * Wrapper: calcula mora desde el último cálculo (o ayer) hasta $asOf.
     * - mora_add = lo NUEVO generado.
     * - total = mora_generada(BD) + mora_add
     */
    public function calcular(UserAbono $abono, ?Carbon $asOf = null): array
    {
        // ✅ Si ya está pagado, no genera mora (y no “aplica”)
        if ((int)$abono->status === 1) {
            $pctCfg = $this->getConfig();
            $pct    = (float) data_get($pctCfg, 'pct_mora', 0.0);
            if ($pct > 1) $pct = $pct / 100.0;

            return [
                'aplica'        => false,
                'dias_mora'     => 0,
                'cargo_fijo'    => 0.00,
                'porcentaje'    => $pct,
                'monto_pct'     => 0.00,
                'mora_add'      => 0.00,
                'total'         => 0.00,
                'en_gracia'     => false,
                'gracia_hasta'  => null,
            ];
        }

        $asOf = $asOf ? $asOf->copy()->startOfDay() : Carbon::today();

        $win = $this->ventana($abono, $asOf);
        if ($win['en_gracia'] || !$win['tiene_vto']) {
            $pctCfg = $this->getConfig();
            $pct    = (float) data_get($pctCfg, 'pct_mora', 0.0);
            if ($pct > 1) $pct = $pct / 100.0;

            return [
                'aplica'        => false,
                'dias_mora'     => 0,
                'cargo_fijo'    => 0.00,
                'porcentaje'    => $pct,
                'monto_pct'     => 0.00,
                'mora_add'      => 0.00,
                'total'         => (float)($abono->mora_generada ?? 0.0),
                'en_gracia'     => $win['en_gracia'],
                'gracia_hasta'  => $win['gracia_hasta']?->toDateString(),
            ];
        }

        $desde = $abono->mora_last_calc
            ? Carbon::parse($abono->mora_last_calc)->addDay()->startOfDay()
            : $asOf->copy()->subDay();

        $calc = $this->calcularCompuesto($abono, $desde, $asOf);

        $cfg = $this->getConfig();
        $fix = (float) data_get($cfg, 'cargo_fijo', 0.0);
        $pct = (float) data_get($cfg, 'pct_mora', 0.0);
        if ($pct > 1) $pct = $pct / 100.0;

        $moraPrev  = (float) ($abono->mora_generada ?? 0.0);
        $moraAdd   = (float) ($calc['mora_add'] ?? 0.0);
        $moraTotal = $moraPrev + $moraAdd;

        return [
            'aplica'        => $moraTotal > 0,
            'dias_mora'     => (int) ($calc['dias'] ?? 0),
            'cargo_fijo'    => $fix,
            'porcentaje'    => $pct,
            'monto_pct'     => max(0.0, $moraAdd - $fix),
            'mora_add'      => $moraAdd,
            'total'         => $moraTotal,
            'en_gracia'     => false,
            'gracia_hasta'  => $win['gracia_hasta']?->toDateString(),
        ];
    }
}
