<?php
// database/seeders/UserDepositoIngresoSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserDeposito;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use Carbon\Carbon;

class UserDepositoIngresoSeeder extends Seeder
{
    public function run(): void
    {
        $now       = Carbon::now();
        $usuarioId = 1;    // usuario que registra
        $clienteId = 1;    // cliente al que pertenecen los depósitos
        $cajaId    = 1;    // caja donde se ingresa
        $monto     = 200.00;
        $nota      = 'Depósito inicial de prueba';

        // 1) Creamos el depósito
        $deposito = UserDeposito::create([
            'id_cliente'     => $clienteId,
            'cantidad'       => $monto,
            'fecha_deposito' => $now,
            'nota'           => $nota,
            'status'         => 1,        // aprobado
            'id_usuario'     => $usuarioId,
            'id_caja'        => $cajaId,
        ]);

        // 2) Registramos el movimiento en caja
        $caja  = Caja::findOrFail($cajaId);
        $last  = $caja->movimientos()->latest('fecha')->first();
        $antes = $last ? $last->monto_posterior : $caja->saldo_inicial;
        $despues = $antes + $monto;

        MovimientoCaja::create([
            'id_caja'         => $cajaId,
            'tipo_mov'        => 'Ingreso',
            'id_cat_ing'      => null,
            'id_sub_ing'      => null,
            'monto'           => $monto,
            'fecha'           => $now,
            'descripcion'     => "Depósito #{$deposito->id}",
            'monto_anterior'  => $antes,
            'monto_posterior' => $despues,
            'id_usuario'      => $usuarioId,
        ]);

        // 3) Actualizamos el saldo final de la caja
        $caja->update(['saldo_final' => $despues]);
    }
}
