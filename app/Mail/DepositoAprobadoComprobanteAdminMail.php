<?php

namespace App\Mail;

use App\Models\UserDeposito;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepositoAprobadoComprobanteAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserDeposito $deposito,
        public Cliente $cliente,
        public ?string $archivoUrl = null
    ) {}

    public function build()
    {
        return $this->subject('Depósito con comprobante aprobado')
            ->view('emails.depositos.aprobado_comprobante_admin');
    }
}
