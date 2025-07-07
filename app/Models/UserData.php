<?php

namespace App\Models;

use App\Models\Cliente;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Documento;
use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    protected $table = 'user_data';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'id_estado',
        'rfc',
        'direccion',
        'id_municipio',
        'colonia',
        'cp',
        'beneficiario',
        'beneficiario_telefono',
        'beneficiario_02',
        'beneficiario_telefono_02',
        'banco',
        'cuenta',
        'nip',
        'fecha_alta',
        'fecha_modificacion',
        'id_usuario',
        'status',
        'porcentaje_1',
        'porcentaje_2',
        'fecha_ingreso',
    ];

    protected $casts = [
        'id_cliente'         => 'integer',
        'id_estado'          => 'integer',
        'id_municipio'       => 'integer',
        'id_usuario'         => 'integer',
        'status'             => 'integer',
        'porcentaje_1'       => 'decimal:2',
        'porcentaje_2'       => 'decimal:2',
        'fecha_alta'         => 'datetime',
        'fecha_modificacion' => 'datetime',
        'fecha_ingreso'      => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'id_municipio', 'id');
    }

    public function documento()
    {
        return $this->hasOne(Documento::class, 'id_cliente', 'id_cliente');
    }

    public function laboral()
    {
        return $this->hasOne(UserLaboral::class, 'id_cliente', 'id_cliente');
    }
}
