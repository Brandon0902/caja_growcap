<?php
// app/Models/Mensaje.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mensaje extends Model
{
    use HasFactory;

    protected $table = 'mensajes';
    public $timestamps = false; // no created_at/updated_at automáticos

    protected $fillable = [
        'tipo',
        'id_cliente',
        'url',
        'nombre',
        'descripcion',
        'introduccion',
        'img',
        'fecha',
        'fecha_edit',
        'id_usuario',
        'status',
    ];

    /**
     * Cliente asociado al mensaje.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /**
     * Usuario (admin) que creó o editó el mensaje.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
