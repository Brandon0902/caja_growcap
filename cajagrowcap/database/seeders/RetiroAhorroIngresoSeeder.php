<?php
// database/seeders/RetiroAhorroIngresoSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RetiroAhorro;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use Carbon\Carbon;

class RetiroAhorroIngresoSeeder extends Seeder
{
    public function run(): void
    {
        $now       = Carbon::now();
        $clienteId = 1;     // cliente al que pertenece el retiro
        $cajaId    = 1;     // caja de donde se retira
        $monto     = 150.00;
        $usuarioId = 1;     // usuario que registra el movimiento

        // 1) Creamos el retiro de ahorro
        $retiroAh = RetiroAhorro::create([
            'id_cliente'         => $clienteId,
            'tipo'               => 'Ahorro',
            'cantidad'           => $monto,
            'fecha_solicitud'    => $now,
            'fecha_aprobacion'   => $now,
            'fecha_transferencia'=> $now,
            'status'             => 1,    // aprobado
            'id_caja'            => $cajaId,
        ]);

        // 2) Registramos el egreso en la caja, incluyendo id_usuario
        $caja   = Caja::findOrFail($cajaId);
        $last   = $caja->movimientos()->latest('fecha')->first();
        $antes  = $last ? $last->monto_posterior : $caja->saldo_inicial;
        $despues= $antes - $monto;

        MovimientoCaja::create([
            'id_caja'         => $cajaId,
            'tipo_mov'        => 'Egreso',
            'id_cat_gasto'    => null,
            'id_sub_gasto'    => null,
            'monto'           => $monto,
            'fecha'           => $now,
            'descripcion'     => "Retiro ahorro #{$retiroAh->id}",
            'monto_anterior'  => $antes,
            'monto_posterior' => $despues,
            'id_usuario'      => $usuarioId,   // ← agregado
        ]);

        // 3) Actualizamos el saldo_final de la caja
        $caja->update(['saldo_final' => $despues]);
    }
}
