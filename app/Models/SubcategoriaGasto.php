<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubcategoriaGasto extends Model
{
    use HasFactory;

    protected $table = 'subcategorias_gasto';
    protected $primaryKey = 'id_sub_gasto';

    protected $fillable = [
        'id_cat_gasto',
        'nombre',
        'id_usuario',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaGasto::class, 'id_cat_gasto', 'id_cat_gasto');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
