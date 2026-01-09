<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ahorro extends Model
{
    use HasFactory;

    protected $table = 'ahorros';
    protected $primaryKey = 'id';

    public $timestamps = true; // ✅ porque ya tienes created_at/updated_at

    protected $fillable = [
        'nombre',        // ✅ nuevo
        'meses_minimos',
        'monto_minimo',
        'tipo_ahorro',   // ✅ categoría
        'tasa_vigente',
    ];
}
