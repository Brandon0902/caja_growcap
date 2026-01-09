<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    use HasFactory;

    protected $table = 'movimientos_caja';
    protected $primaryKey = 'id_mov';
    public $timestamps = false;

    protected $fillable = [
        'id_caja',
        'id_usuario',
        'id_sucursal',      // ← para alcance por sucursal
        'tipo_mov',
        'id_cat_ing',
        'id_sub_ing',
        'id_cat_gasto',
        'id_sub_gasto',
        'proveedor_id',
        'origen_id',
        'monto',
        'fecha',
        'descripcion',
        'monto_anterior',
        'monto_posterior',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    /** Relaciones **/

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'id_proveedor');
    }

    // Categoría de ingreso
    public function categoriaIngreso()
    {
        return $this->belongsTo(CategoriaIngreso::class, 'id_cat_ing', 'id_cat_ing');
    }

    // Subcategoría de ingreso (FK local id_sub_ing → PK remota id_sub_ingreso)
    public function subcategoriaIngreso()
    {
        return $this->belongsTo(SubcategoriaIngreso::class, 'id_sub_ing', 'id_sub_ingreso');
    }

    // Categoría de gasto
    public function categoriaGasto()
    {
        return $this->belongsTo(CategoriaGasto::class, 'id_cat_gasto', 'id_cat_gasto');
    }

    // Subcategoría de gasto
    public function subcategoriaGasto()
    {
        return $this->belongsTo(SubcategoriaGasto::class, 'id_sub_gasto', 'id_sub_gasto');
    }
}
