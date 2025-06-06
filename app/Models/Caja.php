<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'cajas';
    protected $primaryKey = 'id_caja';

    protected $fillable = [
        'id_sucursal',
        'nombre',
        'responsable_id',
        'fecha_apertura',
        'saldo_inicial',
        'fecha_cierre',
        'saldo_final',
        'estado',
        'id_usuario',
        'acceso_activo',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id', 'id_usuario');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'id_caja', 'id_caja');
    }
}
