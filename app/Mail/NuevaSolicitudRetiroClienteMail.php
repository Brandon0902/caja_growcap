<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\Retiro;
use App\Models\RetiroAhorro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevaSolicitudRetiroClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cliente $cliente;
    public Retiro|RetiroAhorro $retiro;
    public string $origen; // 'ahorro' | 'inversion'

    public function __construct(Cliente $cliente, Retiro|RetiroAhorro $retiro, string $origen)
    {
        $this->cliente = $cliente;
        $this->retiro  = $retiro;
        $this->origen  = $origen;
    }

    public function build()
    {
        $tipo = $this->origen === 'ahorro' ? 'retiro de ahorro' : 'retiro de inversión';

        return $this->subject('Hemos recibido tu solicitud de ' . $tipo)
                    ->view('emails.retiros.nueva_solicitud_cliente');
    }
}
