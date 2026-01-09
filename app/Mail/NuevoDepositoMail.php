<?php

namespace App\Mail;

use App\Models\UserDeposito;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevoDepositoMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserDeposito $deposito;
    public Cliente $cliente;
    public ?string $archivoUrl;

    /**
     * Crear una nueva instancia del mensaje.
     */
    public function __construct(UserDeposito $deposito, Cliente $cliente, ?string $archivoUrl = null)
    {
        $this->deposito  = $deposito;
        $this->cliente   = $cliente;
        $this->archivoUrl = $archivoUrl;
    }

    /**
     * Construir el mensaje.
     */
    public function build()
    {
        return $this->subject('Nuevo depósito pendiente #'.$this->deposito->id)
                    ->view('emails.depositos.nuevo_deposito');
    }
}
