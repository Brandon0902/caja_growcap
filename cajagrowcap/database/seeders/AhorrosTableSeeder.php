<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ahorro;
use Carbon\Carbon;

class AhorrosTableSeeder extends Seeder
{
    public function run()
    {
        $planes = [
            [
                'tipo_ahorro'  => 'Mensual',
                'meses_minimos'=> 1,
                'monto_minimo' => 100,
                'tasa_vigente' => 1.5,
                'created_at'   => Carbon::now()->subDays(30),
                'updated_at'   => Carbon::now()->subDays(30),
            ],
            [
                'tipo_ahorro'  => 'Trimestral',
                'meses_minimos'=> 3,
                'monto_minimo' => 500,
                'tasa_vigente' => 4.0,
                'created_at'   => Carbon::now()->subDays(20),
                'updated_at'   => Carbon::now()->subDays(20),
            ],
            [
                'tipo_ahorro'  => 'Anual',
                'meses_minimos'=> 12,
                'monto_minimo' => 1000,
                'tasa_vigente' => 6.5,
                'created_at'   => Carbon::now()->subDays(10),
                'updated_at'   => Carbon::now()->subDays(10),
            ],
        ];

        foreach ($planes as $plan) {
            Ahorro::create($plan);
        }
    }
}
