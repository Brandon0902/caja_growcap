<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserDeposito;
use Carbon\Carbon;

class UserDepositosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vaciar tabla para evitar duplicados
        UserDeposito::truncate();

        $now = Carbon::now();

        $depositos = [
            ['cantidad' => 1500.50, 'nota' => 'Depósito inicial',    'deposito' => 'Transferencia', 'status' => 0],
            ['cantidad' => 2000.00, 'nota' => 'Depósito ahorro',      'deposito' => 'Efectivo',      'status' => 0],
            ['cantidad' => 1750.75, 'nota' => 'Segundo depósito',     'deposito' => 'Transferencia', 'status' => 0],
            ['cantidad' => 2200.00, 'nota' => 'Tercer depósito',      'deposito' => 'Efectivo',      'status' => 0],
            ['cantidad' => 2500.00, 'nota' => 'Depósito final',       'deposito' => 'Transferencia', 'status' => 0],
        ];

        foreach ($depositos as $d) {
            UserDeposito::create([
                'id_cliente'     => 1,
                'cantidad'       => $d['cantidad'],
                'fecha_deposito' => $now->copy()->subDays(rand(1,10))->toDateString(),
                'nota'           => $d['nota'],
                'deposito'       => $d['deposito'],
                'id_usuario'     => 1,
                'fecha_alta'     => $now,
                'fecha_edit'     => $now,
                'status'         => $d['status'],
            ]);
        }
    }
}
