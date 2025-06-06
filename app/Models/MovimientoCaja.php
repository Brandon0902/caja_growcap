<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    use HasFactory;

    protected $table = 'movimientos_caja';
    protected $primaryKey = 'id_mov';

    protected $fillable = [
        'id_caja',
        'tipo_mov',
        'id_cat_ing',
        'id_sub_ing',
        'id_cat_gasto',
        'id_sub_gasto',
        'proveedor_id',
        'monto',
        'fecha',
        'descripcion',
        'monto_anterior',
        'monto_posterior',
        'id_usuario',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'id_proveedor');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
