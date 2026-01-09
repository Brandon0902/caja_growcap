<?php

namespace App\Mail;

use App\Models\UserPrestamo;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevoPrestamoMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserPrestamo $prestamo;
    public Cliente $cliente;
    public ?array $docsUrls;

    /**
     * Crear una nueva instancia del mensaje.
     *
     * @param  UserPrestamo  $prestamo
     * @param  Cliente       $cliente
     * @param  array|null    $docsUrls  ['solicitud'=>url|null, 'domicilio'=>..., 'ine_frente'=>..., 'ine_reverso'=>...]
     */
    public function __construct(UserPrestamo $prestamo, Cliente $cliente, ?array $docsUrls = null)
    {
        $this->prestamo = $prestamo;
        $this->cliente  = $cliente;
        $this->docsUrls = $docsUrls;
    }

    /**
     * Construir el mensaje.
     */
    public function build()
    {
        return $this->subject('Nueva solicitud de préstamo #'.$this->prestamo->id.' (Pendiente)')
                    ->view('emails.prestamos.nueva_solicitud');
    }
}
