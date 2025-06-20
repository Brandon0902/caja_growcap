<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Asegúrate de importar estas dos clases
use App\Models\User;
use App\Models\Caja;

class Sucursal extends Model
{
    use HasFactory;

    // Nombre de la tabla y PK personalizados
    protected $table = 'sucursales';
    protected $primaryKey = 'id_sucursal';

    // Si tu tabla no tiene created_at/updated_at, descomenta esta línea:
    // public $timestamps = false;

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'gerente_id',
        'politica_crediticia',
        'id_usuario',
        'acceso_activo',
    ];

    /**
     * La sucursal tiene un gerente (usuario).
     */
    public function gerente()
    {
        return $this->belongsTo(User::class, 'gerente_id', 'id_usuario');
    }

    /**
     * El usuario que creó o actualizó la sucursal.
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Relación 1:N con Caja.
     */
    public function cajas()
    {
        return $this->hasMany(Caja::class, 'id_sucursal', 'id_sucursal');
    }

    /**
     * Usuarios que tienen acceso a la sucursal (pivote usuario_sucursal_acceso).
     * Incluimos el campo 'acceso_activo' del pivote para poder consultarlo.
     */
    public function usuariosConAcceso()
    {
        return $this->belongsToMany(
            User::class,
            'usuario_sucursal_acceso', // nombre exacto de la tabla pivote
            'id_sucursal',
            'usuario_id'
        )
        ->withPivot('acceso_activo');
    }
}
