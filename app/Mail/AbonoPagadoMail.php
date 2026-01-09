<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\UserPrestamo;
use App\Models\UserAbono;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbonoPagadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cliente $cliente;
    public UserPrestamo $prestamo;
    public UserAbono $abono;
    public float $monto;
    public array $breakdown;

    /**
     * @param  Cliente      $cliente
     * @param  UserPrestamo $prestamo
     * @param  UserAbono    $abono
     * @param  float        $monto
     * @param  array        $breakdown  ['pagos'=>[], 'saldo_a_favor', 'monto_pagado', 'saldo_restante']
     */
    public function __construct(
        Cliente $cliente,
        UserPrestamo $prestamo,
        UserAbono $abono,
        float $monto,
        array $breakdown
    ) {
        $this->cliente   = $cliente;
        $this->prestamo  = $prestamo;
        $this->abono     = $abono;
        $this->monto     = $monto;
        $this->breakdown = $breakdown;
    }

    public function build()
    {
        return $this->subject('Abono pagado en préstamo #'.$this->prestamo->id)
                    ->view('emails.abonos.pagado');
    }
}
