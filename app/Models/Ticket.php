<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_cliente',
        'area',
        'asunto',
        'mensaje',
        'adjunto',
        'fecha',
        'fecha_seguimiento',
        'fecha_cierre',
        'id_usuario',
        'status',
    ];

    // <-- Aquí conviertes 'fecha' (y las demás) a Carbon automáticamente
    protected $casts = [
        'fecha'             => 'datetime',
        'fecha_seguimiento' => 'datetime',
        'fecha_cierre'      => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    /**
     * Cliente que abrió el ticket.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /**
     * Usuario/admin que creó el ticket.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Respuestas asociadas (hilo completo).
     */
    public function respuestas()
    {
        return $this->hasMany(TicketRespuesta::class, 'ticket_id')
                    ->whereNull('parent_id')
                    ->orderBy('fecha', 'asc');
    }
}
