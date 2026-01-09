<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CuentaPorPagarDetalle;
use App\Models\UserAbono;
use Carbon\Carbon;

class MarkOverdueCuentas extends Command
{
    protected $signature = 'cuentas:marcar-vencidos';
    protected $description = 'Marca como VENCIDO todo abono pendiente cuya fecha ya pas¨® (CxP y pr¨¦stamos)';

    public function handle(): int
    {
        $tz  = config('app.timezone', 'UTC');
        $hoy = Carbon::now($tz)->toDateString();

        // --- Cuentas por Pagar ---
        $cxp = CuentaPorPagarDetalle::query()
            ->where('estado', 'pendiente')
            ->whereDate('fecha_pago', '<', $hoy)
            ->update(['estado' => 'vencido']);

        // --- Pr¨¦stamos (UserAbono) ---
        // 0 = Pendiente, 1 = Pagado, 2 = Vencido

        // 1) Con fecha_vencimiento expl¨ªcita
        $prestamos1 = UserAbono::query()
            ->where('status', 0)
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->update(['status' => 2]);

        // 2) Sin fecha_vencimiento -> usar 'fecha'
        $prestamos2 = UserAbono::query()
            ->where('status', 0)
            ->whereNull('fecha_vencimiento')
            ->whereDate('fecha', '<', $hoy)
            ->update(['status' => 2]);

        $this->info("CxP vencidos: {$cxp}");
        $this->info("Pr¨¦stamos vencidos (fecha_vencimiento): {$prestamos1}");
        $this->info("Pr¨¦stamos vencidos (fecha): {$prestamos2}");

        return Command::SUCCESS;
    }
}
