<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ahorro extends Model
{
    use HasFactory;

    protected $table = 'ahorros';
    protected $primaryKey = 'id';

    // Como la tabla no tiene created_at/updated_at, deshabilitamos timestamps
    public $timestamps = false;

    protected $fillable = [
        'meses_minimos',
        'monto_minimo',
        'tipo_ahorro',
        'tasa_vigente',
    ];
}
