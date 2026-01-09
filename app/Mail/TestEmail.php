<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Mensaje que se mostrará en la vista.
     *
     * @var string
     */
    public string $mensaje;

    /**
     * Crea una nueva instancia del mensaje.
     *
     * @param  string  $mensaje
     * @return void
     */
    public function __construct(string $mensaje = 'Hola, este es un correo de prueba de Growcap.')
    {
        $this->mensaje = $mensaje;
    }

    /**
     * Construye el mensaje.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject('Prueba SMTP Growcap')
                    ->view('emails.test'); // resources/views/emails/test.blade.php
    }
}
