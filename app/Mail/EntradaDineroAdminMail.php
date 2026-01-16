<?php

namespace App\Mail;

use App\Models\MovimientoCaja;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EntradaDineroAdminMail extends Mailable
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
            ? "Entrada de dinero actualizada ({$cajaNombre})"
            : "Entrada de dinero registrada ({$cajaNombre})";

        return $this->subject($subject)
            ->view('emails.cajas.entrada_admin');
    }
}
