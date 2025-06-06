<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioSucursalAcceso extends Model
{
    protected $table = 'usuario_sucursal_accesos';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'id_sucursal',
        'acceso_activo',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id_usuario');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }
}
