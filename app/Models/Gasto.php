<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Gasto extends Model
{
    use HasFactory;

    // Tabla y PK por defecto: 'gastos' y 'id' (no hace falta declararlas).

    protected $fillable = [
        'caja_id',          // Origen
        'destino_caja_id',  // Destino (puede ser NULL)
        'tipo',
        'cantidad',
        'concepto',
        'comprobante',      // nombre de archivo almacenado en disk 'gastos'
    ];

    protected $casts = [
        'cantidad'   => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Si quieres exponer automáticamente la URL en JSON/arrays:
    protected $appends = ['comprobante_url'];

    /**
     * Caja de donde sale el dinero.
     */
    public function cajaOrigen()
    {
        return $this->belongsTo(Caja::class, 'caja_id', 'id_caja');
    }

    /**
     * Caja a la que va el dinero (puede ser NULL).
     */
    public function cajaDestino()
    {
        return $this->belongsTo(Caja::class, 'destino_caja_id', 'id_caja');
    }

    /**
     * Accessor: URL pública del comprobante (o null si no hay archivo).
     * Usa el disk 'gastos' ya configurado en filesystems.php.
     */
    public function getComprobanteUrlAttribute(): ?string
    {
        return $this->comprobante
            ? Storage::disk('gastos')->url($this->comprobante)
            : null;
    }
}
