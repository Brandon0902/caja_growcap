<?php

namespace App\Mail;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RetiroAhorroStatusActualizadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cliente $cliente;
    /** @var mixed stdClass del retiro_ahorro (fila de la tabla retiros_ahorro) */
    public $retiro;
    public int $status;

    public function __construct(Cliente $cliente, $retiro, int $status)
    {
        $this->cliente = $cliente;
        $this->retiro  = $retiro;
        $this->status  = $status;
    }

    protected function statusLabel(int $status): string
    {
        return match ($status) {
            0 => 'Pendiente',
            1 => 'Aprobado',
            2 => 'Pagado',
            3 => 'Rechazado',
            default => 'Pendiente',
        };
    }

    public function build()
    {
        return $this->subject('Actualización de tu retiro de ahorro')
            ->view('emails.retiros.status_actualizado_cliente')
            ->with([
                'cliente'      => $this->cliente,
                'retiro'       => $this->retiro,
                'origen'       => 'ahorro',
                'status'       => $this->status,
                'status_label' => $this->statusLabel($this->status),
            ]);
    }
}
