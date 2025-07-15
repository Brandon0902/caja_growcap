<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retiro extends Model
{
    protected $table = 'retiros';
    public $timestamps = false; // No tienes created_at/updated_at

    protected $fillable = [
        'id_cliente',
        'tipo',
        'cantidad',
        'fecha_solicitud',
        'fecha_aprobacion',
        'fecha_transferencia',
        'id_usuario',
        'status',
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
