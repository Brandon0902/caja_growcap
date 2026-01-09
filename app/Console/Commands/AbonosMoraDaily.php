<?php
// app/Console/Commands/AbonosMoraDaily.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserAbono;
use App\Models\UserPrestamo;
use App\Services\MoraService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AbonosMoraDaily extends Command
{
    protected $signature = 'abonos:mora-daily';
    protected $description = 'Actualiza estado/mora de abonos vencidos';

    public function handle(MoraService $mora): int
    {
        $tz   = config('app.timezone', 'UTC');
        $hoy  = Carbon::now($tz)->startOfDay();
        $ayer = (clone $hoy)->subDay();

        $totalMora = 0.0; $procesados = 0;

        // Candidatos: tienen vencimiento pasado (ya “vencidos” a nivel calendario), no pagados
        UserAbono::query()
            ->where('status', '!=', 1) // no pagados (0 pendiente o 2 vencido)
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->chunkById(500, function ($lote) use ($mora, $hoy, $ayer, &$totalMora, &$procesados) {

                foreach ($lote as $a) {
                    $win = $mora->ventana($a, $hoy);

                    // 1) Siempre marcar “vencido” si la fecha actual es posterior al vencimiento
                    if ((int)$a->status !== 1 && (int)$a->status !== 2) {
                        $a->status = 2; // vencido
                        $a->save();
                    }

                    // 2) Si sigue en período de gracia => NO sumar mora todavía
                    if ($win['en_gracia']) {
                        continue;
                    }

                    // 3) Fuera de gracia: capitalizar los días desde último cálculo (o ayer)
                    $last  = $a->mora_last_calc ? Carbon::parse($a->mora_last_calc) : null;
                    $desde = $last ? $last->copy()->addDay()->startOfDay() : $ayer;

                    $calc = $mora->calcularCompuesto($a, $desde, $hoy);
                    if ($calc['dias'] <= 0 || $calc['mora_add'] <= 0) {
                        continue;
                    }

                    DB::transaction(function () use ($a, $calc, $hoy, &$totalMora, &$procesados) {
                        $a->mora_generada = round((float)$a->mora_generada + $calc['mora_add'], 2);
                        $a->mora_dias     = (int)$a->mora_dias + (int)$calc['dias'];
                        $a->mora_last_calc= $hoy->toDateString();
                        $a->status        = 2; // vencido
                        $a->save();

                        $totalMora   = round($totalMora + $calc['mora_add'], 2);
                        $procesados += 1;
                    });
                }
            });

        // Recalcular mora_acumulada por préstamo
        $prestamos = UserAbono::query()
            ->select('user_prestamo_id')
            ->distinct()
            ->pluck('user_prestamo_id');

        foreach ($prestamos as $pid) {
            $sum = (float) UserAbono::where('user_prestamo_id', $pid)->sum('mora_generada');
            UserPrestamo::whereKey($pid)->update(['mora_acumulada' => round($sum, 2)]);
        }

        $this->info("Abonos procesados: {$procesados} | Mora generada hoy: $" . number_format($totalMora, 2));
        return self::SUCCESS;
    }
}
