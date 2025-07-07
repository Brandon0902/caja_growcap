<?php
// app/Models/Estado.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    protected $table = 'estados';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'fecha_creacion',
        'fecha_edicion',
        'id_usuario',
        'fecha_edit',
        'status',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_edicion'  => 'datetime',
        'fecha_edit'     => 'datetime',
        'id_usuario'     => 'integer',
        'status'         => 'integer',
    ];

    public function municipios()
    {
        return $this->hasMany(Municipio::class, 'id_estado', 'id');
    }
}
