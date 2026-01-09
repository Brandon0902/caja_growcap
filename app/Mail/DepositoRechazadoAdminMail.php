<?php

namespace App\Mail;

use App\Models\UserDeposito;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepositoRechazadoAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserDeposito $deposito,
        public Cliente $cliente,
        public ?string $archivoUrl = null
    ) {}

    public function build()
    {
        return $this->subject('Depósito rechazado')
            ->view('emails.depositos.rechazado_admin');
    }
}
