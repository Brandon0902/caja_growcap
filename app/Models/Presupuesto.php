<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    protected $fillable = ['fuente','mes','año','monto'];
    // opcionalmente:
    protected $casts = [
        'mes'   => 'integer',
        'año'   => 'integer',
        'monto' => 'decimal:2',
    ];
}
