<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inversion;
use Carbon\Carbon;

class InversionesTableSeeder extends Seeder
{
    public function run()
    {
        $planes = [
            [
                'periodo'       => 3,
                'meses_minimos' => 3,
                'monto_minimo'  => 1000,
                'monto_maximo'  => 5000,
                'rendimiento'   => 5,
                'fecha'         => Carbon::now()->subDays(30),
                'fecha_edit'    => Carbon::now()->subDays(30),
                'id_usuario'    => 1,
                'status'        => 1,
            ],
            [
                'periodo'       => 6,
                'meses_minimos' => 6,
                'monto_minimo'  => 5001,
                'monto_maximo'  => 10000,
                'rendimiento'   => 7,
                'fecha'         => Carbon::now()->subDays(20),
                'fecha_edit'    => Carbon::now()->subDays(20),
                'id_usuario'    => 1,
                'status'        => 1,
            ],
            [
                'periodo'       => 12,
                'meses_minimos' => 12,
                'monto_minimo'  => 10001,
                'monto_maximo'  => 50000,
                'rendimiento'   => 10,
                'fecha'         => Carbon::now()->subDays(10),
                'fecha_edit'    => Carbon::now()->subDays(10),
                'id_usuario'    => 1,
                'status'        => 1,
            ],
            // …añade hasta 10 planes si quieres
        ];

        foreach ($planes as $plan) {
            Inversion::create($plan);
        }
    }
}
