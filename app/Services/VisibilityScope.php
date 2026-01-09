<?php
// app/Services/VisibilityScope.php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VisibilityScope
{
    /* ===================== Pivotes y columnas comunes ===================== */
    private const PIVOT_SUC_TABLE = 'usuario_sucursal_acceso';
    private const PIVOT_SUC_USER  = 'usuario_id';
    private const PIVOT_SUC_SUC   = 'id_sucursal';

    // Columna directa de sucursal
    public const COL_SUCURSAL = 'id_sucursal';
    // Columna FK de caja
    public const COL_CAJA     = 'id_caja';

    // Tabla de cajas y su columna de sucursal
    private const CAJAS_TABLE   = 'cajas';
    private const CAJAS_PK      = 'id_caja';
    private const CAJAS_SUC_COL = 'id_sucursal';

    /**
     * MAPA DE MÓDULOS → cómo aplicar el filtro.
     * - 'sucursal_col' : la tabla del módulo TIENE esta columna de sucursal.
     * - 'via_caja'     : el módulo TIENE id_caja; se filtra via JOIN a cajas.
     * - 'via_parent'   : el módulo NO tiene sucursal pero cuelga de una tabla padre
     *                    que sí la tiene (se hace JOIN al padre).
     */
    private const MODULE_MAP = [
        // --- Sidebar: Dashboard / Sucursales / Cajas ---
        'dashboard'             => ['sucursal_col' => null],
        'sucursales'            => ['sucursal_col' => self::COL_SUCURSAL],
        'cajas'                 => ['sucursal_col' => self::COL_SUCURSAL],

        // Cajas derivados
        'movimientos_caja'      => ['via_caja'     => self::COL_CAJA],
        'transacciones_cajas'   => ['via_caja'     => self::COL_CAJA],
        'gastos'                => ['via_caja'     => self::COL_CAJA],

        // Contabilidad Profunda
        'contabilidad_profunda' => ['sucursal_col' => self::COL_SUCURSAL],
        'presupuestos'          => ['sucursal_col' => self::COL_SUCURSAL],

        // Cuentas por pagar
        'cuentas_pagar'         => ['sucursal_col' => self::COL_SUCURSAL],
        'cuentas_pagar_detalles'=> [
            'via_parent' => [
                'table'          => 'cuentas_por_pagar',
                'parent_pk'      => 'id_cuentas_por_pagar',
                'fk_to_parent'   => 'cuenta_id',
                'parent_suc_col' => 'id_sucursal',
            ],
        ],

        // Perfil cliente (tablas con columna directa)
        'user_data'        => ['sucursal_col' => self::COL_SUCURSAL],
        'user_ahorros'     => ['sucursal_col' => self::COL_SUCURSAL],
        'user_inversiones' => ['sucursal_col' => self::COL_SUCURSAL],

        // Depósitos y Retiros: filtran vía CLIENTE
        'depositos' => [
            'via_parent' => [
                'table'          => 'clientes',
                'parent_pk'      => 'id',
                'fk_to_parent'   => 'id_cliente',
                'parent_suc_col' => 'id_sucursal',
            ],
        ],
        'retiros' => [
            'via_parent' => [
                'table'          => 'clientes',
                'parent_pk'      => 'id',
                'fk_to_parent'   => 'id_cliente',
                'parent_suc_col' => 'id_sucursal',
            ],
        ],

        // Catálogo de planes de préstamo (si lo usas en Admin)
        'prestamos' => ['sucursal_col' => self::COL_SUCURSAL],

        // Préstamos de usuarios (tabla: user_prestamos) → filtra por sucursal del CLIENTE
        'user_prestamos' => [
            'via_parent' => [
                'table'          => 'clientes',
                'parent_pk'      => 'id',
                'fk_to_parent'   => 'id_cliente',
                'parent_suc_col' => 'id_sucursal',
            ],
        ],

        'adminuserabonos' => ['sucursal_col' => self::COL_SUCURSAL],
        'documentos'      => ['sucursal_col' => self::COL_SUCURSAL],

        // Soporte
        'mensajes' => ['sucursal_col' => self::COL_SUCURSAL],
        'tickets'  => ['sucursal_col' => self::COL_SUCURSAL],

        // Admin (catálogos con sucursal)
        'clientes'              => ['sucursal_col' => self::COL_SUCURSAL],
        'inversiones'           => ['sucursal_col' => self::COL_SUCURSAL],
        'ahorros'               => ['sucursal_col' => self::COL_SUCURSAL],
        'config_mora'           => ['sucursal_col' => self::COL_SUCURSAL],
        'empresas'              => ['sucursal_col' => self::COL_SUCURSAL],
        'preguntas'             => ['sucursal_col' => self::COL_SUCURSAL],
        'usuarios'              => ['sucursal_col' => self::COL_SUCURSAL],
        'categoria_ingresos'    => ['sucursal_col' => self::COL_SUCURSAL],
        'subcategoria_ingresos' => ['sucursal_col' => self::COL_SUCURSAL],
        'categoria_gastos'      => ['sucursal_col' => self::COL_SUCURSAL],
        'subcategoria_gastos'   => ['sucursal_col' => self::COL_SUCURSAL],
        'proveedores'           => ['sucursal_col' => self::COL_SUCURSAL],
    ];

    /* ===================== Helpers de permisos ===================== */

    /**
     * Esquema FINAL:
     * - modulo.ver            => ve TODO (sin filtro)
     * - modulo.ver_sucursal   => ve LIMITADO a sucursal(es) del usuario
     */
    private static function hasVer(string $module, $u): bool
    {
        return $u->can("$module.ver");
    }

    private static function hasVerSucursal(string $module, $u): bool
    {
        return $u->can("$module.ver_sucursal");
    }

    private static function canRead(string $module, $u): bool
    {
        return self::hasVer($module, $u) || self::hasVerSucursal($module, $u);
    }

    /** IDs de sucursales del usuario (pivote + principal). */
    private static function sucursalesAccesoIds($u)
    {
        $userId = $u->id_usuario ?? $u->id;

        $ids = DB::table(self::PIVOT_SUC_TABLE)
            ->where(self::PIVOT_SUC_USER, $userId)
            ->pluck(self::PIVOT_SUC_SUC)
            ->filter();

        if (!empty($u->id_sucursal)) {
            $ids->push((int) $u->id_sucursal);
        }

        return $ids->unique()->values();
    }

    /* ============== Helpers Eloquent|Query Builder y alias =================== */

    private static function currentFrom(EloquentBuilder|QueryBuilder $q): string
    {
        return $q instanceof EloquentBuilder ? $q->getQuery()->from : $q->from;
    }

    private static function currentJoins(EloquentBuilder|QueryBuilder $q): ?array
    {
        return $q instanceof EloquentBuilder ? $q->getQuery()->joins : ($q->joins ?? null);
    }

    private static function parseFrom(string $from): array
    {
        $from = trim($from);

        if (preg_match('/^([A-Za-z0-9_]+)\s+as\s+([A-Za-z_][A-Za-z0-9_]*)$/i', $from, $m)) {
            return ['table' => $m[1], 'alias' => $m[2], 'ref' => $m[2]];
        }

        if (preg_match('/^([A-Za-z0-9_]+)\s+([A-Za-z_][A-Za-z0-9_]*)$/i', $from, $m)) {
            return ['table' => $m[1], 'alias' => $m[2], 'ref' => $m[2]];
        }

        return ['table' => $from, 'alias' => null, 'ref' => $from];
    }

    /** Devuelve el nombre con el que quedó “unida” una tabla (alias si existe). */
    private static function joinedName(EloquentBuilder|QueryBuilder $q, string $table): ?string
    {
        foreach (self::currentJoins($q) ?? [] as $join) {
            $t = $join->table; // ej: 'clientes' o 'clientes as c'

            if ($t === $table) return $table;

            if (preg_match('/^'.preg_quote($table, '/').'\s+as\s+([A-Za-z_][A-Za-z0-9_]*)$/i', $t, $m)) {
                return $m[1];
            }
        }
        return null;
    }

    /* ===================== Aplicadores de filtro ===================== */

    private static function filterBySucursalColumn(EloquentBuilder|QueryBuilder $q, string $colSucursal, $u)
    {
        $from = self::parseFrom(self::currentFrom($q));

        // Fail-safe: si la tabla real no tiene id_sucursal, no fugamos info.
        if (!Schema::hasColumn($from['table'], $colSucursal)) {
            return $q->whereRaw('0=1');
        }

        $ids = self::sucursalesAccesoIds($u);
        $qualified = "{$from['ref']}.{$colSucursal}";

        return $q->whereIn($qualified, $ids->isNotEmpty() ? $ids : [-1]);
    }

    private static function filterBySucursalViaCaja(EloquentBuilder|QueryBuilder $q, string $fkCajaCol, $u, ?string $moduleTable = null)
    {
        $ids = self::sucursalesAccesoIds($u);

        $fromStr = $moduleTable ?: self::currentFrom($q);
        $from    = self::parseFrom($fromStr);

        $joined = self::joinedName($q, self::CAJAS_TABLE);
        if (!$joined) {
            $q->join(self::CAJAS_TABLE, "{$from['ref']}.{$fkCajaCol}", '=', self::CAJAS_TABLE.'.'.self::CAJAS_PK);
            $joined = self::CAJAS_TABLE;
        }

        return $q->whereIn($joined.'.'.self::CAJAS_SUC_COL, $ids->isNotEmpty() ? $ids : [-1]);
    }

    private static function filterBySucursalViaParent(
        EloquentBuilder|QueryBuilder $q,
        string $moduleTable,
        string $parentTable,
        string $parentPk,
        string $fkToParent,
        string $parentSucursalCol,
        $u
    ) {
        $ids = self::sucursalesAccesoIds($u);

        $module = self::parseFrom($moduleTable);

        $joined = self::joinedName($q, $parentTable);
        if (!$joined) {
            $q->join($parentTable, "{$module['ref']}.{$fkToParent}", '=', "{$parentTable}.{$parentPk}");
            $joined = $parentTable;
        }

        return $q->whereIn("{$joined}.{$parentSucursalCol}", $ids->isNotEmpty() ? $ids : [-1]);
    }

    /**
     * Patrón general por módulo.
     * - Si NO tiene (ver|ver_sucursal) → 0=1
     * - Si tiene ver → sin filtro (PRIORIDAD)
     * - Si tiene ver_sucursal (y NO ver) → limitar por sucursal(es)
     */
    private static function applyForModule(EloquentBuilder|QueryBuilder $q, $u, string $module, ?string $moduleTable = null)
    {
        if (!self::canRead($module, $u)) {
            return $q->whereRaw('0=1');
        }

        // ✅ PRIORIDAD: si tiene "ver", ve todo aunque también tenga "ver_sucursal"
        if (self::hasVer($module, $u)) {
            return $q;
        }

        // Si NO tiene "ver", pero sí "ver_sucursal" => limitamos
        if (self::hasVerSucursal($module, $u)) {
            $cfg = self::MODULE_MAP[$module] ?? ['sucursal_col' => self::COL_SUCURSAL];

            if (!empty($cfg['via_caja'])) {
                return self::filterBySucursalViaCaja($q, $cfg['via_caja'], $u, $moduleTable);
            }

            if (!empty($cfg['via_parent'])) {
                $p    = $cfg['via_parent'];
                $from = $moduleTable ?: self::currentFrom($q);
                return self::filterBySucursalViaParent(
                    $q, $from, $p['table'], $p['parent_pk'], $p['fk_to_parent'], $p['parent_suc_col'], $u
                );
            }

            if (!empty($cfg['sucursal_col'])) {
                return self::filterBySucursalColumn($q, $cfg['sucursal_col'], $u);
            }

            return $q; // módulos sin sucursal_col (ej dashboard)
        }

        // (por seguridad, aunque canRead ya cubre)
        return $q->whereRaw('0=1');
    }

    /* ===================== Scopes públicos por módulo ===================== */
    public static function dashboard(EloquentBuilder|QueryBuilder $q, $u) { return $q; }

    public static function sucursales(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'sucursales'); }
    public static function cajas(EloquentBuilder|QueryBuilder $q, $u)      { return self::applyForModule($q, $u, 'cajas'); }

    public static function movimientos(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'movimientos_caja'); }
    public static function transaccionesCajas(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'transacciones_cajas'); }
    public static function gastos(EloquentBuilder|QueryBuilder $q, $u)      { return self::applyForModule($q, $u, 'gastos'); }

    public static function contabilidadProfunda(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'contabilidad_profunda'); }
    public static function presupuestos(EloquentBuilder|QueryBuilder $q, $u)        { return self::applyForModule($q, $u, 'presupuestos'); }

    public static function cuentasPagar(EloquentBuilder|QueryBuilder $q, $u) {
        return self::applyForModule($q, $u, 'cuentas_pagar');
    }
    public static function cuentasPagarDetalles(EloquentBuilder|QueryBuilder $q, $u) {
        return self::applyForModule($q, $u, 'cuentas_pagar_detalles', 'cuentas_por_pagar_detalles');
    }

    public static function clientes(EloquentBuilder|QueryBuilder $q, $u)    { return self::applyForModule($q, $u, 'clientes'); }
    public static function prestamos(EloquentBuilder|QueryBuilder $q, $u)   { return self::applyForModule($q, $u, 'prestamos'); }
    public static function inversiones(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'inversiones'); }
    public static function ahorros(EloquentBuilder|QueryBuilder $q, $u)     { return self::applyForModule($q, $u, 'ahorros'); }

    public static function userData(EloquentBuilder|QueryBuilder $q, $u)        { return self::applyForModule($q, $u, 'user_data'); }
    public static function userAhorros(EloquentBuilder|QueryBuilder $q, $u)     { return self::applyForModule($q, $u, 'user_ahorros'); }
    public static function userInversiones(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'user_inversiones'); }

    public static function depositos(EloquentBuilder|QueryBuilder $q, $u) {
        return self::applyForModule($q, $u, 'depositos', 'user_depositos');
    }

    public static function retirosInv(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'retiros'); }
    public static function retirosAhorro(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'retiros'); }
    public static function retiros(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'retiros'); }

    public static function prestamosUsuarios(EloquentBuilder|QueryBuilder $q, $u) {
        return self::applyForModule($q, $u, 'user_prestamos');
    }
    public static function userPrestamos(EloquentBuilder|QueryBuilder $q, $u) {
        return self::prestamosUsuarios($q, $u);
    }

    public static function adminUserAbonos(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'adminuserabonos'); }
    public static function documentos(EloquentBuilder|QueryBuilder $q, $u)     { return self::applyForModule($q, $u, 'documentos'); }
    public static function mensajes(EloquentBuilder|QueryBuilder $q, $u)       { return self::applyForModule($q, $u, 'mensajes'); }
    public static function tickets(EloquentBuilder|QueryBuilder $q, $u)        { return self::applyForModule($q, $u, 'tickets'); }
    public static function empresas(EloquentBuilder|QueryBuilder $q, $u)       { return self::applyForModule($q, $u, 'empresas'); }
    public static function preguntas(EloquentBuilder|QueryBuilder $q, $u)      { return self::applyForModule($q, $u, 'preguntas'); }
    public static function usuarios(EloquentBuilder|QueryBuilder $q, $u)       { return self::applyForModule($q, $u, 'usuarios'); }

    public static function categoriaIngresos(EloquentBuilder|QueryBuilder $q, $u)    { return self::applyForModule($q, $u, 'categoria_ingresos'); }
    public static function subcategoriaIngresos(EloquentBuilder|QueryBuilder $q, $u) { return self::applyForModule($q, $u, 'subcategoria_ingresos'); }
    public static function categoriaGastos(EloquentBuilder|QueryBuilder $q, $u)      { return self::applyForModule($q, $u, 'categoria_gastos'); }
    public static function subcategoriaGastos(EloquentBuilder|QueryBuilder $q, $u)   { return self::applyForModule($q, $u, 'subcategoria_gastos'); }

    public static function proveedores(EloquentBuilder|QueryBuilder $q, $u) {
        return self::applyForModule($q, $u, 'proveedores');
    }
}
