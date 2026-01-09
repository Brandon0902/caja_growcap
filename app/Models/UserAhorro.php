<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAhorro extends Model
{
    use HasFactory;

    protected $table      = 'user_ahorro';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'id_cliente',
        'ahorro_id',
        'monto_ahorro',
        'tipo',
        'tiempo',
        'rendimiento',
        'interes_acumulado',
        'rendimiento_generado',
        'retiros',
        'meses_minimos',
        'fecha_solicitud',
        'fecha_creacion',
        'fecha_inicio',
        'status',
        'id_usuario',
        'id_caja',
        'saldo_fecha',
        'fecha_ultimo_calculo',
        'fecha_fin',
        'saldo_disponible',
        'cuota',
        'frecuencia_pago',

        // Si EXISTEN en tu BD, descomenta:
        // 'retiros_echos',
        // 'nota',

        // ==== Stripe / pagos ====
        'stripe_subscription_id',
        'stripe_status',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_invoice_id',
        'stripe_paid_at',
        'payment_method',
        'payment_status',
        'fecha_pago',
    ];

    protected $casts = [
        'id_cliente'            => 'integer',
        'ahorro_id'             => 'integer',
        'monto_ahorro'          => 'decimal:2',
        'tipo'                  => 'integer',
        'tiempo'                => 'integer',

        // decimal(7,5) -> conserva 5 decimales
        'rendimiento'           => 'decimal:5',

        'interes_acumulado'     => 'decimal:2',

        // decimal(18,4) -> conserva 4 decimales
        'rendimiento_generado'  => 'decimal:4',

        'retiros'               => 'integer',
        'meses_minimos'         => 'integer',

        // Si EXISTE en tu BD:
        // 'retiros_echos'         => 'integer',

        'fecha_solicitud'       => 'datetime',
        'fecha_creacion'        => 'datetime',
        'fecha_inicio'          => 'datetime',

        'status'                => 'integer',
        'id_usuario'            => 'integer',
        'id_caja'               => 'integer',

        'saldo_fecha'           => 'decimal:2',
        'fecha_ultimo_calculo'  => 'date',
        'fecha_fin'             => 'datetime',

        'saldo_disponible'      => 'decimal:2',
        'cuota'                 => 'decimal:2',
        'frecuencia_pago'       => 'string',

        'stripe_subscription_id'      => 'string',
        'stripe_status'               => 'string',
        'stripe_checkout_session_id'  => 'string',
        'stripe_payment_intent_id'    => 'string',
        'stripe_invoice_id'           => 'string',
        'stripe_paid_at'              => 'datetime',

        'payment_method'         => 'string',
        'payment_status'         => 'string',
        'fecha_pago'             => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id');
    }

    public function ahorro()
    {
        return $this->belongsTo(Ahorro::class, 'ahorro_id', 'id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }

    public function movimientos()
    {
        $id = $this->getKey();

        return $this->hasMany(MovimientoCaja::class, 'id_caja', 'id_caja')
            ->where(function ($q) use ($id) {
                $q->where('descripcion', 'like', "Depósito ahorro #{$id}%")
                  ->orWhere('descripcion', 'like', "Retiro ahorro #{$id}%");
            });
    }
}
