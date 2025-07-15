<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketRespuesta extends Model
{
    use HasFactory;

    protected $table = 'ticket_respuestas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'ticket_id',
        'parent_id',
        'id_cliente',
        'id_usuario',
        'respuesta',
        'fecha',
    ];

    // <-- Esto convierte "fecha" en Carbon automáticamente
    protected $casts = [
        'fecha'      => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** El ticket al que pertenece esta respuesta. */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /** Respuesta padre (si es un hilo). */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Hilos hijos (sub-respuestas). */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
                    ->orderBy('fecha', 'asc');
    }

    /** Cliente que respondió (opcional). */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    /** Usuario/admin que respondió (opcional). */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
