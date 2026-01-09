<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserAbono extends Model
{
    protected $table      = 'user_abonos';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    protected $fillable = [
        'tipo_abono',
        'fecha_vencimiento',
        'user_prestamo_id',
        'id_cliente',
        'num_pago',
        'mora_generada',
        'fecha',
        'cantidad',
        'status',
        'saldo_restante',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha'             => 'datetime',
        'mora_generada'     => 'decimal:2',
        'cantidad'          => 'decimal:2',
        'saldo_restante'    => 'decimal:2',
        'num_pago'          => 'integer',
        'user_prestamo_id'  => 'integer',
        'id_cliente'        => 'integer',
        'status'            => 'integer',
    ];

    public function userPrestamo(): BelongsTo
    {
        return $this->belongsTo(UserPrestamo::class, 'user_prestamo_id', 'id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id');
    }

    /**
     * ✅ NUEVO (opcional): comisión asociada a este abono (si fue comisionable)
     * empleado_comisiones.user_abono_id -> user_abonos.id
     */
    public function comision(): HasOne
    {
        return $this->hasOne(EmpleadoComision::class, 'user_abono_id', 'id');
    }
}
