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
    public $timestamps = false;          // la tabla no tiene created_at / updated_at

    protected $fillable = [
        'id_superior',
        'id_padre',
        'nombre',
        'apellido',
        'telefono',
        'email',
        'codigo_cliente',
        'username',
        'pass_reset_guid',
        'pass',          // ← importante para asignación masiva
        'tipo',
        'fecha',
        'fecha_edit',
        'ultimo_acceso',
        'id_usuario',
        'status',
    ];

    protected $casts = [
        'fecha'        => 'date',      // lo convierte a Carbon (sólo fecha)
        'fecha_edit'   => 'datetime',  // Carbon con fecha+hora
        'ultimo_acceso'=> 'datetime',
    ];

    /* ---------- Genera automáticamente el código del cliente ---------- */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cliente) {
            $iniciales = strtoupper(
                Str::substr($cliente->nombre, 0, 1) .
                Str::substr($cliente->apellido, 0, 1)
            );

            do {
                $codigo = $iniciales . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (self::where('codigo_cliente', $codigo)->exists());

            $cliente->codigo_cliente = $codigo;
        });
    }

    /* ------------------ Relaciones ------------------ */
    public function inversiones()
    {
        return $this->hasMany(\App\Models\UserInversion::class, 'id_cliente');
    }

    public function userData()
    {
        return $this->hasOne(\App\Models\UserData::class, 'id_cliente', 'id');
    }
    
}
