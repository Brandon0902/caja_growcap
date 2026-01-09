<?php

namespace App\Mail;

use App\Models\UserDeposito;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepositoStripePagadoClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserDeposito $deposito,
        public Cliente $cliente
    ) {}

    public function build()
    {
        return $this->subject('Pago de depósito recibido')
            ->view('emails.depositos.stripe_pagado_cliente');
    }
}
