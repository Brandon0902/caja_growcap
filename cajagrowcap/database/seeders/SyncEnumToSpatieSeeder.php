<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncEnumToSpatieSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Asegurar roles en el guard 'web'
        foreach (['admin','cobrador','contador','gerente','otro'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        // 2) Sincronizar TODOS los usuarios: enum usuarios.rol -> Spatie
        User::query()->each(function (User $u) {
            if (filled($u->rol)) {
                $u->syncRoles([$u->rol]);
            } else {
                $u->syncRoles([]);
            }
        });

        // 3) Limpiar cache de permisos (MUY importante)
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
