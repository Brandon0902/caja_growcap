<?php

namespace App\Mail;

use App\Models\UserDeposito;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepositoStripePagadoAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserDeposito $deposito,
        public ?Cliente $cliente = null,
        public ?string $paymentIntentId = null,
        public ?string $checkoutSessionId = null,
    ) {}

    public function build()
    {
        return $this->subject('Depósito pagado con tarjeta (Stripe) — Pendiente de revisión')
            ->view('emails.depositos.stripe_pagado_admin');
    }
}
