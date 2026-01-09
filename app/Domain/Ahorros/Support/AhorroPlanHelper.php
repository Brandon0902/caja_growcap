<?php

namespace App\Domain\Ahorros\Support;

final class AhorroPlanHelper
{
    /**
     * Determina si un plan es "Temporada" SOLO por el campo tipo_ahorro.
     * Regla: tipo_ahorro === 'temporada' (case-insensitive).
     */
    public static function isTemporada(?string $tipoAhorro): bool
    {
        $t = mb_strtolower(trim((string) $tipoAhorro));
        return $t === 'temporada';
    }

    /**
     * Label para frontend:
     * - PRIORIDAD: nombre (tal cual en BD)
     * - fallback: tipo_ahorro
     * - último fallback: "Ahorro"
     */
    public static function label(?string $nombre, ?string $tipoAhorro = null): string
    {
        $n = trim((string) $nombre);
        if ($n !== '') return $n;

        $t = trim((string) $tipoAhorro);
        if ($t !== '') return $t;

        return 'Ahorro';
    }
}
