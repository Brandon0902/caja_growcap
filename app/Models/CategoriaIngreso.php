<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaIngreso extends Model
{
    use HasFactory;

    protected $table = 'categorias_ingreso';
    protected $primaryKey = 'id_cat_ing';

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
        return $this->hasMany(SubcategoriaIngreso::class, 'id_cat_ing', 'id_cat_ing');
    }
}
