<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Tabla y clave primaria personalizados:
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    // Columnas que se pueden asignar en masa
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'fecha_creacion',
        'activo',
    ];

    // Columnas que no queremos exponer en JSON
    protected $hidden = [
        'password',
        // 'remember_token', // elimínalo si tu tabla no lo tiene
    ];

    // Casts para convertir tipos automáticamente
    protected $casts = [
        'fecha_creacion' => 'datetime',
        'password'       => 'hashed',
        'activo'         => 'boolean',
    ];

    /**
     * Relación: si este usuario es “gerente” de alguna sucursal.
     */
    public function sucursalGerenciada()
    {
        return $this->hasOne(Sucursal::class, 'gerente_id', 'id_usuario');
    }

    /**
     * Relación pivote: sucursales a las que tiene acceso este usuario.
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
     * Relación pivote: cajas a las que tiene acceso este usuario.
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
     * Relación: cajas de las cuales este usuario es responsable.
     */
    public function cajasResponsable()
    {
        return $this->hasMany(Caja::class, 'responsable_id', 'id_usuario');
    }

    /**
     * Relación: sucursales que este usuario creó/actualizó (campaña id_usuario en sucursales).
     */
    public function sucursalesCreadas()
    {
        return $this->hasMany(Sucursal::class, 'id_usuario', 'id_usuario');
    }
}
