<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\Retiro;
use App\Models\RetiroAhorro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RetiroStatusActualizadoClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cliente $cliente;
    public Retiro|RetiroAhorro $retiro;
    public string $origen;      // 'ahorro' | 'inversion'
    public int $status;         // 0,1,2,3
    public string $statusTexto; // 'Pendiente', 'Aprobado', etc.
    public string $mensaje;     // mensaje amigable

    public function __construct(
        Cliente $cliente,
        Retiro|RetiroAhorro $retiro,
        string $origen,
        int $status,
        string $statusTexto,
        string $mensaje
    ) {
        $this->cliente     = $cliente;
        $this->retiro      = $retiro;
        $this->origen      = $origen;
        $this->status      = $status;
        $this->statusTexto = $statusTexto;
        $this->mensaje     = $mensaje;
    }

    public function build()
    {
        $tipo = $this->origen === 'ahorro' ? 'retiro de ahorro' : 'retiro de inversión';

        return $this->subject("Actualización de tu $tipo (#{$this->retiro->id})")
                    ->view('emails.retiros.status_actualizado_cliente');
    }
}
