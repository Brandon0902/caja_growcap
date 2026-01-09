<?php

namespace App\Mail;

use App\Models\UserInversion;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevaInversionSolicitudMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserInversion $inversion;
    public Cliente $cliente;

    public function __construct(UserInversion $inversion, Cliente $cliente)
    {
        $this->inversion = $inversion;
        $this->cliente   = $cliente;
    }

    public function build()
    {
        $status = (int) ($this->inversion->status ?? 0);

        $subject = match ($status) {
            2       => 'Inversi¨®n activada #'.$this->inversion->id,
            3       => 'Inversi¨®n terminada #'.$this->inversion->id,
            default => 'Nueva solicitud de inversi¨®n #'.$this->inversion->id,
        };

        return $this->subject($subject)
            ->view('emails.inversiones.nueva_solicitud');
    }
}
