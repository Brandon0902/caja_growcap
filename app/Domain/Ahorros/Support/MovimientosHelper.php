<?php
declare(strict_types=1);

namespace App\Domain\Ahorros\Support;

use App\Models\UserAhorro;
use App\Models\MovimientoAhorro;

final class MovimientosHelper
{
    /**
     * Registra un movimiento interno del ahorro.
     *
     * @param string $tipo  Ej: 'TRANSFER','RETIRO','ABONO_PRESTAMO','CAMBIO_CUOTA'
     * @param float  $monto Puede ser negativo (eg. retiro) o positivo (entrada).
     * @param float  $saldoResultante Saldo disponible después de la operación.
     * @param string|null $obs Observaciones legibles.
     * @param int|null $idUsuario Usuario que ejecutó (si aplica).
     */
    public static function registrar(
        UserAhorro $ahorro,
        string $tipo,
        float $monto,
        float $saldoResultante,
        ?string $obs = null,
        ?int $idUsuario = null
    ): MovimientoAhorro {
        return MovimientoAhorro::create([
            'id_ahorro'        => $ahorro->id,
            'monto'            => $monto,
            'saldo_resultante' => $saldoResultante,
            'fecha'            => now(),
            'tipo'             => strtoupper($tipo),
            'observaciones'    => $obs,
            'id_usuario'       => $idUsuario,
        ]);
    }
}
