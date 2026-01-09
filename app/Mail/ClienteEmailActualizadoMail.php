<?php

namespace App\Mail;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClienteEmailActualizadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cliente $cliente,
        public ?string $oldEmail = null,
    ) {}

    public function build()
    {
        return $this->subject('Tu correo fue actualizado')
            ->view('emails.clientes.email_actualizado_cliente');
    }
}
