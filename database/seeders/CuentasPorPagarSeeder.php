<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CuentaPorPagar;
use App\Models\CuentaPorPagarDetalle;
use Carbon\Carbon;

class CuentasPorPagarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crear una cuenta por pagar con proveedor_id en null
        $cuenta = CuentaPorPagar::create([
            'id_sucursal'       => 1,
            'id_caja'           => 1,
            'proveedor_id'      => null,    // proveedor opcional
            'monto_total'       => 1000.00,
            'fecha_emision'     => Carbon::now()->subDays(10)->toDateString(),
            'fecha_vencimiento' => Carbon::now()->addDays(20)->toDateString(),
            'estado'            => 'pendiente',
            'descripcion'       => 'Cuentas por pagar inicial de prueba',
            'id_usuario'        => 1,       // Usuario que registra la cuenta
        ]);

        // Crear detalle de amortización
        CuentaPorPagarDetalle::create([
            'cuenta_id'        => $cuenta->id_cuentas_por_pagar,
            'numero_pago'      => 1,
            'fecha_pago'       => Carbon::now()->subDays(5)->toDateString(),
            'saldo_inicial'    => 1000.00,
            'amortizacion_cap' => 500.00,
            'pago_interes'     => 50.00,
            'monto_pago'       => 550.00,
            'saldo_restante'   => 450.00,
            'estado'           => 'pendiente',
            'caja_id'          => 1,
            'semana'           => Carbon::now()->weekOfYear,
        ]);
    }
}
