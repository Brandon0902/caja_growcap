<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sucursal;
use App\Models\Caja;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\CuentaPorPagarDetalle;

class CuentaPorPagar extends Model
{
    use HasFactory;

    protected $table = 'cuentas_por_pagar';
    protected $primaryKey = 'id_cuentas_por_pagar';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'id_sucursal',
        'id_caja',
        'proveedor_id',
        'monto_total',
        'tasa_anual',
        'numero_abonos',
        'periodo_pago',
        'fecha_emision',
        'fecha_vencimiento',
        'estado',
        'descripcion',
        'id_usuario',
    ];

    protected $casts = [
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
        'tasa_anual'        => 'decimal:2',
        'numero_abonos'     => 'integer',
    ];

    /**
     * Relaciones
     */

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'id_proveedor');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function detalles()
    {
        return $this->hasMany(CuentaPorPagarDetalle::class, 'cuenta_id', 'id_cuentas_por_pagar');
    }
}
