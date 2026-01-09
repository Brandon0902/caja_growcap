<?php

namespace App\Mail;

use App\Models\UserAhorro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AhorroActivadoAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserAhorro $ahorro,
        public array $clienteData,
        public ?string $planLabel = null,
    ) {}

    public function build()
    {
        return $this->subject('Ahorro activado por admin')
            ->view('emails.ahorros.activado_admin');
    }
}
