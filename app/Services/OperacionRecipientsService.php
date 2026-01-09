<?php

namespace App\Services;

use App\Models\User;

class OperacionRecipientsService
{
    /**
     * Correos a notificar para una operación:
     * 1) Actor (usuario que realiza la acción)
     * 2) Admin del sistema (MAIL_FROM_ADDRESS)
     * 3) "Gerente(s) de la sucursal": usuarios de esa sucursal con rol gerente o admin
     */
    public function forSucursalAndActor(int $sucursalId, ?User $actor = null): array
    {
        $emails = [];

        // 1) Actor
        if ($actor && !empty($actor->email)) {
            $emails[] = trim((string) $actor->email);
        }

        // 2) Admin del sistema (MAIL_FROM_ADDRESS)
        $adminSistema = trim((string) (config('mail.from.address') ?? ''));
        if ($adminSistema !== '') {
            $emails[] = $adminSistema;
        }

        // 3) Jefes de la sucursal: gerente OR admin (en BD) filtrados por id_sucursal
        $jefesSucursal = User::query()
            ->where('id_sucursal', $sucursalId)
            ->whereIn('rol', ['gerente', 'admin'])
            ->pluck('email')
            ->filter()
            ->map(fn ($e) => trim((string) $e))
            ->all();

        $emails = array_merge($emails, $jefesSucursal);

        // Limpieza: válidos + únicos
        $emails = array_values(array_unique(array_filter($emails, function ($e) {
            return is_string($e) && $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL);
        })));

        return $emails;
    }
}
