<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VistaGanancia extends Model
{
    use HasFactory;

    protected $table = 'vista_ganancias';
    protected $primaryKey = 'id_vista';

    protected $fillable = [
        'periodo',
        'id_sucursal',
        'id_caja',
        'ingresos_negocio',
        'ingresos_personales',
        'costos_directos',
        'gastos_negocio',
        'gastos_personales',
        'otros_ingresos',
        'otros_gastos',
        'utilidad_neta',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }
}
