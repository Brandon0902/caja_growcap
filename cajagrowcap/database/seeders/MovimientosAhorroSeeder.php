<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MovimientosAhorroSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // Obtenemos hasta 3 ahorros del cliente 1
        $ahorroIds = DB::table('user_ahorro')
                       ->where('id_cliente', 1)
                       ->limit(3)
                       ->pluck('id')
                       ->toArray();

        foreach ($ahorroIds as $idAhorro) {
            // Creamos 5 movimientos por cada ahorro
            for ($i = 1; $i <= 5; $i++) {
                $monto = mt_rand(100, 2000) / 1.0;
                DB::table('movimientos_ahorro')->insert([
                    'id_ahorro'         => $idAhorro,
                    'monto'             => $monto,
                    'observaciones'     => 'Movimiento de prueba #' . $i,
                    'saldo_resultante'  => round(5000 - ($i * $monto), 2),
                    'fecha'             => $now->copy()->subDays($i * 3),
                    'tipo'              => ($i % 2 === 0) ? 'Abono' : 'Retiro',
                    'id_usuario'        => 1,
                ]);
            }
        }

        $this->command->info('Sembrados movimientos de ahorro para el cliente 1.');
    }
}
