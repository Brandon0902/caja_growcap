<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserAhorro;
use Carbon\Carbon;

class AccrueAhorrosDaily extends Command
{
    protected $signature = 'ahorros:accrue';
    protected $description = 'Capitaliza interés diario compuesto sobre saldo a la fecha en ahorros activos';

    public function handle(): int
    {
        $tz  = config('app.timezone', 'UTC');
        $hoy = Carbon::now($tz)->startOfDay();

        $total = 0;

        UserAhorro::query()
            ->where('status', 1) // Activos
            ->whereNotNull('fecha_ultimo_calculo')
            ->where('rendimiento', '>', 0)
            ->where('saldo_fecha', '>', 0)
            ->chunkById(500, function ($lote) use ($hoy, &$total) {
                foreach ($lote as $ah) {
                    // Normaliza fechas
                    $desde = Carbon::parse($ah->fecha_ultimo_calculo)->startOfDay();

                    // Si por alguna razón viene en el futuro, corrige y salta
                    if ($desde->greaterThan($hoy)) {
                        $ah->update(['fecha_ultimo_calculo' => $hoy->toDateString()]);
                        continue;
                    }

                    $dias = $desde->diffInDays($hoy);
                    if ($dias === 0) continue;

                    // Cap de seguridad si el cron estuvo parado
                    $dias = min($dias, 370);

                    $tasaAnual = (float) ($ah->rendimiento ?? 0) / 100.0;
                    $saldoBase = (float) ($ah->saldo_fecha ?? 0);

                    if ($saldoBase <= 0 || $tasaAnual <= 0) {
                        $ah->update(['fecha_ultimo_calculo' => $hoy->toDateString()]);
                        continue;
                    }

                    $rDia       = $tasaAnual / 365.0;
                    $saldoNuevo = $saldoBase * pow(1 + $rDia, $dias);
                    $ganado     = $saldoNuevo - $saldoBase;

                    // Redondeo financiero
                    $saldoNuevo = round($saldoNuevo, 2);
                    $ganado     = round($ganado, 2);

                    $updates = [
                        'saldo_fecha'          => $saldoNuevo,          // capitaliza el saldo “a la fecha”
                        'fecha_ultimo_calculo' => $hoy->toDateString(), // avanza control
                    ];

                    // Suma al acumulado si la columna existe
                    if (array_key_exists('interes_acumulado', $ah->getAttributes())) {
                        $updates['interes_acumulado'] = round(
                            (float) ($ah->interes_acumulado ?? 0) + $ganado,
                            2
                        );
                    }

                    // Importante: NO tocar 'saldo_disponible' (se libera al cierre)
                    // 'rendimiento_generado' es tope/estimado; no se modifica aquí
                    $ah->update($updates);
                    $total++;
                }
            });

        $this->info("Ahorros procesados: {$total}");
        $this->info('Capitalización diaria de ahorros completada.');
        return self::SUCCESS;
    }
}
