<?php

namespace App\Mail;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserDataActualizadaAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cliente $cliente;
    public string $seccion;
    public string $actor;
    public ?string $tab;

    public function __construct(Cliente $cliente, string $seccion, string $actor, ?string $tab = null)
    {
        $this->cliente = $cliente;
        $this->seccion = $seccion;
        $this->actor = $actor;
        $this->tab = $tab;
    }

    public function build()
    {
        $nombreSeccion = $this->seccion !== '' ? $this->seccion : 'Datos del cliente';

        return $this->subject("Datos actualizados ({$nombreSeccion})")
            ->view('emails.clientes.datos_actualizados_admin', [
                'cliente' => $this->cliente,
                'seccion' => $this->seccion,
                'actor' => $this->actor,
                'tab' => $this->tab,
            ]);
    }
}
