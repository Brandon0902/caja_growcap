<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Retiro;
use App\Models\RetiroAhorro;
use Carbon\Carbon;

class RetirosSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // 3 retiros de inversión (cliente_id = 1)
        $inversiones = [
            ['id' => 5, 'monto' => 4665.00],
            ['id' => 3, 'monto' => 4724.00],
            ['id' => 8, 'monto' => 9710.00],
        ];

        foreach ($inversiones as $inv) {
            Retiro::create([
                'id_cliente'         => 1,
                'tipo'               => 'Transferencia',
                'cantidad'           => $inv['monto'],
                'fecha_solicitud'    => $now->copy()->subDays(3),
                'fecha_aprobacion'   => $now->copy()->subDays(2),
                'fecha_transferencia'=> $now->copy()->subDay(),
                'id_usuario'         => 1,
                'status'             => 0,          // 0 = Solicitado
            ]);
        }

        // 3 retiros de ahorro (cliente_id = 1)
        $ahorros = [
            ['id' => 17, 'monto' => 7644.17],
            ['id' => 14, 'monto' => 1029.39],
            ['id' => 2,  'monto' => 2849.38],
        ];

        foreach ($ahorros as $ah) {
            RetiroAhorro::create([
                'id_cliente'         => 1,
                'tipo'               => 'Transferencia',
                'cantidad'           => $ah['monto'],
                'created_at'         => $now->copy()->subDays(3),
                'fecha_solicitud'    => $now->copy()->subDays(2),
                'fecha_aprobacion'   => $now->copy()->subDay(),
                'fecha_transferencia'=> $now,
                'id_ahorro'          => $ah['id'],
                'status'             => 0,          // 0 = Solicitado
            ]);
        }
    }
}
