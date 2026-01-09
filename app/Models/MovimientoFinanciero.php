<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoFinanciero extends Model
{
    /** Apunta a la VISTA SQL */
    protected $table = 'movimientos_financieros';

    /** La vista no usa autoincrement ni timestamps */
    public $incrementing = false;
    public $timestamps   = false;

    /** PK y tipo */
    protected $primaryKey = 'id';
    protected $keyType    = 'int';

    /** Columnas expuestas por la vista (ajusta si tu vista cambia) */
    protected $fillable = [
        'id',
        'fecha',
        'tipo',             // 'ingreso' | 'egreso'
        'monto',
        'descripcion',
        'fuente',

        // FK opcionales que vienen en la vista
        'cliente_id',
        'sucursal_id',
        'caja_id',
        'user_id',

        // Para categorías (la vista pone el id que corresponda según el tipo)
        'categoria_id',
        'subcategoria_id',
    ];

    protected $casts = [
        'id'              => 'integer',
        'fecha'           => 'datetime',
        'monto'           => 'decimal:2',
        'cliente_id'      => 'integer',
        'sucursal_id'     => 'integer',
        'caja_id'         => 'integer',
        'user_id'         => 'integer',
        'categoria_id'    => 'integer',
        'subcategoria_id' => 'integer',
    ];

    /* =======================
     * Relaciones (para eager load)
     * ======================= */

    /** Cliente (si aplica) */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /** Sucursal (FK: sucursal_id -> sucursales.id_sucursal) */
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id', 'id_sucursal');
    }

    /** Caja (FK: caja_id -> cajas.id_caja) */
    public function caja()
    {
        return $this->belongsTo(Caja::class, 'caja_id', 'id_caja');
    }

    /** Usuario (FK: user_id -> usuarios.id_usuario) */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_usuario');
    }

    /** Categoría de ingreso (FK: categoria_id -> categorias_ingreso.id_cat_ing) */
    public function categoriaIngreso()
    {
        return $this->belongsTo(CategoriaIngreso::class, 'categoria_id', 'id_cat_ing');
    }

    /** Subcategoría de ingreso (FK: subcategoria_id -> subcategorias_ingreso.id_sub_ing) */
    public function subcategoriaIngreso()
    {
        return $this->belongsTo(SubcategoriaIngreso::class, 'subcategoria_id', 'id_sub_ing');
    }

    /** Categoría de gasto (FK: categoria_id -> categorias_gasto.id_cat_gasto) */
    public function categoriaGasto()
    {
        return $this->belongsTo(CategoriaGasto::class, 'categoria_id', 'id_cat_gasto');
    }

    /** Subcategoría de gasto (FK: subcategoria_id -> subcategorias_gasto.id_sub_gasto) */
    public function subcategoriaGasto()
    {
        return $this->belongsTo(SubcategoriaGasto::class, 'subcategoria_id', 'id_sub_gasto');
    }
}
