<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaGasto extends Model
{
    use HasFactory;

    protected $table = 'categorias_gasto';
    protected $primaryKey = 'id_cat_gasto';

    protected $fillable = [
        'nombre',
        'id_usuario',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function subcategorias()
    {
        return $this->hasMany(SubcategoriaGasto::class, 'id_cat_gasto', 'id_cat_gasto');
    }
}
