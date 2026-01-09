<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retiro extends Model
{
    protected $table = 'retiros';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'tipo',
        'cantidad',
        'fecha_solicitud',
        'fecha_aprobacion',
        'fecha_transferencia',
        'id_usuario',
        'status',
        'id_caja',

        // ✅ NUEVOS
        'id_user_inversion',
        'descuento_aplicado',
        'rollback_at',
        'rollback_user_id',
    ];

    protected $casts = [
        'cantidad'             => 'float',
        'fecha_solicitud'      => 'datetime',
        'fecha_aprobacion'     => 'datetime',
        'fecha_transferencia'  => 'datetime',
        'status'               => 'int',
        'id_caja'              => 'int',
        'id_cliente'           => 'int',
        'id_usuario'           => 'int',

        // ✅ NUEVOS
        'id_user_inversion'    => 'int',
        'descuento_aplicado'   => 'int',
        'rollback_at'          => 'datetime',
        'rollback_user_id'     => 'int',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
