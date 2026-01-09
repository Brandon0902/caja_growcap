<?php

namespace App\Mail;

use App\Models\UserAhorro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AhorroActivadoClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserAhorro $ahorro,
        public array $clienteData,
        public ?string $planLabel = null,
    ) {}

    public function build()
    {
        return $this->subject('Tu ahorro fue activado')
            ->view('emails.ahorros.activado_cliente');
    }
}
