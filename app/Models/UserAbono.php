<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAbono extends Model
{
    protected $table      = 'user_abonos';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    protected $fillable = [
        'tipo_abono',
        'fecha_vencimiento',
        'user_prestamo_id',   // ← ahora aquí
        'id_cliente',
        'num_pago',
        'mora_generada',
        'fecha',
        'cantidad',
        'status',
        'saldo_restante',
    ];

    protected $casts = [
        'fecha_vencimiento'  => 'date',
        'fecha'              => 'datetime',
        'mora_generada'      => 'decimal:2',
        'cantidad'           => 'decimal:2',
        'saldo_restante'     => 'decimal:2',
        'num_pago'           => 'integer',
        'user_prestamo_id'   => 'integer',
        'id_cliente'         => 'integer',
    ];

    public function userPrestamo()
    {
        return $this->belongsTo(\App\Models\UserPrestamo::class,
                               'user_prestamo_id', 'id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class,
                               'id_cliente','id');
    }
}
