<?php

namespace App\Mail;

use App\Models\UserAhorro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AhorroInactivoAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserAhorro $ahorro,
        public array $clienteData,
        public ?string $planLabel = null,
    ) {}

    public function build()
    {
        return $this->subject('Ahorro marcado inactivo por admin')
            ->view('emails.ahorros.inactivo_admin');
    }
}
