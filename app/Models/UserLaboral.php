<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Usuario;

class UserLaboral extends Model
{
    // Nombre de la tabla
    protected $table = 'user_laborales';

    // Desactivamos timestamps automáticos
    public $timestamps = false;

    // Campos asignables masivamente
    protected $fillable = [
        'id_cliente',
        'empresa_id',
        'direccion',
        'telefono',
        'puesto',
        'fecha_registro',
        'salario_mensual',
        'tipo_salario',
        'estado_salario',
        'tipo_salario_valor',
        'recurrencia_pago',
        'recurrencia_valor',
        'id_usuario',
        'fecha',
    ];

    /**
     * Relación con Cliente (tabla clientes)
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /**
     * Relación con Empresa (tabla empresas)
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Relación con Usuario (tabla usuarios)
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
