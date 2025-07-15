<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoAhorro extends Model
{
    // Si tu tabla no sigue el plural estándar, especifícalo:
    protected $table = 'movimientos_ahorro';

    // Si NO usas created_at/updated_at automáticos:
    public $timestamps = false;

    // Qué campos puedes rellenar masivamente:
    protected $fillable = [
        'id_ahorro',
        'monto',
        'observaciones',
        'saldo_resultante',
        'fecha',
        'tipo',
        'id_usuario',
    ];

    /** Relación al ahorro padre */
    public function ahorro()
    {
        return $this->belongsTo(UserAhorro::class, 'id_ahorro');
    }

    /** Relación al usuario que registró el movimiento */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
