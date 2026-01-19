<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class UserAhorroSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Listado de IDs de ahorros disponibles
        $ahorroIds = DB::table('ahorros')->pluck('id')->toArray();

        // Inserta 10 registros con id_cliente = 1
        for ($i = 0; $i < 10; $i++) {
            DB::table('user_ahorro')->insert([
                'id_cliente'           => 1,
                'ahorro_id'            => $faker->randomElement($ahorroIds),
                'monto_ahorro'         => $faker->randomFloat(2, 100, 10000),
                'tipo'                 => $faker->numberBetween(1,3),
                'tiempo'               => $faker->numberBetween(1,60),
                'rendimiento'          => $faker->randomFloat(2, 0.5, 15),
                'rendimiento_generado' => $faker->randomFloat(2, 0, 500),
                'retiros'              => $faker->numberBetween(0,5),
                'meses_minimos'        => $faker->numberBetween(1,12),
                'fecha_solicitud'      => $faker->dateTimeBetween('-1 years', 'now'),
                'fecha_creacion'       => now(),
                'fecha_inicio'         => $faker->dateTimeBetween('-1 years', 'now'),
                'status'               => $faker->randomElement([0,1]),
                'saldo_fecha'          => $faker->randomFloat(2, 0, 10000),
                'fecha_ultimo_calculo' => $faker->date(),
                'fecha_fin'            => $faker->dateTimeBetween('now', '+1 years'),
                'saldo_disponible'     => $faker->randomFloat(2, 0, 10000),
                'cuota'                => $faker->randomFloat(2, 0, 1000),
                'frecuencia_pago'      => $faker->randomElement(['Mensual','Quincenal','Semanal']),
            ]);
        }
    }
}
