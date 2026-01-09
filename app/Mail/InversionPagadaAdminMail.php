<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\UserInversion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InversionPagadaAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserInversion $inversion,
        public Cliente $cliente,
        public array $origenes = []
    ) {}

    public function build()
    {
        return $this->subject('Inversion #'.$this->inversion->id.' pagada con saldo (ACTIVA)')
            ->view('emails.inversiones.pago_confirmado_admin');
    }
}
