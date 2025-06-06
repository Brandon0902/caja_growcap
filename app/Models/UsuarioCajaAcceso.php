<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioCajaAcceso extends Model
{
    protected $table = 'usuario_caja_accesos';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'id_caja',
        'acceso_activo',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id_usuario');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }
}
