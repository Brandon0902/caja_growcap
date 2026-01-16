<?php

namespace App\Mail;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RetiroStatusActualizadoAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cliente $cliente;
    /** @var mixed stdClass del retiro (fila de la tabla retiros o retiros_ahorro) */
    public $retiro;
    public int $status;
    public string $origen;

    public function __construct(Cliente $cliente, $retiro, int $status, string $origen)
    {
        $this->cliente = $cliente;
        $this->retiro  = $retiro;
        $this->status  = $status;
        $this->origen  = $origen;
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
        $origenLabel = $this->origen === 'ahorro' ? 'ahorro' : 'inversión';
        $statusLabel = $this->statusLabel($this->status);

        return $this->subject("Retiro de {$origenLabel} actualizado ({$statusLabel})")
            ->view('emails.retiros.status_actualizado_admin')
            ->with([
                'cliente'      => $this->cliente,
                'retiro'       => $this->retiro,
                'origen'       => $this->origen,
                'status'       => $this->status,
                'status_label' => $statusLabel,
            ]);
    }
}
