<?php

namespace App\Observers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserObserver
{
    /** Si quieres limitar a estos nombres, déjalo así. */
    protected array $valid = ['admin','cobrador','contador','gerente','otro'];

    /** Se dispara cuando se crea el usuario */
    public function created(User $user): void
    {
        $this->syncFromEnum($user, force: true);
    }

    /** Se dispara cuando se actualiza el usuario */
    public function updated(User $user): void
    {
        // Solo si cambió el campo 'rol'
        if ($user->wasChanged('rol')) {
            $this->syncFromEnum($user, force: true);
        }
    }

    /** Lógica común */
    protected function syncFromEnum(User $user, bool $force = false): void
    {
        // Si no quieres validar, elimina la condición de in_array
        if (filled($user->rol) && in_array($user->rol, $this->valid, true)) {
            // Asegura que el rol exista (guard 'web')
            Role::findOrCreate($user->rol, 'web');

            // Asigna/actualiza el rol en Spatie
            $user->syncRoles([$user->rol]);
        } else {
            // Si rol viene vacío o no es válido, quitamos roles
            $user->syncRoles([]);
        }

        // MUY IMPORTANTE: limpiar el caché de Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
