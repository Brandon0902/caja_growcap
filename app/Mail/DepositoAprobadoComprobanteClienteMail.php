<?php

namespace App\Mail;

use App\Models\UserDeposito;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepositoAprobadoComprobanteClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserDeposito $deposito,
        public Cliente $cliente
    ) {}

    public function build()
    {
        return $this->subject('Tu depósito fue aprobado')
            ->view('emails.depositos.aprobado_comprobante_cliente');
    }
}
