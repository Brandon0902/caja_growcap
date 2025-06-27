<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';
    protected $primaryKey = 'id';

    // Usaremos nuestros campos fecha_creacion / fecha_modificacion
    public $timestamps = true;
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'nombre',
        'rfc',
        'direccion',
        'ciudad',
        'estado',
        'codigo_postal',
        'pais',
        'telefono',
        'email',
        'estatus',
    ];
}
