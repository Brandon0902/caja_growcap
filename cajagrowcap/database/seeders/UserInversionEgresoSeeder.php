<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserInversion;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\CategoriaGasto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UserInversionEgresoSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Crear la inversión con status = 5 (egreso)
        $inversion = UserInversion::create([
            'id_cliente'           => 1,
            'id_activo'            => 1,               // tu plan
            'fecha_solicitud'      => Carbon::now(),
            'fecha_inicio'         => Carbon::now(),
            'inversion'            => 100.00,          // aquí
            'tipo'                 => 1,               // ejemplo de tipo
            'tiempo'               => 4,               // ejemplo de duración
            'rendimiento'          => 5,               // porcentaje
            'rendimiento_generado' => 100.00 * 5 / 100,
            'status'               => 5,               // 5 = Invertida → egreso
            'id_usuario'           => Auth::id() ?? 1,
            'id_caja'              => 1,
        ]);

        // 2) Registrar el egreso en movimientos_caja
        $caja          = Caja::findOrFail($inversion->id_caja);
        $ultimoMov     = $caja->movimientos()->latest('fecha')->first();
        $saldoAnterior = $ultimoMov
            ? $ultimoMov->monto_posterior
            : $caja->saldo_inicial;

        $monto         = $inversion->inversion;
        $saldoPosterior= $saldoAnterior - $monto;

        $cat = CategoriaGasto::firstWhere('nombre', 'Inversiones');

        MovimientoCaja::create([
            'id_caja'        => $caja->id_caja,
            'tipo_mov'       => 'Egreso',
            'id_cat_gasto'   => $cat->id_cat_gasto ?? null,
            'id_sub_gasto'   => null,
            'monto'          => $monto,
            'fecha'          => Carbon::now(),
            'descripcion'    => "Desembolso inversión #{$inversion->id}",
            'monto_anterior' => $saldoAnterior,
            'monto_posterior'=> $saldoPosterior,
            'id_usuario'     => Auth::id() ?? 1,
        ]);

        // 3) Actualizar saldo_final
        $caja->update(['saldo_final' => $saldoPosterior]);
    }
}
