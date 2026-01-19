<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserAbono;
use App\Models\UserPrestamo;
use Carbon\Carbon;

class UserAbonoSeeder extends Seeder
{
    public function run()
    {
        // Intentamos encontrar el UserPrestamo con ID = 2
        $prestamo = UserPrestamo::find(2);

        if (! $prestamo) {
            $this->command->info('No existe UserPrestamo con ID = 2. Saltando seed de abonos.');
            return;
        }

        $clienteId = $prestamo->id_cliente;
        $total     = $prestamo->cantidad;
        $nPagos    = 4;                          // número de abonos a crear
        $monto     = round($total / $nPagos, 2);
        $hoy       = Carbon::today();

        for ($i = 1; $i <= $nPagos; $i++) {
            UserAbono::create([
                'tipo_abono'         => 'Mensual',
                'fecha_vencimiento'  => $hoy->copy()->addWeeks($i * $prestamo->semanas),
                'user_prestamo_id'   => $prestamo->id,    // FK al UserPrestamo
                'id_cliente'         => $clienteId,
                'num_pago'           => $i,
                'mora_generada'      => 0.00,
                'fecha'              => $hoy->copy()->addWeeks($i * $prestamo->semanas),
                'cantidad'           => $monto,
                'status'             => 0,                // 0 = Pendiente
                'saldo_restante'     => round($total - $monto * $i, 2),
            ]);
        }

        $this->command->info("Sembrados {$nPagos} abonos para user_prestamo #2.");
    }
}
