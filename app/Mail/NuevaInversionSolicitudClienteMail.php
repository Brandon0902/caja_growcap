<?php

namespace App\Mail;

use App\Models\UserInversion;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevaInversionSolicitudClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserInversion $inversion,
        public Cliente $cliente
    ) {}

    public function build()
    {
        return $this->subject('Hemos recibido tu solicitud de inversión')
            ->view('emails.inversiones.nueva_solicitud_cliente');
    }
}
