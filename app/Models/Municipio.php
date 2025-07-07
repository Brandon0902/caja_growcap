<?php
// app/Models/Municipio.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $table = 'municipios';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_estado',
        'nombre',
        'status',
    ];

    protected $casts = [
        'id_estado' => 'integer',
        'status'    => 'integer',
    ];

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }
}
