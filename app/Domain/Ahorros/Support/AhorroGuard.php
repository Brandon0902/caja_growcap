<?php
declare(strict_types=1);

namespace App\Domain\Ahorros\Support;

use App\Models\UserAhorro;
use InvalidArgumentException;

final class AhorroGuard
{
    /** Exige ahorro Activo (status=1). */
    public static function assertActivo(UserAhorro $ahorro): void
    {
        if ((int)$ahorro->status !== 1) {
            throw new InvalidArgumentException('El ahorro no está activo.');
        }
    }

    /** Exige monto válido (>0). */
    public static function assertMontoValido(float $monto): void
    {
        if ($monto <= 0) {
            throw new InvalidArgumentException('El monto debe ser mayor a 0.');
        }
    }

    /** Verifica que el monto no exceda el saldo disponible. */
    public static function assertDisponible(UserAhorro $ahorro, float $monto): void
    {
        $disp = (float)($ahorro->saldo_disponible ?? 0);
        if ($monto > $disp) {
            throw new InvalidArgumentException('El monto excede el saldo disponible del ahorro.');
        }
    }

    /** Atajo común: activo + monto válido + dentro del disponible. */
    public static function ensurePuedeDebitar(UserAhorro $ahorro, float $monto): void
    {
        self::assertActivo($ahorro);
        self::assertMontoValido($monto);
        self::assertDisponible($ahorro, $monto);
    }
}
