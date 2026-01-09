<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetiroAhorro extends Model
{
    protected $table = 'retiros_ahorro';
    public $timestamps = false;

    protected $fillable = [
        'tipo',
        'fecha_aprobacion',
        'id_cliente',
        'fecha_transferencia',
        'created_at',
        'fecha_solicitud',
        'cantidad',
        'id_ahorro',
        'status',
        'id_caja',

        // ✅ NUEVOS
        'descuento_aplicado',
        'rollback_at',
        'rollback_user_id',
    ];

    protected $casts = [
        'cantidad'             => 'float',
        'created_at'           => 'datetime',
        'fecha_solicitud'      => 'datetime',
        'fecha_aprobacion'     => 'datetime',
        'fecha_transferencia'  => 'datetime',
        'status'               => 'int',
        'id_caja'              => 'int',
        'id_cliente'           => 'int',
        'id_ahorro'            => 'int',

        // ✅ NUEVOS
        'descuento_aplicado'   => 'int',
        'rollback_at'          => 'datetime',
        'rollback_user_id'     => 'int',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function ahorro()
    {
        return $this->belongsTo(Ahorro::class, 'id_ahorro');
    }
}
