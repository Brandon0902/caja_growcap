<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPrestamo extends Model
{
    /** Tabla y PK */
    protected $table = 'user_prestamos';
    protected $primaryKey = 'id';
    public $timestamps = false;

    /** Fillable */
    protected $fillable = [
        'id_cliente',
        'aval_id',
        'cantidad',
        'tipo_prestamo',
        'tiempo',
        'interes',
        'interes_generado',
        'doc_solicitud_aval',
        'doc_comprobante_domicilio',
        'doc_ine_frente',
        'doc_ine_reverso',
        'semanas',
        'abonos_echos',
        'fecha_solicitud',
        'aval_notified_at',
        'fecha_inicio',
        'fecha_seguimiento',
        'fecha_edit',
        'deposito',
        'nota',
        'id_usuario',
        'id_empleado',      // ✅ NUEVO
        'id_activo',
        'status',
        'aval_status',
        'aval_responded_at',
        'num_atrasos',
        'mora_acumulada',
        'id_caja',
        'saldo_a_favor',
    ];

    /** Casts */
    protected $casts = [
        'id_cliente'        => 'integer',
        'aval_id'           => 'integer',
        'cantidad'          => 'float',
        'interes'           => 'float',
        'interes_generado'  => 'float',
        'mora_acumulada'    => 'float',
        'tiempo'            => 'integer',
        'semanas'           => 'integer',
        'abonos_echos'      => 'integer',
        'id_usuario'        => 'integer',
        'id_empleado'       => 'integer',  // ✅ NUEVO (en BD es bigint unsigned, en PHP ok)
        'id_activo'         => 'integer',
        'status'            => 'integer',
        'aval_status'       => 'integer',
        'num_atrasos'       => 'integer',
        'id_caja'           => 'integer',
        'fecha_solicitud'   => 'datetime',
        'aval_notified_at'  => 'datetime',
        'fecha_inicio'      => 'datetime',
        'fecha_seguimiento' => 'datetime',
        'fecha_edit'        => 'datetime',
        'aval_responded_at' => 'datetime',
        'saldo_a_favor'     => 'decimal:2',
    ];

    /* ==========================
       Relaciones
       ========================== */

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id');
    }

    public function aval(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'aval_id', 'id');
    }

    public function prestamo(): BelongsTo
    {
        // FK local (user_prestamos.id_activo) -> PK remota (prestamos.id_prestamo)
        return $this->belongsTo(Prestamo::class, 'id_activo', 'id_prestamo');
    }

    /** Alias para "plan" */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'id_activo', 'id_prestamo');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }

    /**
     * Abonos del préstamo.
     * FK real en user_abonos = user_prestamo_id
     */
    public function abonos(): HasMany
    {
        return $this->hasMany(UserAbono::class, 'user_prestamo_id', 'id');
    }

    /**
     * ✅ NUEVO: empleado/cobrador asignado al préstamo
     * user_prestamos.id_empleado -> usuarios.id_usuario
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_empleado', 'id_usuario');
    }

    /**
     * ✅ NUEVO: comisiones asociadas a este préstamo
     */
    public function comisiones(): HasMany
    {
        return $this->hasMany(EmpleadoComision::class, 'user_prestamo_id', 'id');
    }
}
