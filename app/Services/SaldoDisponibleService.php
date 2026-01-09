<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaldoDisponibleService
{
    private function pickCol(string $table, array $candidates): ?string
    {
        if (!Schema::hasTable($table)) return null;
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }

    /** Convierte columnas tipo "$1,234.00" o "1,234.00" a número. */
    private function numExpr(string $qualifiedCol): string
    {
        return "CAST(REPLACE(REPLACE($qualifiedCol, ',', ''), '$','') AS DECIMAL(18,2))";
    }

    public function forCliente(int $clienteId): array
    {
        $tblAho = 'user_ahorro';
        $tblDep = 'user_depositos';
        $tblInv = 'user_inversiones';

        /* ===================== AHORROS (solo ACTIVOS status=2 y con saldo>0) ===================== */
        $sdAhorros = 0.0;

        if (Schema::hasTable($tblAho)) {
            $ahoDispCol   = $this->pickCol($tblAho, ['saldo_disponible', 'saldo_disp']);
            $ahoStatusCol = $this->pickCol($tblAho, ['status']);

            if ($ahoDispCol) {
                $q = DB::table($tblAho)->where('id_cliente', $clienteId);

                if ($ahoStatusCol) {
                    $q->where("$tblAho.$ahoStatusCol", 2); // Activo = 2
                }

                $q->where("$tblAho.$ahoDispCol", '>', 0);

                $sdAhorros = (float)($q->selectRaw("COALESCE(SUM($tblAho.$ahoDispCol),0) as s")->value('s') ?? 0);
            }
        }

        /* ===================== DEPÓSITOS APROBADOS ===================== */
        $sdDepositos = 0.0;

        if (Schema::hasTable($tblDep)) {
            $depMontoCol  = $this->pickCol($tblDep, ['cantidad', 'monto']);
            $depStatusCol = $this->pickCol($tblDep, ['status']);

            if ($depMontoCol) {
                $numExpr = $this->numExpr("$tblDep.$depMontoCol");

                $q = DB::table($tblDep)->where('id_cliente', $clienteId);
                if ($depStatusCol) $q->where("$tblDep.$depStatusCol", 1); // aprobado

                $sdDepositos = (float)($q->selectRaw("COALESCE(SUM($numExpr),0) as s")->value('s') ?? 0);
            }
        }

        /* ===================== INVERSIONES TERMINADAS (status=3) ===================== */
        $sdInversiones = 0.0;

        if (Schema::hasTable($tblInv)) {
            $invStatusCol = $this->pickCol($tblInv, ['status']);
            $capActualCol = $this->pickCol($tblInv, ['capital_actual']);

            if ($capActualCol) {
                $q = DB::table($tblInv)->where('id_cliente', $clienteId);
                if ($invStatusCol) $q->where("$tblInv.$invStatusCol", 3); // Terminada = 3

                $capExpr = $this->numExpr("$tblInv.$capActualCol");

                $sdInversiones = (float)($q->selectRaw("COALESCE(SUM($capExpr),0) as s")->value('s') ?? 0);
            }
        }

        $total = $sdAhorros + $sdDepositos + $sdInversiones;

        return [
            'sd_ahorros'     => (float)$sdAhorros,
            'sd_depositos'   => (float)$sdDepositos,
            'sd_inversiones' => (float)$sdInversiones,
            'total'          => (float)$total,
        ];
    }

    /**
     * Consume saldo priorizando:
     *  1) Depósitos (status=1)
     *  2) Inversiones terminadas (status=3, capital_actual)
     *  3) Ahorros activos (status=2, saldo_disponible)
     *
     * Devuelve el detalle de lo consumido.
     *
     * @throws \RuntimeException si no alcanza el saldo
     */
    public function consumePreferDepositos(int $clienteId, float $monto): array
    {
        if ($monto <= 0) {
            throw new \InvalidArgumentException('El monto a consumir debe ser mayor a 0.');
        }

        return DB::transaction(function () use ($clienteId, $monto) {
            $restante = $monto;

            $detalle = [
                'deposits'    => [],
                'inversiones' => [],
                'ahorros'     => [],
            ];

            /* ========== 1) DEPÓSITOS (status=1) ========== */
            $tblDep      = 'user_depositos';
            $depStatusCol = $this->pickCol($tblDep, ['status']);
            $depMontoCol  = $this->pickCol($tblDep, ['cantidad', 'monto']);

            if ($restante > 0 && $depMontoCol && Schema::hasTable($tblDep)) {

                $depQuery = DB::table($tblDep)
                    ->where('id_cliente', $clienteId)
                    ->when($depStatusCol, fn($q) => $q->where("$tblDep.$depStatusCol", 1))
                    ->lockForUpdate();

                if (Schema::hasColumn($tblDep, 'fecha_deposito')) {
                    $depQuery->orderBy('fecha_deposito', 'asc');
                } else {
                    $depQuery->orderBy('id', 'asc');
                }

                $depos = $depQuery->get();

                // --- 1er pase: buscar un solo depósito que cubra todo ---
                foreach ($depos as $d) {
                    $saldo = (float)$d->{$depMontoCol};
                    if ($saldo <= 0) continue;

                    if ($saldo >= $restante) {
                        $usar        = $restante;
                        $nuevoSaldo  = $saldo - $usar;
                        $update      = [ $depMontoCol => $nuevoSaldo ];

                        if ($depStatusCol && $nuevoSaldo <= 0) {
                            // lo marcamos como "usado" (por ejemplo status=2)
                            $update[$depStatusCol] = 2;
                        }

                        DB::table($tblDep)->where('id', $d->id)->update($update);

                        $detalle['deposits'][] = [
                            'id'    => $d->id,
                            'monto' => $usar,
                        ];

                        $restante = 0;
                        break;
                    }
                }

                // --- 2do pase: consumir varios depósitos si aún falta ---
                if ($restante > 0) {
                    foreach ($depos as $d) {
                        if ($restante <= 0) break;

                        $saldo = (float)$d->{$depMontoCol};
                        if ($saldo <= 0) continue;

                        $usar       = min($saldo, $restante);
                        $nuevoSaldo = $saldo - $usar;
                        $update     = [ $depMontoCol => $nuevoSaldo ];

                        if ($depStatusCol && $nuevoSaldo <= 0) {
                            $update[$depStatusCol] = 2;
                        }

                        DB::table($tblDep)->where('id', $d->id)->update($update);

                        $detalle['deposits'][] = [
                            'id'    => $d->id,
                            'monto' => $usar,
                        ];

                        $restante -= $usar;
                    }
                }
            }

            /* ========== 2) INVERSIONES TERMINADAS (status=3) ========== */
            $tblInv       = 'user_inversiones';
            $invStatusCol = $this->pickCol($tblInv, ['status']);
            $capCol       = $this->pickCol($tblInv, ['capital_actual']);

            if ($restante > 0 && $capCol && Schema::hasTable($tblInv)) {
                $invQuery = DB::table($tblInv)
                    ->where('id_cliente', $clienteId)
                    ->when($invStatusCol, fn($q) => $q->where("$tblInv.$invStatusCol", 3))
                    ->where("$tblInv.$capCol", '>', 0)
                    ->lockForUpdate();

                if (Schema::hasColumn($tblInv, 'fecha_fin')) {
                    $invQuery->orderBy('fecha_fin', 'asc');
                } else {
                    $invQuery->orderBy('id', 'asc');
                }

                $invs = $invQuery->get();

                foreach ($invs as $inv) {
                    if ($restante <= 0) break;

                    $saldo = (float)$inv->{$capCol};
                    if ($saldo <= 0) continue;

                    $usar       = min($saldo, $restante);
                    $nuevoSaldo = $saldo - $usar;

                    DB::table($tblInv)->where('id', $inv->id)->update([
                        $capCol => $nuevoSaldo,
                    ]);

                    $detalle['inversiones'][] = [
                        'id'    => $inv->id,
                        'monto' => $usar,
                    ];

                    $restante -= $usar;
                }
            }

            /* ========== 3) AHORROS ACTIVOS (status=2, saldo_disponible) ========== */
            $tblAho      = 'user_ahorro';
            $ahoStatusCol = $this->pickCol($tblAho, ['status']);
            $ahoDispCol   = $this->pickCol($tblAho, ['saldo_disponible', 'saldo_disp']);

            if ($restante > 0 && $ahoDispCol && Schema::hasTable($tblAho)) {
                $ahoQuery = DB::table($tblAho)
                    ->where('id_cliente', $clienteId)
                    ->when($ahoStatusCol, fn($q) => $q->where("$tblAho.$ahoStatusCol", 2))
                    ->where("$tblAho.$ahoDispCol", '>', 0)
                    ->lockForUpdate();

                if (Schema::hasColumn($tblAho, 'fecha_inicio')) {
                    $ahoQuery->orderBy('fecha_inicio', 'asc');
                } else {
                    $ahoQuery->orderBy('id', 'asc');
                }

                $ahorros = $ahoQuery->get();

                foreach ($ahorros as $a) {
                    if ($restante <= 0) break;

                    $saldo = (float)$a->{$ahoDispCol};
                    if ($saldo <= 0) continue;

                    $usar       = min($saldo, $restante);
                    $nuevoSaldo = $saldo - $usar;

                    DB::table($tblAho)->where('id', $a->id)->update([
                        $ahoDispCol => $nuevoSaldo,
                    ]);

                    $detalle['ahorros'][] = [
                        'id'    => $a->id,
                        'monto' => $usar,
                    ];

                    $restante -= $usar;
                }
            }

            if ($restante > 0) {
                throw new \RuntimeException('Saldo insuficiente para cubrir el monto solicitado.');
            }

            $detalle['total_consumido'] = $monto;

            return $detalle;
        });
    }
}
