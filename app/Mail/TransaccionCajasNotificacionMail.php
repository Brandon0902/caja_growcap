<?php

namespace App\Mail;

use App\Models\Gasto;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransaccionCajasNotificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Gasto $gasto,
        public User $actor,
        public string $accion = 'creada', // creada | actualizada | eliminada
    ) {}

    public function build()
    {
        $subject = match ($this->accion) {
            'actualizada' => 'Transacción entre cajas actualizada',
            'eliminada'   => 'Transacción entre cajas eliminada',
            default       => 'Transacción entre cajas registrada',
        };

        return $this->subject($subject)
            ->view('emails.cajas.transaccion_notificacion');
    }
}
