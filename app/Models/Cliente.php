<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // ✅
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use App\Services\VisibilityScope;

class Cliente extends Authenticatable
{
    use HasApiTokens, HasFactory;

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
        'codigo_cliente',
        'user',
        'pass_reset_guid',
        'pass',
        'tipo',
        'fecha',
        'fecha_edit',
        'ultimo_acceso',
        'id_usuario',
        'id_sucursal',
        'status',
    ];

    protected $hidden = [
        'pass',
        'pass_reset_guid',
        'remember_token', // por si existe en algún momento
    ];

    protected $casts = [
        'fecha'         => 'date',
        'fecha_edit'    => 'datetime',
        'ultimo_acceso' => 'datetime',
        'id_sucursal'   => 'integer',
        'status'        => 'boolean',

        // ✅ Recomendado: cuando asignes pass, lo hashea
        // (si tu login aún compara texto plano, primero ajústalo a Hash::check)
        'pass'          => 'hashed',
    ];

    /**
     * ✅ Laravel debe usar `pass` como contraseña para autenticar.
     */
    public function getAuthPassword()
    {
        return $this->pass;
    }

    public function getAuthPasswordName()
    {
        return 'pass';
    }

    /**
     * Genera un código de cliente si no viene.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Cliente $cliente) {
            if (empty($cliente->codigo_cliente)) {
                $iniciales = strtoupper(
                    Str::substr((string) $cliente->nombre, 0, 1) .
                    Str::substr((string) $cliente->apellido, 0, 1)
                );

                do {
                    $codigo = $iniciales . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                } while (self::where('codigo_cliente', $codigo)->exists());

                $cliente->codigo_cliente = $codigo;
            }
        });
    }

    /* =========================
     |  Relaciones
     * ========================= */

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function inversiones()
    {
        return $this->hasMany(\App\Models\UserInversion::class, 'id_cliente', 'id');
    }

    public function userData()
    {
        return $this->hasOne(\App\Models\UserData::class, 'id_cliente', 'id');
    }

    /* =========================
     |  Scope de visibilidad
     * ========================= */

    public function scopeVisibleTo(Builder $query, $user): Builder
    {
        return VisibilityScope::clientes($query, $user);
    }
}
