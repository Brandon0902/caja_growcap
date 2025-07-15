<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetiroAhorro extends Model
{
    protected $table = 'retiros_ahorro';
    public $timestamps = false; // Solo created_at según tu tabla

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
