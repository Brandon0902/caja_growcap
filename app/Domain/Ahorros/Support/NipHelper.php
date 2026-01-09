<?php
declare(strict_types=1);

namespace App\Domain\Ahorros\Support;

use App\Models\Cliente;
use App\Models\UserData;

final class NipHelper
{
    /**
     * Verifica el NIP del cliente.
     * - Si el NIP en BD está plano: comparación directa.
     * - Si decides hashearlo: cambia a password_verify / Hash::check.
     */
    public static function verify(Cliente $cliente, string $nip): bool
    {
        $nip = trim((string)$nip);
        if ($nip === '') return false;

        $guardado = UserData::where('id_cliente', $cliente->id)->value('nip');
        if ($guardado === null) return false;

        // TODO: si lo guardas hasheado, reemplaza por Hash::check($nip, $guardado)
        return hash_equals((string)$guardado, $nip);
    }
}
