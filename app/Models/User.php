<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /** Spatie guard */
    protected $guard_name = 'web';

    /** Tabla / PK */
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    /** Asignación masiva */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',              // enum('admin','cobrador','contador','gerente','otro')
        'fecha_creacion',
        'activo',
        'id_sucursal',
    ];

    /** Ocultos */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Casts */
    protected $casts = [
        'fecha_creacion'     => 'datetime',
        'password'           => 'hashed',
        'activo'             => 'boolean',
        'id_sucursal'        => 'integer',
        'email_verified_at'  => 'datetime',
    ];

    /** Sincroniza enum -> Spatie en create/save */
    protected static function booted(): void
    {
        static::created(fn(User $u) => $u->syncRoleFromEnum());
        static::saved(fn(User $u)   => $u->syncRoleFromEnum());
    }

    /** Helpers de rol */
    public function isAdmin(): bool    { return $this->hasRole('admin'); }
    public function isGerente(): bool  { return $this->hasRole('gerente'); }
    public function isContador(): bool { return $this->hasRole('contador'); }
    public function isCobrador(): bool { return $this->hasRole('cobrador'); }

    /**
     * Sincroniza el rol de Spatie con el enum 'rol' del usuario.
     */
    public function syncRoleFromEnum(): void
    {
        if (!empty($this->rol)) {
            $this->syncRoles([$this->rol]); // escribe/actualiza en model_has_roles
        }
    }

    // ===== Relaciones =====

    /** Sucursal a la que pertenece el usuario (FK id_sucursal) */
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function sucursalGerenciada()
    {
        return $this->hasOne(Sucursal::class, 'gerente_id', 'id_usuario');
    }

    public function sucursalesConAcceso()
    {
        return $this->belongsToMany(
            Sucursal::class,
            'usuario_sucursal_acceso',
            'usuario_id',
            'id_sucursal'
        )->withPivot('acceso_activo');
    }

    /** Cajas a las que el usuario tiene acceso (pivot) */
    public function cajasConAcceso()
    {
        return $this->belongsToMany(
            Caja::class,
            'usuario_caja_acceso',
            'usuario_id',
            'id_caja'
        )->withPivot('acceso_activo');
    }

    /** Alias usado por los scopes de visibilidad (ver_asignadas) */
    public function cajasAsignadas()
    {
        return $this->cajasConAcceso();
    }

    public function cajasResponsable()
    {
        return $this->hasMany(Caja::class, 'responsable_id', 'id_usuario');
    }

    public function sucursalesCreadas()
    {
        return $this->hasMany(Sucursal::class, 'id_usuario', 'id_usuario');
    }

    public function inversiones()
    {
        return $this->hasMany(\App\Models\UserInversion::class, 'id_usuario', 'id_usuario');
    }

    public function ahorros()
    {
        return $this->hasMany(\App\Models\UserAhorro::class, 'id_usuario', 'id_usuario');
    }

    /* ==========================
       ✅ NUEVAS RELACIONES (Comisiones)
       ========================== */

    /**
     * Préstamos donde este usuario es el cobrador/empleado asignado.
     * user_prestamos.id_empleado -> usuarios.id_usuario
     */
    public function prestamosAsignados()
    {
        return $this->hasMany(\App\Models\UserPrestamo::class, 'id_empleado', 'id_usuario');
    }

    /**
     * Comisiones del empleado.
     * empleado_comisiones.id_empleado -> usuarios.id_usuario
     */
    public function comisiones()
    {
        return $this->hasMany(\App\Models\EmpleadoComision::class, 'id_empleado', 'id_usuario');
    }
}
