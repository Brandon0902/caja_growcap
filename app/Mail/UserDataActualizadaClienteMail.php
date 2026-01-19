<?php

namespace App\Mail;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserDataActualizadaClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cliente $cliente;
    public string $seccion;
    public string $actor;

    public function __construct(Cliente $cliente, string $seccion, string $actor)
    {
        $this->cliente = $cliente;
        $this->seccion = $seccion;
        $this->actor = $actor;
    }

    public function build()
    {
        $nombreSeccion = $this->seccion !== '' ? $this->seccion : 'Tus datos';

        return $this->subject("Tus datos fueron actualizados ({$nombreSeccion})")
            ->view('emails.clientes.datos_actualizados_cliente', [
                'cliente' => $this->cliente,
                'seccion' => $this->seccion,
                'actor' => $this->actor,
            ]);
    }
}
