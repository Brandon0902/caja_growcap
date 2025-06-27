<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_superior',
        'id_padre',
        'nombre',
        'apellido',
        'telefono',
        'email',
        // 'codigo_cliente' se genera internamente
        'username',
        'pass_reset_guid',
        'pass',
        'tipo',
        'fecha',
        'fecha_edit',
        'ultimo_acceso',
        'id_usuario',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function($cliente) {
            // Generar código único de cliente
            $iniciales = strtoupper(
                Str::substr($cliente->nombre, 0, 1) .
                Str::substr($cliente->apellido, 0, 1)
            );

            do {
                $digitos = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $codigo  = $iniciales . $digitos;
            } while ( self::where('codigo_cliente', $codigo)->exists() );

            $cliente->codigo_cliente = $codigo;
        });
    }
}
