<?php
// database/seeders/UserAhorroIngresoSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserAhorro;
use App\Models\Ahorro;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\CategoriaIngreso;
use Carbon\Carbon;

class UserAhorroIngresoSeeder extends Seeder
{
    public function run(): void
    {
        $now    = Carbon::now();
        $userId = 1; // fallback si no hay usuario autenticado

        // 1) Traemos el plan maestro de ahorros
        $plan = Ahorro::findOrFail(1);

        // 2) Preparamos valores asegurándonos de que sean numéricos
        $planKey       = $plan->getKey();                                 // La PK real
        $tipoPlan      = $plan->tipo           ?? 1;                     // Entero: si no existe, 1 (mensual)
        $tiempoPlan    = $plan->tiempo         ?? $plan->meses_minimos ?? 1;  // Meses de duración
        $rendPlanPct   = $plan->rendimiento    ?? 0.0;                   // % de rendimiento
        $mesesMinimos  = $plan->meses_minimos  ?? $tiempoPlan;           // Mínimo de meses

        // 3) Cálculo de montos
        $montoBase     = 50.00;
        $rendGen       = $montoBase * $rendPlanPct / 100;
        $retirosHechos = 0;

        // 4) Creamos el registro en user_ahorro
        $ahorro = UserAhorro::create([
            'id_cliente'           => 1,
            'ahorro_id'            => $planKey,
            'monto_ahorro'         => $montoBase,
            'tipo'                 => $tipoPlan,
            'tiempo'               => $tiempoPlan,
            'rendimiento'          => $rendPlanPct,
            'rendimiento_generado' => $rendGen,
            'retiros'              => $retirosHechos,
            'meses_minimos'        => $mesesMinimos,
            'fecha_solicitud'      => $now,
            'fecha_creacion'       => $now,
            'fecha_inicio'         => $now,
            'status'               => 5, // “Depositado” → ingreso
            'id_usuario'           => $userId,
            'id_caja'              => 1,
            'saldo_fecha'          => $montoBase + $rendGen,
            'fecha_ultimo_calculo' => $now,
            'fecha_fin'            => $now->copy()->addMonths($tiempoPlan),
            'saldo_disponible'     => $montoBase + $rendGen,
            'cuota'                => ($montoBase + $rendGen) / max(1, $tiempoPlan),
            'frecuencia_pago'      => $plan->frecuencia_pago ?? 'mensual',
        ]);

        // 5) Registramos el ingreso en la caja
        $caja   = Caja::findOrFail($ahorro->id_caja);
        $ultimo = $caja->movimientos()->latest('fecha')->first();
        $antes  = $ultimo ? $ultimo->monto_posterior : $caja->saldo_inicial;
        $ingreso = $ahorro->monto_ahorro + $ahorro->rendimiento_generado;
        $despues = $antes + $ingreso;

        $cat = CategoriaIngreso::firstWhere('nombre', 'Ahorros');

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'tipo_mov'        => 'Ingreso',
            'id_cat_ing'      => $cat->id_cat_ing ?? null,
            'id_sub_ing'      => null,
            'monto'           => $ingreso,
            'fecha'           => $now,
            'descripcion'     => "Depósito ahorro #{$ahorro->id}",
            'monto_anterior'  => $antes,
            'monto_posterior' => $despues,
            'id_usuario'      => $userId,
        ]);

        // 6) Actualizamos el saldo_final de la caja
        $caja->update(['saldo_final' => $despues]);
    }
}
