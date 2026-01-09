<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Caja;

class UserDeposito extends Model
{
    public $timestamps = false;

    protected $table = 'user_depositos';

    // Asegúrate de incluir id_caja para que se guarde al crear
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
        'id_caja',      // <-- importante
    ];

    /** Cliente */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id');
    }

    /** Usuario (quien registró) */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    /** Caja asociada al depósito */
    public function caja()
    {
        // En tu esquema Caja usa PK `id_caja`
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }
}
