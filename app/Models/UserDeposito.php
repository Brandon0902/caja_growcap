<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;
use App\Models\User;

class UserDeposito extends Model
{
    // Deshabilitamos timestamps si no usas created_at/updated_at automáticos
    public $timestamps = false;

    // Nombre de la tabla si no sigue la convención pluralizada
    protected $table = 'user_depositos';

    // Campos asignables masivamente
    protected $fillable = [
        'id_cliente',
        'cantidad',
        'fecha_deposito',
        'nota',
        'deposito',
        'id_usuario',
        'fecha_alta',
        'fecha_edit',
        'status',
    ];

    /**
     * Relación con Cliente.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /**
     * Relación con Usuario (administrador).
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
