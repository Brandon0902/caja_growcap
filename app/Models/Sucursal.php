<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';
    protected $primaryKey = 'id_sucursal';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'gerente_id',
        'politica_crediticia',
        'id_usuario',
        'acceso_activo',
    ];

    public function gerente()
    {
        return $this->belongsTo(User::class, 'gerente_id', 'id_usuario');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function cajas()
    {
        return $this->hasMany(Caja::class, 'id_sucursal', 'id_sucursal');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'usuario_sucursal_accesos', 'id_sucursal', 'usuario_id');
    }
}
