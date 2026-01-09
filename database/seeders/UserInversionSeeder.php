<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserInversion;
use Carbon\Carbon;

class UserInversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserInversion::create([
            // Ajusta estos IDs a uno real en tu tabla de clientes y planes
            'id_cliente'       => 1,
            'id_activo'        => 1,
            'fecha_solicitud'  => Carbon::now(),
            'fecha_inicio'     => Carbon::now(),
            'cantidad'         => 100.00,      // monto de la inversión
            'interes'          => 5,           // porcentaje de interés del plan
            'interes_generado' => 100.00 * 5 / 100, 
            'status'           => 5,           // 5 = Invertida (egreso de caja)
            'nota'             => 'Seeder: inversión inicial pequeña',
            'id_usuario'       => 1,           // usuario que “realiza” la inversión
            'id_caja'          => 1,           // caja desde la que se egresa
        ]);
    }
}
