<?php
// app/Services/AbonoService.php

namespace App\Services;

use App\Models\UserPrestamo;
use App\Models\UserAbono;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AbonoService
{
    public function __construct(private MoraService $moraService) {}

    /**
     * Paga un abono (por saldo interno).
     *
     * REGLAS:
     * - Siempre se paga en orden: abonos NO pagados con num_pago <= abono seleccionado.
     * - Si NO liquida intereses: exige mínimo (capital vencido anterior pendiente + capital del abono actual).
     * - Si SÍ liquida intereses: además exige cubrir toda la mora acumulada del préstamo (no pagada).
     * - Si se liquida intereses, se "limpia" mora_generada de los abonos (y mora_dias si existe).
     *
     * @return array{pagos:array<int,array>, saldo_a_favor:float, monto_pagado:float, saldo_restante:float}
     */
    public function pagar(
        UserPrestamo $prestamo,
        UserAbono $abono,
        float $monto,
        ?string $referencia = null,
        ?Carbon $fechaPago = null,
        bool $liquidarIntereses = false
    ): array {
        $fechaPago = $fechaPago ?: now();

        return DB::transaction(function () use ($prestamo, $abono, $monto, $referencia, $fechaPago, $liquidarIntereses) {
            $pagos = [];

            /** @var UserPrestamo $prestamo */
            $prestamo = UserPrestamo::lockForUpdate()->findOrFail($prestamo->id);
            /** @var UserAbono $abono */
            $abono    = UserAbono::lockForUpdate()->findOrFail($abono->id);

            $abonosPrestamo = UserAbono::where('user_prestamo_id', $prestamo->id)
                ->orderBy('num_pago')
                ->lockForUpdate()
                ->get();

            $hoy = $fechaPago->copy()->startOfDay();

            /* -------------------------------------------------
             * 0) Actualizar mora SOLO de abonos NO pagados y vencidos
             * ------------------------------------------------- */
            foreach ($abonosPrestamo as $a) {
                if ((int)$a->status === 1) continue; // pagado no genera mora

                $vtoRaw = $a->fecha_vencimiento ?? $a->fecha ?? null;
                if (!$vtoRaw) continue;

                $vto = Carbon::parse($vtoRaw)->startOfDay();
                if ($vto->gt($hoy)) continue;

                $calc    = $this->moraService->calcular($a, $hoy);
                $moraAdd = (float)($calc['mora_add'] ?? 0.0);
                if ($moraAdd <= 0) continue;

                $a->mora_generada = (float)($a->mora_generada ?? 0.0) + $moraAdd;

                if (Schema::hasColumn('user_abonos', 'mora_dias')) {
                    $a->mora_dias = (int)($a->mora_dias ?? 0) + (int)($calc['dias_mora'] ?? 0);
                }
                if (Schema::hasColumn('user_abonos', 'mora_last_calc')) {
                    $a->mora_last_calc = $hoy->toDateString();
                }

                $a->save();
            }

            // refrescar abono target
            $abono->refresh();
            $targetNum = (int) $abono->num_pago;

            if ((int)$abono->status === 1) {
                throw new \RuntimeException('Este abono ya está pagado.');
            }

            /* -------------------------------------------------
             * 1) Calcular:
             *   - capital vencido anterior pendiente
             *   - mora total pendiente (del préstamo)
             * ------------------------------------------------- */
            $capitalVencidoPend = 0.0;
            $moraPendiente      = 0.0;

            foreach ($abonosPrestamo as $x) {
                // mora pendiente (si existe)
                $moraPendiente += max(0.0, (float)($x->mora_generada ?? 0.0));

                // Solo nos importa capital vencido anterior no pagado
                if ((int)$x->status === 1) continue;

                $num = (int) $x->num_pago;
                if ($num >= $targetNum) continue;

                // Detectar si ya aplica mora (vencido)
                $m = $this->moraService->calcular($x, $hoy);
                $esVencido = ($m['aplica'] ?? false) === true || (int)$x->status === 2;
                if (!$esVencido) continue;

                $prog = (float) $x->cantidad;
                $pag  = (float) ($x->pago_monto ?? 0.0);
                $capitalVencidoPend += max(0.0, $prog - $pag);
            }

            $programadoActual = (float) $abono->cantidad;

            $minSinIntereses = round($capitalVencidoPend + $programadoActual, 2);
            $minConIntereses = round($minSinIntereses + $moraPendiente, 2);

            if ($liquidarIntereses) {
                if ($monto + 1e-6 < $minConIntereses) {
                    throw new \RuntimeException('Para liquidar intereses debes pagar al menos: $' . number_format($minConIntereses, 2, '.', ','));
                }
            } else {
                if ($monto + 1e-6 < $minSinIntereses) {
                    throw new \RuntimeException('Debes pagar al menos: $' . number_format($minSinIntereses, 2, '.', ',') . ' (incluye abonos vencidos).');
                }
            }

            /* -------------------------------------------------
             * 2) Pagar capital en orden:
             *    - primero abonos vencidos anteriores (capital pendiente)
             *    - luego abono actual (capital)
             * ------------------------------------------------- */
            $targets = $abonosPrestamo
                ->filter(fn(UserAbono $x) => (int)$x->status !== 1 && (int)$x->num_pago <= $targetNum)
                ->values();

            foreach ($targets as $t) {
                if ($monto <= 0) break;

                $prog = (float) $t->cantidad;
                $pagPrev = (float) ($t->pago_monto ?? 0.0);
                $pend = max(0.0, $prog - $pagPrev);

                if ($pend <= 0.000001) continue;

                if ($monto + 1e-6 < $pend) {
                    // por regla, esto no debería pasar por el mínimo requerido
                    break;
                }

                // pagar ese capital
                $pago = $pend;
                $monto = round($monto - $pago, 2);

                $t->status     = 1;
                $t->pago_monto = round($pagPrev + $pago, 2);
                $t->pago_at    = $fechaPago;
                $t->referencia = $referencia;
                $t->save();

                $pagos[] = [
                    'abono_id'   => $t->id,
                    'num_pago'   => $t->num_pago,
                    'programado' => $prog,
                    'mora'       => 0.0,
                    'pagado'     => $pago,
                ];
            }

            /* -------------------------------------------------
             * 3) Si liquida intereses:
             *    "cobrar" mora pendiente y limpiar mora_generada.
             * ------------------------------------------------- */
            if ($liquidarIntereses && $moraPendiente > 0) {
                if ($monto + 1e-6 < $moraPendiente) {
                    // por el mínimo requerido, no debería pasar
                    throw new \RuntimeException('No alcanzó el monto para cubrir intereses.');
                }

                $monto = round($monto - $moraPendiente, 2);

                // limpiar mora en BD
                foreach ($abonosPrestamo as $x) {
                    $changed = false;

                    if ((float)($x->mora_generada ?? 0.0) > 0) {
                        $x->mora_generada = 0.0;
                        $changed = true;
                    }
                    if (Schema::hasColumn('user_abonos', 'mora_dias')) {
                        if ((int)($x->mora_dias ?? 0) > 0) {
                            $x->mora_dias = 0;
                            $changed = true;
                        }
                    }
                    if (Schema::hasColumn('user_abonos', 'mora_last_calc')) {
                        // opcional: dejar la marca de cálculo
                        $x->mora_last_calc = $hoy->toDateString();
                        $changed = true;
                    }

                    if ($changed) $x->save();
                }

                $pagos[] = [
                    'abono_id'   => null,
                    'num_pago'   => null,
                    'programado' => 0.0,
                    'mora'       => $moraPendiente,
                    'pagado'     => $moraPendiente,
                ];
            }

            /* -------------------------------------------------
             * 4) Remanente -> saldo_a_favor
             * ------------------------------------------------- */
            if (Schema::hasColumn('user_prestamos', 'saldo_a_favor') && $monto > 0) {
                $prestamo->saldo_a_favor = round((float)($prestamo->saldo_a_favor ?? 0) + $monto, 2);
            }

            /* -------------------------------------------------
             * 5) Recalcular totales del préstamo
             * ------------------------------------------------- */
            $total = (float) ($prestamo->monto_total ?? 0);
            if ($total <= 0) {
                $total = (float) UserAbono::where('user_prestamo_id', $prestamo->id)->max('saldo_restante')
                    ?: (float) UserAbono::where('user_prestamo_id', $prestamo->id)->sum('cantidad');
            }

            $pagadoReal = (float) UserAbono::where('user_prestamo_id', $prestamo->id)
                ->where('status', 1)
                ->sum(DB::raw('COALESCE(pago_monto, cantidad)'));

            if (Schema::hasColumn('user_prestamos', 'monto_pagado')) {
                $prestamo->monto_pagado = $pagadoReal;
            }

            if (Schema::hasColumn('user_prestamos', 'saldo_restante')) {
                $prestamo->saldo_restante = max(
                    0,
                    round($total - $pagadoReal - (float)($prestamo->saldo_a_favor ?? 0), 2)
                );
            }

            if ($prestamo->saldo_restante !== null && $prestamo->saldo_restante <= 0.01) {
                $prestamo->status = 6; // Terminado
            }

            $prestamo->save();

            return [
                'pagos'          => $pagos,
                'saldo_a_favor'  => (float) ($prestamo->saldo_a_favor ?? 0),
                'monto_pagado'   => $pagadoReal,
                'saldo_restante' => (float) ($prestamo->saldo_restante ?? 0),
            ];
        });
    }
}
