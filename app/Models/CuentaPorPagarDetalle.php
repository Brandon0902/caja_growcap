<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaPorPagarDetalle extends Model
{
    use HasFactory;

    protected $table = 'cuentas_por_pagar_detalles';

    protected $fillable = [
        'cuenta_id',
        'numero_pago',
        'fecha_pago',
        'saldo_inicial',
        'amortizacion_cap',
        'pago_interes',
        'monto_pago',
        'saldo_restante',
        'estado',
        'caja_id',
        'comentario',      // ← nuevo
        'semana',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
    ];

    public function cuenta()
    {
        return $this->belongsTo(
            CuentaPorPagar::class,
            'cuenta_id',
            'id_cuentas_por_pagar'
        );
    }

    public function caja()
    {
        return $this->belongsTo(
            Caja::class,
            'caja_id',
            'id_caja'
        );
    }
}
