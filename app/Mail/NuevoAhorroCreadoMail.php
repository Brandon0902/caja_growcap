<?php

namespace App\Mail;

use App\Models\UserAhorro;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevoAhorroCreadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserAhorro $ahorro;
    public Cliente $cliente;

    /**
     * Crear una nueva instancia del mensaje.
     */
    public function __construct(UserAhorro $ahorro, Cliente $cliente)
    {
        $this->ahorro  = $ahorro;
        $this->cliente = $cliente;
    }

    /**
     * Construye el mensaje.
     */
    public function build()
    {
        return $this->subject('Nuevo ahorro creado #'.$this->ahorro->id)
                    ->view('emails.ahorros.nuevo_ahorro');
    }
}
