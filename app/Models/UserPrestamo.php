<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPrestamo extends Model
{
    // Nombre de la tabla
    protected $table = 'user_prestamos';

    // Clave primaria
    protected $primaryKey = 'id';

    // No usamos created_at / updated_at
    public $timestamps = false;

    // Campos asignables en masa
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
        'id_activo',
        'status',
        'aval_status',
        'aval_responded_at',
        'num_atrasos',
        'mora_acumulada',
    ];

    // Casts para fechas y números
    protected $casts = [
        'id_cliente'         => 'integer',
        'aval_id'            => 'integer',
        'cantidad'           => 'decimal:2',
        'tiempo'             => 'integer',
        'interes'            => 'integer',
        'abonos_echos'       => 'integer',
        'id_usuario'         => 'integer',
        'id_activo'          => 'integer',
        'status'             => 'integer',
        'aval_status'        => 'integer',
        'num_atrasos'        => 'integer',
        'fecha_solicitud'    => 'datetime',
        'aval_notified_at'   => 'datetime',
        'fecha_inicio'       => 'date',
        'fecha_seguimiento'  => 'datetime',
        'fecha_edit'         => 'datetime',
        'aval_responded_at'  => 'datetime',
        'mora_acumulada'     => 'decimal:2',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'id_cliente');
    }

    public function aval()
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'aval_id');
    }

    public function prestamo()
    {
        //         FK local   PK remota
        return $this->belongsTo(Prestamo::class, 'id_activo', 'id_prestamo');
    }
}
