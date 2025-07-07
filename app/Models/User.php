<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Nombre real de la tabla y clave primaria
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    // Si tu PK es entero autoincremental
    public $incrementing = true;
    protected $keyType = 'int';

    // Tu tabla no usa created_at / updated_at
    public $timestamps = false;

    // Columnas que se pueden llenar en masa
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'fecha_creacion',
        'activo',
    ];

    // Columnas ocultas al serializar
    protected $hidden = [
        'password',
        // 'remember_token',
    ];

    // Conversión automática de tipos
    protected $casts = [
        'fecha_creacion' => 'datetime',
        'password'       => 'hashed',
        'activo'         => 'boolean',
    ];

    /**
     * Inversiones realizadas por el usuario.
     */
    public function inversiones()
    {
        return $this->hasMany(\App\Models\UserInversion::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Ahorros realizados por el usuario.
     */
    public function ahorros()
    {
        return $this->hasMany(\App\Models\UserAhorro::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Si este usuario es “gerente” de alguna sucursal.
     */
    public function sucursalGerenciada()
    {
        return $this->hasOne(Sucursal::class, 'gerente_id', 'id_usuario');
    }

    /**
     * Sucursales a las que tiene acceso (pivote).
     */
    public function sucursalesConAcceso()
    {
        return $this->belongsToMany(
            Sucursal::class,
            'usuario_sucursal_acceso',
            'usuario_id',
            'id_sucursal'
        )->withPivot('acceso_activo');
    }

    /**
     * Cajas a las que tiene acceso (pivote).
     */
    public function cajasConAcceso()
    {
        return $this->belongsToMany(
            Caja::class,
            'usuario_caja_acceso',
            'usuario_id',
            'id_caja'
        )->withPivot('acceso_activo');
    }

    /**
     * Cajas de las cuales este usuario es responsable.
     */
    public function cajasResponsable()
    {
        return $this->hasMany(Caja::class, 'responsable_id', 'id_usuario');
    }

    /**
     * Sucursales creadas/actualizadas por este usuario.
     */
    public function sucursalesCreadas()
    {
        return $this->hasMany(Sucursal::class, 'id_usuario', 'id_usuario');
    }
}
