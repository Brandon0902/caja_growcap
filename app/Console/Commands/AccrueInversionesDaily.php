<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserInversion;
use Carbon\Carbon;

class AccrueInversionesDaily extends Command
{
    protected $signature = 'inversiones:accrue';
    protected $description = 'Acumula intereses diarios (compuestos) de inversiones activas';

    public function handle(): int
    {
        $tz  = config('app.timezone', 'UTC');
        $hoy = Carbon::now($tz)->startOfDay();

        $total = 0;

        UserInversion::where('status', 2) // Activas
            ->orderBy('id')
            ->chunkById(500, function ($lote) use ($hoy, &$total) {

                foreach ($lote as $inv) {

                    // Inicializaciones seguras
                    $capital = $inv->capital_actual;
                    if ($capital === null) {
                        // si hay inversión inicial, usarla como base
                        if ($inv->inversion !== null && (float)$inv->inversion > 0) {
                            $capital = (float)$inv->inversion;
                        } else {
                            // no hay base, solo marcar fecha y seguir
                            $inv->update(['fecha_ultimo_calculo' => $hoy->toDateString()]);
                            continue;
                        }
                    } else {
                        $capital = (float)$capital;
                    }

                    $fechaUlt = $inv->fecha_ultimo_calculo
                        ? Carbon::parse($inv->fecha_ultimo_calculo)->startOfDay()
                        : null;

                    // Si no hay fecha previa, arranca hoy dejando capital tal cual
                    if (!$fechaUlt) {
                        $inv->update([
                            'capital_actual'       => round($capital, 2),
                            'interes_acumulado'    => round((float)($inv->interes_acumulado ?? 0), 2),
                            'fecha_ultimo_calculo' => $hoy->toDateString(),
                        ]);
                        $total++;
                        continue;
                    }

                    $dias = max(0, $fechaUlt->diffInDays($hoy));
                    if ($dias === 0) {
                        // nada que capitalizar hoy
                        continue;
                    }

                    $tasaAnual = (float) ($inv->rendimiento ?? 0) / 100.0;
                    if ($tasaAnual <= 0 || $capital <= 0) {
                        // solo avanza fecha si no hay tasa o capital válido
                        $inv->update(['fecha_ultimo_calculo' => $hoy->toDateString()]);
                        $total++;
                        continue;
                    }

                    $rDia         = $tasaAnual / 365.0;
                    $capitalNuevo = $capital * pow(1 + $rDia, $dias);
                    $ganado       = $capitalNuevo - $capital;

                    // Redondeo financiero
                    $capitalNuevo = round($capitalNuevo, 2);
                    $ganado       = round($ganado, 2);

                    $inv->update([
                        'capital_actual'       => $capitalNuevo,
                        'interes_acumulado'    => round((float)($inv->interes_acumulado ?? 0) + $ganado, 2),
                        'fecha_ultimo_calculo' => $hoy->toDateString(),
                    ]);

                    $total++;
                }
            });

        $this->info("Inversiones procesadas: {$total}");
        $this->info('Acumulación diaria de inversiones completada.');
        return self::SUCCESS;
    }
}
