<?php

namespace App\Mail;

use App\Models\UserPrestamo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PrestamoAutorizadoClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserPrestamo $prestamo;

    public function __construct(UserPrestamo $prestamo)
    {
        $this->prestamo = $prestamo;
    }

    public function build()
    {
        $this->prestamo->loadMissing(['cliente', 'plan', 'caja']);

        $id = $this->prestamo->id ?? null;

        return $this->subject("Tu préstamo ha sido autorizado (#{$id})")
            ->view('emails.prestamos.prestamo_autorizado_cliente', [
                'prestamo' => $this->prestamo,
            ]);
    }
}
