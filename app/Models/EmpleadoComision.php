<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoComision extends Model
{
    protected $table = 'empleado_comisiones';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_empleado',
        'user_prestamo_id',
        'user_abono_id',
        'id_cliente',
        'num_pago',
        'monto_abono',
        'fecha_pago',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id_empleado'      => 'integer',   // BD bigint unsigned
        'user_prestamo_id' => 'integer',   // BD int unsigned
        'user_abono_id'    => 'integer',   // BD bigint unsigned
        'id_cliente'       => 'integer',   // BD int unsigned
        'num_pago'         => 'integer',
        'monto_abono'      => 'decimal:2',
        'fecha_pago'       => 'datetime',
        'status'           => 'integer',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    /**
     * empleado_comisiones.id_empleado -> usuarios.id_usuario
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_empleado', 'id_usuario');
    }

    /**
     * empleado_comisiones.user_prestamo_id -> user_prestamos.id
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(UserPrestamo::class, 'user_prestamo_id', 'id');
    }

    /**
     * empleado_comisiones.user_abono_id -> user_abonos.id
     */
    public function abono(): BelongsTo
    {
        return $this->belongsTo(UserAbono::class, 'user_abono_id', 'id');
    }

    /**
     * empleado_comisiones.id_cliente -> clientes.id
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id');
    }
}
