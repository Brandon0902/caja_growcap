<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserPrestamo;
use Carbon\Carbon;

class UserPrestamosTableSeeder extends Seeder
{
    public function run()
    {
        UserPrestamo::create([
            'id_cliente'          => 1,                   // Cliente 1
            'aval_id'             => null,                // sin aval
            'cantidad'            => 5000.00,             // monto del préstamo
            'tipo_prestamo'       => '3 meses',           // opcional, de tu migración
            'tiempo'              => 12,                  // en semanas
            'interes'             => 10,                  // porcentaje entero
            'interes_generado'    => '10%',               // texto descriptivo
            'doc_solicitud_aval'  => null,
            'doc_comprobante_domicilio' => null,
            'doc_ine_frente'      => null,
            'doc_ine_reverso'     => null,
            'semanas'             => '12',
            'abonos_echos'        => 0,
            'fecha_solicitud'     => Carbon::now(),
            'aval_notified_at'    => null,
            'fecha_inicio'        => Carbon::now()->toDateString(),
            'fecha_seguimiento'   => Carbon::now(),
            'fecha_edit'          => Carbon::now(),
            'deposito'            => 'Transferencia',
            'nota'                => 'Préstamo inicial de prueba',
            'id_usuario'          => 1,                   // usuario administrador
            'id_activo'           => 1,                   // tipo de préstamo (prestamos.id = 1)
            'status'              => 1,                   // activo
            'aval_status'         => 1,                   // aprobado
            'aval_responded_at'   => Carbon::now(),
            'num_atrasos'         => 0,
            'mora_acumulada'      => 0.00,
        ]);
    }
}
