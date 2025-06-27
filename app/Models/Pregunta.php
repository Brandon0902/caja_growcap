<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    use HasFactory;

    protected $table = 'preguntas';
    protected $primaryKey = 'id';
    public $timestamps = false;  // porque usamos 'fecha' manual

    protected $fillable = [
        'pregunta',
        'respuesta',
        'categoria',
        'img',
        'id_usuario',
        'fecha',
        'status',
    ];
}
