<?php

namespace App\Mail;

use App\Models\Gasto;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MovimientoDineroAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Gasto $gasto,
        public User $actor,
        public string $accion = 'creada'
    ) {}

    public function build()
    {
        $subject = match ($this->accion) {
            'actualizada' => 'Movimiento de dinero actualizado',
            'eliminada'   => 'Movimiento de dinero eliminado',
            default       => 'Movimiento de dinero registrado',
        };

        return $this->subject($subject)
            ->view('emails.cajas.movimiento_dinero_admin');
    }
}
