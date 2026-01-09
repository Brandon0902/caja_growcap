<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\UserInversion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InversionPagadaClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserInversion $inversion,
        public Cliente $cliente,
        public string $metodo,              // 'saldo' | 'stripe'
        public array $origenes = [],        // detalle normalizado (saldo)
        public array $stripeMeta = []       // opcional (stripe)
    ) {}

    public function build()
    {
        return $this->subject('Tu inversión fue activada')
            ->view('emails.inversiones.pago_confirmado_cliente');
    }
}
