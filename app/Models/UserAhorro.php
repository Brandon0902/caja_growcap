<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAhorro extends Model
{
    use HasFactory;

    protected $table = 'user_ahorro';
    protected $primaryKey = 'id';

    // Si NO quieres que Laravel maneje created_at/updated_at automáticamente:
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'ahorro_id',
        'monto_ahorro',
        'tipo',
        'tiempo',
        'rendimiento',
        'rendimiento_generado',
        'retiros',
        'meses_minimos',
        'fecha_solicitud',
        'fecha_creacion',
        'fecha_inicio',
        'status',
        'saldo_fecha',
        'fecha_ultimo_calculo',
        'fecha_fin',
        'saldo_disponible',
        'cuota',
        'frecuencia_pago',
    ];

    /**
     * Relación con el usuario (cliente).
     */
    public function cliente()
    {
        return $this->belongsTo(User::class, 'id_cliente');
    }

    /**
     * Relación con el tipo de ahorro.
     */
    public function ahorro()
    {
        return $this->belongsTo(Ahorro::class, 'ahorro_id');
    }
}
