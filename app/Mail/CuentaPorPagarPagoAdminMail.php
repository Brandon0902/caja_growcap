<?php

namespace App\Mail;

use App\Models\CuentaPorPagar;
use App\Models\CuentaPorPagarDetalle;
use App\Models\Caja;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CuentaPorPagarPagoAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CuentaPorPagar $cuenta,
        public CuentaPorPagarDetalle $detalle,
        public ?Caja $caja,
        public User $actor,
        public string $accion = 'pagado'
    ) {}

    public function build()
    {
        $idCuenta  = $this->cuenta->id_cuentas_por_pagar ?? $this->cuenta->id ?? '';
        $numPago   = $this->detalle->numero_pago ?? '';
        $subject   = "Pago CxP notificado al admin #{$idCuenta} (abono #{$numPago})";

        if ($this->accion === 'actualizado') {
            $subject = "Pago CxP actualizado (admin) #{$idCuenta} (abono #{$numPago})";
        }

        return $this->subject($subject)
            ->view('emails.cuentas_por_pagar.pago_admin');
    }
}
