<?php

namespace App\Mail;

use App\Models\UserInversion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InversionActivadaClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserInversion $inversion;

    public function __construct(UserInversion $inversion)
    {
        $this->inversion = $inversion;
    }

    public function build()
    {
        // Asegura relaciones para la vista
        $this->inversion->loadMissing(['cliente', 'plan', 'caja']);

        $id = $this->inversion->id ?? null;

        return $this->subject("Tu inversión ha sido activada (#{$id})")
            ->view('emails.inversiones.inversion_activada_cliente', [
                'inversion' => $this->inversion,
            ]);
    }
}
