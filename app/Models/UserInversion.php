<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInversion extends Model
{
    // Nombre de la tabla
    protected $table = 'user_inversiones';

    // Clave primaria
    protected $primaryKey = 'id';

    // No usamos created_at / updated_at
    public $timestamps = false;

    // Campos asignables en masa
    protected $fillable = [
        'id_cliente',
        'inversion',
        'tipo',
        'tiempo',
        'rendimiento',
        'rendimiento_generado',
        'retiros',
        'meses_minimos',
        'retiros_echos',
        'fecha_solicitud',
        'fecha_inicio',
        'fecha_alta',
        'fecha_edit',
        'deposito',
        'id_usuario',
        'id_activo',
        'status',
    ];

    // Casts para fechas y números
    protected $casts = [
        'id_cliente'           => 'integer',
        'inversion'            => 'integer',
        'tiempo'               => 'integer',
        'rendimiento'          => 'integer',
        'retiros'              => 'integer',
        'retiros_echos'        => 'integer',
        'id_usuario'           => 'integer',
        'id_activo'            => 'integer',
        'status'               => 'integer',
        'fecha_solicitud'      => 'datetime',
        'fecha_inicio'         => 'date',
        'fecha_alta'           => 'datetime',
        'fecha_edit'           => 'datetime',
    ];


    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'id_cliente');
    }

    public function plan()
    {
        return $this->belongsTo(\App\Models\Inversion::class, 'id_activo', 'id');
    }

}
