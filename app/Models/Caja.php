<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\CuentaPorPagar;
use App\Models\CuentaPorPagarDetalle;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'cajas';
    protected $primaryKey = 'id_caja';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_sucursal',
        'nombre',
        'responsable_id',
        'fecha_apertura',
        'saldo_inicial',
        'fecha_cierre',
        'saldo_final',
        'estado',
        'id_usuario',
        'acceso_activo',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre'   => 'datetime',
    ];

    /** Relaciones **/

    // Cada caja pertenece a una sucursal
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    // Usuario responsable de la caja
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id', 'id_usuario');
    }

    // Usuario que cre¨® la caja
    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    // Movimientos de caja (egresos / ingresos)
    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'id_caja', 'id_caja');
    }

    // Cuentas por pagar asociadas a esta caja (cabeceras)
    public function cuentasPorPagar()
    {
        return $this->hasMany(CuentaPorPagar::class, 'id_caja', 'id_caja');
    }

    // Detalles de pagos (cronograma) registrados en esta caja
    public function detallesCuentasPorPagar()
    {
        return $this->hasMany(CuentaPorPagarDetalle::class, 'caja_id', 'id_caja');
    }
}
