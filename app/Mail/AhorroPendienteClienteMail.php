<?php

namespace App\Mail;

use App\Models\UserAhorro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AhorroPendienteClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserAhorro $ahorro,
        public array $clienteData,
    ) {}

    public function build()
    {
        return $this->subject('Tu ahorro fue creado (pendiente de revision)')
            ->view('emails.ahorros.pendiente_cliente');
    }
}
