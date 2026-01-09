<?php

namespace App\Mail;

use App\Models\MovimientoCaja;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MovimientoCajaNotificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MovimientoCaja $movimiento,
        public User $actor,
        public string $accion = 'creado' // creado | actualizado
    ) {}

    public function build()
    {
        $cajaNombre = $this->movimiento->caja?->nombre ?? 'Caja';
        $subject = $this->accion === 'actualizado'
            ? "Movimiento de caja actualizado ({$cajaNombre})"
            : "Movimiento de caja registrado ({$cajaNombre})";

        return $this->subject($subject)
            ->view('emails.cajas.movimiento_notificacion');
    }
}
