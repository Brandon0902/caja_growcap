<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TicketsTableSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $areas = ['General', 'Soporte', 'Retiros', 'Inversiones', 'Ahorros'];

        $tickets = [];

        for ($i = 1; $i <= 5; $i++) {
            $tickets[] = [
                'id_cliente'         => 1,
                'area'               => $areas[array_rand($areas)],
                'asunto'             => "Asunto de ticket #{$i}",
                'mensaje'            => "Este es el detalle del ticket de soporte número {$i}.",
                'adjunto'            => null,
                'fecha'              => $now,
                'fecha_seguimiento'  => null,
                'fecha_cierre'       => null,
                'id_usuario'         => 1,
                'status'             => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        DB::table('tickets')->insert($tickets);

        $this->command->info('🆕 5 tickets sembrados correctamente en la tabla tickets.');
    }
}
