<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermisosController extends Controller
{
    /** Pivot acceso a Cajas */
    private const PIVOT_TABLE       = 'usuario_caja_acceso';
    private const PIVOT_USER_COLUMN = 'usuario_id';
    private const PIVOT_CAJA_COLUMN = 'id_caja';

    /**
     * Árbol jerárquico mostrado en la matriz.
     * Cada 'module' debe existir como prefijo en permissions.name.
     */
    private const TREE = [
        'Dashboard' => [
            ['module' => 'dashboard', 'label' => 'Dashboard'],
        ],
        'Sucursal' => [
            ['module' => 'sucursales', 'label' => 'Sucursal'],
        ],
        'Cajas' => [
            ['module' => 'cajas',               'label' => 'Cajas'],
            ['module' => 'movimientos_caja',    'label' => 'Movimientos'],
            ['module' => 'gastos',              'label' => 'Transacción entre cajas'],
        ],
        'Cont Profunda' => [
            ['module' => 'contabilidad_profunda', 'label' => 'Contabilidad Profunda'],
            ['module' => 'presupuestos',          'label' => 'Presupuestos'],
        ],
        'Cuentas por pagar' => [
            ['module' => 'cuentas_pagar',          'label' => 'Registro'],
            ['module' => 'cuentas_pagar_detalles', 'label' => 'Abonos (general)'],
        ],
        'Clientes' => [
            ['module' => 'user_data',        'label' => 'Datos de clientes'],
            ['module' => 'user_ahorros',     'label' => 'Ahorros'],
            ['module' => 'user_inversiones', 'label' => 'Inversiones'],
            ['module' => 'depositos',        'label' => 'Depósitos'],
            ['module' => 'retiros',          'label' => 'Retiros'],
            ['module' => 'user_prestamos',   'label' => 'Préstamos'],
            ['module' => 'adminuserabonos',  'label' => 'Abonos'],
            ['module' => 'documentos',       'label' => 'Documentos'],
        ],
        'Soporte' => [
            ['module' => 'mensajes', 'label' => 'Mensajes'],
            ['module' => 'tickets',  'label' => 'Tickets'],
        ],
        'Admin' => [
            ['module' => 'clientes',              'label' => 'Clientes'],
            ['module' => 'prestamos',             'label' => 'Préstamos'],
            ['module' => 'inversiones',           'label' => 'Inversiones'],
            ['module' => 'ahorros',               'label' => 'Ahorros'],
            ['module' => 'config_mora',           'label' => 'Mora'],
            ['module' => 'empresas',              'label' => 'Empresas'],
            ['module' => 'preguntas',             'label' => 'Preguntas'],
            ['module' => 'admin',                 'label' => 'Permisos'],
            ['module' => 'usuarios',              'label' => 'Usuarios'],
            ['module' => 'categoria_ingresos',    'label' => 'Cat. Ingresos'],
            ['module' => 'subcategoria_ingresos', 'label' => 'Subcat. Ingresos'],
            ['module' => 'categoria_gastos',      'label' => 'Cat. Gastos'],
            ['module' => 'subcategoria_gastos',   'label' => 'Subcat. Gastos'],
            ['module' => 'proveedores',           'label' => 'Proveedores'],
        ],
    ];

    /** Acciones granulares (columnas) */
    private const ACTIONS = ['crear','editar','eliminar','ver'];

    /**
     * ✅ ESQUEMA FINAL:
     * - modulo.ver
     * - modulo.ver_sucursal
     */
    private const SCOPES  = ['ver_sucursal'];

    public function index(Request $request)
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = Role::where('guard_name', 'web')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get();

        $users    = User::orderBy('name')->get();
        $cajasAll = Caja::orderBy('nombre')->get(['id_caja', 'id_sucursal', 'nombre']);

        // Índice rápido de permisos: module → action → "module.action"
        $permIndex = [];
        foreach (Permission::where('guard_name', 'web')->get() as $p) {
            [$m, $a] = array_pad(explode('.', $p->name, 2), 2, null);
            if ($m && $a) {
                $permIndex[$m][$a] = $p->name;
            }
        }

        return view('permisos.index', [
            'roles'     => $roles,
            'users'     => $users,
            'cajasAll'  => $cajasAll,
            'permIndex' => $permIndex,
            'tree'      => self::TREE,
            'actions'   => self::ACTIONS,
            'scopes'    => self::SCOPES, // ✅ ahora es ver_sucursal
        ]);
    }

    /** Guarda selección granular por acción y scope (ver_sucursal) */
    public function updateRolePermissions(Request $request, Role $role)
    {
        $request->validate([
            'matrix' => ['array'],
            'matrix.*.acciones' => ['array'],
            'matrix.*.scope'    => ['nullable', 'in:ver_sucursal'], // ✅
        ]);

        $this->assertWebGuardRole($role);

        // Cargar permisos del guard e indexar por módulo/acción
        $all = Permission::where('guard_name','web')->get()
            ->groupBy(fn($p)=>explode('.', $p->name, 2)[0])
            ->map(function($grp){
                $map = [];
                foreach ($grp as $p) {
                    [$m,$a] = explode('.', $p->name, 2);
                    $map[$a] = $p;
                }
                return $map;
            });

        $selected = collect($request->input('matrix', []));
        $grantIds = [];

        foreach ($selected as $module => $opts) {
            // Acciones
            foreach (array_keys($opts['acciones'] ?? []) as $action) {
                if (in_array($action, self::ACTIONS, true) && isset($all[$module][$action])) {
                    $grantIds[] = $all[$module][$action]->id;
                }
            }

            // Scope ✅ ver_sucursal
            $scope = $opts['scope'] ?? null;
            if ($scope && isset($all[$module][$scope])) {
                $grantIds[] = $all[$module][$scope]->id;
            }
        }

        $role->syncPermissions(Permission::whereIn('id', array_unique($grantIds))->get());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('ok', "Permisos del rol '{$role->name}' actualizados.");
    }

    /** Sincroniza roles directos de un usuario desde el multiselect */
    public function syncUserRoles(Request $request, User $user)
    {
        $request->validate([
            'roles'   => ['array'],
            'roles.*' => ['string'],
        ]);

        $newRoles = collect((array)$request->input('roles', []))
            ->filter()->unique()->values()->all();

        foreach ($newRoles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $this->assertNotDemotingLastAdmin($user, $newRoles);

        $currentRoles = $user->roles->pluck('name')->sort()->values()->all();
        $mustLogout   = (Auth::id() === $user->id) && ($currentRoles !== $newRoles);

        $user->syncRoles($newRoles);
        if (!empty($newRoles)) {
            $user->rol = $newRoles[0];
            $user->save();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($mustLogout) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('warning', 'Tu rol cambió. Inicia sesión nuevamente.');
        }

        return back()->with('ok', "Roles del usuario '{$user->name}' actualizados.");
    }

    /** Sincroniza acceso a Cajas del usuario (pivot) */
    public function syncUserCajaAccess(Request $request, User $user)
    {
        $request->validate([
            'cajas'   => ['array'],
            'cajas.*' => ['integer', 'exists:cajas,id_caja'],
        ]);

        $ids = collect($request->input('cajas', []))->filter()->unique()->values();

        DB::transaction(function () use ($user, $ids) {
            DB::table(self::PIVOT_TABLE)
                ->where(self::PIVOT_USER_COLUMN, $this->userKey($user))
                ->delete();

            if ($ids->isNotEmpty()) {
                $rows = $ids->map(fn($idCaja) => [
                    self::PIVOT_USER_COLUMN => $this->userKey($user),
                    self::PIVOT_CAJA_COLUMN => $idCaja,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                DB::table(self::PIVOT_TABLE)->insert($rows);
            }
        });

        return back()->with('ok', "Acceso a cajas del usuario '{$user->name}' actualizado.");
    }

    /** Sincroniza enum usuarios.rol → Spatie */
    public function syncEnumToSpatie()
    {
        $affected = 0;

        DB::transaction(function () use (&$affected) {
            User::query()->each(function (User $u) use (&$affected) {
                if (!empty($u->rol)) {
                    Role::firstOrCreate(['name' => $u->rol, 'guard_name' => 'web']);
                    $u->syncRoles([$u->rol]);
                    $affected++;
                }
            });
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('ok', "Sincronización completa. Usuarios afectados: {$affected}.");
    }

    /** Reset caché Spatie */
    public function cacheReset()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return back()->with('ok', 'Caché de permisos reiniciada.');
    }

    /**
     * ✅ Normaliza permisos en BD según TREE + ACTIONS + SCOPES (ver_sucursal):
     * - Crea los que falten
     * - Borra los sobrantes
     */
    public function pruneAndNormalize()
    {
        $validModules = collect(self::TREE)->flatten(1)->pluck('module')->unique()->values();

        $validActions = array_merge(self::ACTIONS, self::SCOPES); // ✅ incluye ver_sucursal

        $requiredNames = $validModules->flatMap(function ($m) use ($validActions) {
            return collect($validActions)->map(fn($a) => "{$m}.{$a}");
        })->values();

        DB::transaction(function () use ($requiredNames) {
            // 1) crear faltantes
            $existing = Permission::where('guard_name','web')->pluck('name')->all();
            $missing  = $requiredNames->diff($existing)->values();

            foreach ($missing as $name) {
                Permission::create(['name' => $name, 'guard_name' => 'web']);
            }

            // 2) borrar sobrantes
            $toDelete = Permission::where('guard_name','web')
                ->whereNotIn('name', $requiredNames->all())
                ->get();

            if ($toDelete->isNotEmpty()) {
                $ids = $toDelete->pluck('id')->all();
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
                Permission::whereIn('id', $ids)->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('ok', 'Permisos normalizados (incluye ver_sucursal).');
    }

    /* ===================== Helpers ===================== */

    protected function assertWebGuardRole(Role $role): void
    {
        if ($role->guard_name !== 'web') {
            throw ValidationException::withMessages([
                'role' => 'Solo se permiten roles del guard "web".',
            ]);
        }
    }

    protected function assertNotDemotingLastAdmin(User $user, array $newRoles): void
    {
        $removingAdmin = $user->hasRole('admin') && !in_array('admin', $newRoles, true);
        if (!$removingAdmin) return;

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if (!$adminRole) return;

        $admins = DB::table('model_has_roles')->where('role_id', $adminRole->id)->count();
        if ($admins <= 1 && $user->hasRole('admin')) {
            throw ValidationException::withMessages([
                'roles' => 'No puedes quitar el rol "admin" del único administrador del sistema.',
            ]);
        }
    }

    protected function userKey(User $user)
    {
        return $user->id_usuario ?? $user->id;
    }
}
