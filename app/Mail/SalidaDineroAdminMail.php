<?php

namespace App\Mail;

use App\Models\MovimientoCaja;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SalidaDineroAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MovimientoCaja $movimiento,
        public User $actor,
        public string $accion = 'creado'
    ) {}

    public function build()
    {
        $cajaNombre = $this->movimiento->caja?->nombre ?? 'Caja';
        $subject = $this->accion === 'actualizado'
            ? "Salida de dinero actualizada ({$cajaNombre})"
            : "Salida de dinero registrada ({$cajaNombre})";

        return $this->subject($subject)
            ->view('emails.cajas.salida_admin');
    }
}
