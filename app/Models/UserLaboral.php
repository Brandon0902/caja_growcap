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

    // (Opcional pero recomendado) Definir PK si tu tabla usa "id"
    protected $primaryKey = 'id';

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

    // ✅ Casts para que fecha_registro y fecha se comporten como Carbon
    protected $casts = [
        'id_cliente'         => 'integer',
        'empresa_id'         => 'integer',
        'id_usuario'         => 'integer',
        'salario_mensual'    => 'decimal:2',
        'tipo_salario_valor' => 'integer',
        'recurrencia_valor'  => 'integer',
        'fecha_registro'     => 'datetime',
        'fecha'              => 'datetime',
    ];

    /**
     * Relación con Cliente (tabla clientes)
     */
    public function cliente()
    {
        // (FK id_cliente -> clientes.id)
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id');
    }

    /**
     * Relación con Empresa (tabla empresas)
     */
    public function empresa()
    {
        // (FK empresa_id -> empresas.id)
        return $this->belongsTo(Empresa::class, 'empresa_id', 'id');
    }

    /**
     * Relación con Usuario (tabla users o usuarios)
     *
     * OJO: Ajusta esto según tu BD:
     * - Si tu tabla de usuarios es "users" con PK "id", lo correcto es:
     *   return $this->belongsTo(User::class, 'id_usuario', 'id');
     *
     * - Si de verdad tienes un modelo Usuario con PK "id_usuario", deja así.
     */
    public function usuario()
    {
        // Tu versión original apuntaba a Usuario::class y keys id_usuario -> id_usuario
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
