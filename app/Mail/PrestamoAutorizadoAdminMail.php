<?php

namespace App\Mail;

use App\Models\UserPrestamo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PrestamoAutorizadoAdminMail extends Mailable
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

        return $this->subject("Préstamo autorizado #{$id}")
            ->view('emails.prestamos.prestamo_autorizado_admin', [
                'prestamo' => $this->prestamo,
            ]);
    }
}
