<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prestamo;
use Carbon\Carbon;

class PrestamosTableSeeder extends Seeder
{
    public function run()
    {
        $planes = [
            [
                'id_usuario'    => 1,            // ← aquí
                'periodo'       => '1 mes',
                'semanas'       => 4,
                'interes'       => 3.5,
                'monto_minimo'  => 500,
                'monto_maximo'  => 2000,
                'antiguedad'    => 1,
                'status'        => '1',
                'created_at'    => Carbon::now()->subDays(25),
                'updated_at'    => Carbon::now()->subDays(25),
            ],
            [
                'id_usuario'    => 1,
                'periodo'       => '3 meses',
                'semanas'       => 12,
                'interes'       => 5.0,
                'monto_minimo'  => 2001,
                'monto_maximo'  => 5000,
                'antiguedad'    => 3,
                'status'        => '1',
                'created_at'    => Carbon::now()->subDays(15),
                'updated_at'    => Carbon::now()->subDays(15),
            ],
            [
                'id_usuario'    => 1,
                'periodo'       => '6 meses',
                'semanas'       => 24,
                'interes'       => 7.0,
                'monto_minimo'  => 5001,
                'monto_maximo'  => 10000,
                'antiguedad'    => 6,
                'status'        => '1',
                'created_at'    => Carbon::now()->subDays(5),
                'updated_at'    => Carbon::now()->subDays(5),
            ],
        ];

        foreach ($planes as $plan) {
            Prestamo::create($plan);
        }
    }
}
