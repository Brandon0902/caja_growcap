<?php

namespace App\Mail;

use App\Models\UserDeposito;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepositoAprobadoStripeAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserDeposito $deposito,
        public Cliente $cliente
    ) {}

    public function build()
    {
        return $this->subject('Depósito Stripe aprobado (confirmado por admin)')
            ->view('emails.depositos.aprobado_stripe_admin');
    }
}
