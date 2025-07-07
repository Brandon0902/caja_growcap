<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\UserInversion;
use App\Models\Inversion;     // modelo de la tabla "inversiones"
use Carbon\Carbon;

class UserInversionSeeder extends Seeder
{
    public function run()
    {
        // 1) Borra todo lo anterior (resetea el auto-increment también)
        //    Asegúrate de desactivar temporalmente FK checks si las hay:
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        UserInversion::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2) Obtén todos los IDs de planes
        $planIds = Inversion::pluck('id')->toArray();

        // 3) Seed de 10 inversiones de usuario
        for ($i = 1; $i <= 10; $i++) {
            UserInversion::create([
                'id_cliente'           => 1,
                'inversion'            => rand(1_000, 10_000),
                // ya no usamos 'tipo' como texto, sino referenciamos el plan:
                'id_activo'            => $planIds[array_rand($planIds)],
                'tiempo'               => rand(1, 12),
                'rendimiento'          => rand(5, 20),
                'rendimiento_generado' => (string) rand(100, 1_000),
                'retiros'              => rand(0, 3),
                'meses_minimos'        => (string) rand(1, 6),
                'retiros_echos'        => rand(0, 2),
                'fecha_solicitud'      => Carbon::now()->subDays(rand(1, 30)),
                'fecha_inicio'         => Carbon::now()->subDays(rand(1, 30))->toDateString(),
                'fecha_alta'           => Carbon::now(),
                'fecha_edit'           => Carbon::now(),
                'deposito'             => 'DEP'.str_pad($i, 3, '0', STR_PAD_LEFT),
                'id_usuario'           => 1,
                'status'               => 1,
            ]);
        }
    }
}
