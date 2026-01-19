<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubcategoriaIngreso extends Model
{
    use HasFactory;

    protected $table = 'subcategorias_ingreso';
    protected $primaryKey = 'id_sub_ing';
    public $timestamps = false; // ✅ importante

    protected $fillable = [
        'id_cat_ing',
        'nombre',
        'id_usuario',
    ];

    protected $casts = [
        'id_sub_ing' => 'integer',
        'id_cat_ing' => 'integer',
        'id_usuario' => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaIngreso::class, 'id_cat_ing', 'id_cat_ing');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
