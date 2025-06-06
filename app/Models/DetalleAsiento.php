<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleAsiento extends Model
{
    use HasFactory;

    protected $table = 'detalle_asientos';
    protected $primaryKey = 'id_detalle';

    protected $fillable = [
        'id_asiento',
        'id_cuenta',
        'monto_debito',
        'monto_credito',
        'id_usuario',
    ];

    public function asiento()
    {
        return $this->belongsTo(Asiento::class, 'id_asiento', 'id_asiento');
    }

    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class, 'id_cuenta', 'id_cuenta');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
