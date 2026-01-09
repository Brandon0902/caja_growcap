<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAhorro;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stripe\StripeClient;

class UserAhorroStripeController extends Controller
{
    private function cliente(Request $request): Cliente
    {
        $u = auth('sanctum')->user() ?? $request->user();

        if ($u instanceof Cliente) return $u;

        if ($u && isset($u->id_cliente) && $u->id_cliente) {
            if ($c = Cliente::find($u->id_cliente)) return $c;
        }

        throw new AuthenticationException('El token no corresponde a un cliente.');
    }

    private function normalizaMonto(mixed $raw): float
    {
        $norm = str_replace([' ', '$'], '', (string) $raw);
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $norm)) {
            $norm = str_replace('.', '', $norm);
            $norm = str_replace(',', '.', $norm);
        } else {
            $norm = str_replace(',', '', $norm);
        }
        return (float) $norm;
    }

    private function toBool(mixed $v, bool $default = false): bool
    {
        if ($v === null) return $default;
        $filtered = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $filtered ?? $default;
    }

    private function intervalFromFreq(?string $freq): array
    {
        $freq = $freq ?: 'Mensual';
        return match ($freq) {
            'Semanal'   => ['week', 1],
            'Quincenal' => ['week', 2],
            default     => ['month', 1],
        };
    }

    private function ahorrosProductId(): string
    {
        $pid = (string) (config('services.stripe.ahorros_product_id') ?? env('STRIPE_AHORROS_PRODUCT_ID', ''));
        $pid = trim($pid);
        if ($pid === '') {
            throw new \RuntimeException('Falta configurar STRIPE_AHORROS_PRODUCT_ID (producto activo en Stripe).');
        }
        return $pid;
    }

    /** ✅ expires_at para disparar checkout.session.expired más rápido */
    private function checkoutExpiresAt(): int
    {
        $seconds = (int) (config('services.stripe.checkout_expires_seconds')
            ?? env('STRIPE_CHECKOUT_EXPIRES_SECONDS', 1800)); // 30 min

        $seconds = max(1800, min($seconds, 86400)); // clamp 30m..24h
        return time() + $seconds;
    }

    private function getOrCreateRecurringPrice(
        StripeClient $stripe,
        string $productId,
        int $unitAmountCents,
        string $interval,
        int $intervalCount
    ): string {
        $lookupKey = "ahorro_{$interval}_{$intervalCount}_{$unitAmountCents}";

        $existing = $stripe->prices->all([
            'lookup_keys' => [$lookupKey],
            'active'      => true,
            'limit'       => 1,
        ]);

        if (!empty($existing->data)) {
            return $existing->data[0]->id;
        }

        $price = $stripe->prices->create([
            'currency'    => 'mxn',
            'unit_amount' => $unitAmountCents,
            'recurring'   => [
                'interval'       => $interval,
                'interval_count' => $intervalCount,
            ],
            'product'    => $productId,
            'lookup_key' => $lookupKey,
            'metadata'   => ['type' => 'ahorro_recurring'],
        ]);

        return $price->id;
    }

    /**
     * ✅ Persistir session_id en user_ahorro apenas se crea checkout.
     * - No activa el ahorro.
     * - Solo guarda session + flags si existen columnas.
     */
    private function persistCheckoutSession(UserAhorro $ahorro, string $sessionId, string $action): void
    {
        if ($sessionId === '') return;

        try {
            DB::transaction(function () use ($ahorro, $sessionId, $action) {
                $row = UserAhorro::where('id', $ahorro->id)->lockForUpdate()->first();
                if (!$row) return;

                $table = $row->getTable();

                if (Schema::hasColumn($table, 'stripe_checkout_session_id')) {
                    $row->stripe_checkout_session_id = $sessionId;
                }

                // flags opcionales para UI/auditoría (si existen)
                if (Schema::hasColumn($table, 'payment_method') && empty($row->payment_method)) {
                    $row->payment_method = 'stripe';
                }
                if (Schema::hasColumn($table, 'payment_status') && empty($row->payment_status)) {
                    $row->payment_status = 'pending';
                }
                if (Schema::hasColumn($table, 'stripe_status') && empty($row->stripe_status)) {
                    $row->stripe_status = 'pending';
                }

                $row->save();
            });
        } catch (\Throwable $e) {
            Log::warning('persistCheckoutSession failed', [
                'ahorro_id'  => $ahorro->id,
                'session_id' => $sessionId,
                'action'     => $action,
                'err'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Crea Checkout para:
     * - create: mode=subscription (cuota recurrente + opcional aporte inicial)
     * - update: mode=payment (cobra incrementos hoy) y webhook actualiza suscripción a la nueva cuota total
     */
    public function checkout(Request $request, int $id)
    {
        $cliente = $this->cliente($request);

        $ahorro = UserAhorro::where('id', $id)
            ->where('id_cliente', $cliente->id)
            ->firstOrFail();

        $data = $request->validate([
            'action'              => ['nullable','in:create,update'],

            // CREATE
            'monto_inicial'       => ['nullable'],
            'cuota'               => ['nullable','numeric','min:0'],
            'charge_monto_now'    => ['nullable'],

            // UPDATE (incrementos)
            'add_monto'           => ['nullable'],
            'add_cuota'           => ['nullable','numeric','min:0'],
            'old_subscription_id' => ['nullable','string'],
            'charge_cuota_now'    => ['nullable'],

            // Compat
            'new_monto_inicial'   => ['nullable'],
            'new_cuota'           => ['nullable','numeric','min:0'],
        ]);

        $action = $data['action'] ?? 'create';

        $secret = config('services.stripe.secret');
        if (!$secret) {
            return response()->json(['ok' => false, 'error' => 'Stripe no está configurado (falta STRIPE_SECRET).'], 500);
        }

        $stripe = new StripeClient($secret);

        $frontBase = rtrim(config('app.front_url', config('app.url')), '/');
        [$interval, $intervalCount] = $this->intervalFromFreq($ahorro->frecuencia_pago);

        // ===========================
        // CREATE => mode=subscription
        // ===========================
        if ($action === 'create') {
            $montoInicial = $this->normalizaMonto($data['monto_inicial'] ?? $ahorro->monto_ahorro ?? 0);
            $cuota = (float) ($data['cuota'] ?? $ahorro->cuota ?? 0);

            if ($cuota <= 0) {
                return response()->json(['ok' => false, 'error' => 'Cuota inválida para crear la suscripción.'], 422);
            }

            $chargeMontoNow = $this->toBool($data['charge_monto_now'] ?? null, true);

            $meta = [
                'tipo'              => 'ahorro',
                'action'            => 'create',
                'ahorro_id'         => (string) $ahorro->id,
                'cliente_id'        => (string) $cliente->id,
                'monto_inicial'     => (string) $montoInicial,
                'cuota'             => (string) $cuota,
                'charge_monto_now'  => $chargeMontoNow ? '1' : '0',
                'freq'              => (string) ($ahorro->frecuencia_pago ?: 'Mensual'),
                'interval'          => (string) $interval,
                'interval_count'    => (string) $intervalCount,
            ];

            try {
                $productId = $this->ahorrosProductId();

                $recurringPriceId = $this->getOrCreateRecurringPrice(
                    $stripe,
                    $productId,
                    (int) round($cuota * 100),
                    $interval,
                    $intervalCount
                );

                $lineItems = [
                    ['price' => $recurringPriceId, 'quantity' => 1],
                ];

                if ($chargeMontoNow && $montoInicial > 0) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency'     => 'mxn',
                            'unit_amount'  => (int) round($montoInicial * 100),
                            'product_data' => ['name' => 'Aporte inicial ahorro #'.$ahorro->id],
                        ],
                        'quantity' => 1,
                    ];
                }

                $session = $stripe->checkout->sessions->create([
                    'mode'           => 'subscription',
                    'customer_email' => $cliente->email,
                    'line_items'     => $lineItems,

                    // ✅ expira rápido para que webhook pueda limpiar pending si no pagan
                    'expires_at' => $this->checkoutExpiresAt(),

                    'client_reference_id' => 'ahorro:create:'.$ahorro->id,

                    'metadata' => $meta,
                    'subscription_data' => [
                        'metadata' => $meta,
                    ],

                    'success_url' => $frontBase.'/cliente/ahorros?status=success&action=create'
                        .'&ahorro_id='.$ahorro->id.'&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'  => $frontBase.'/cliente/ahorros?status=cancel&action=create&ahorro_id='.$ahorro->id,
                ]);

                // ✅ guardar session_id inmediatamente en BD
                $this->persistCheckoutSession($ahorro, (string)($session->id ?? ''), 'create');

                return response()->json([
                    'ok'           => true,
                    'checkout_url' => $session->url,
                    'session_id'   => $session->id,
                    'expires_at'   => $session->expires_at ?? null,
                ]);
            } catch (\Throwable $e) {
                // ✅ rollback: si NO se pudo crear sesión, borra el pending limpio
                try {
                    DB::transaction(function () use ($ahorro) {
                        $row = UserAhorro::where('id', $ahorro->id)->lockForUpdate()->first();
                        if (!$row) return;

                        $pending = ((int)($row->status ?? 0) === 0);
                        $noSub   = empty($row->stripe_subscription_id);

                        if ($pending && $noSub) {
                            $row->delete();
                        }
                    });
                } catch (\Throwable $rollbackEx) {
                    Log::warning('Rollback delete ahorro pending failed after checkout create error', [
                        'ahorro_id' => $ahorro->id,
                        'err'       => $rollbackEx->getMessage(),
                    ]);
                }

                Log::error('Stripe ahorro checkout create error', [
                    'ahorro_id'  => $ahorro->id,
                    'cliente_id' => $cliente->id,
                    'msg'        => $e->getMessage(),
                ]);

                return response()->json(['ok' => false, 'error' => 'Error al crear sesión Stripe (create): '.$e->getMessage()], 500);
            }
        }

        // ===========================
        // UPDATE => SUMAR (incrementos)
        // ===========================
        if ($action === 'update') {
            $addMonto = $this->normalizaMonto($data['add_monto'] ?? $data['new_monto_inicial'] ?? 0);
            $addCuota = (float) ($data['add_cuota'] ?? $data['new_cuota'] ?? 0);

            if ($addMonto <= 0 && $addCuota <= 0) {
                return response()->json(['ok' => false, 'error' => 'No hay nada que sumar (monto/cuota en 0).'], 422);
            }

            $oldSubId = trim((string) ($data['old_subscription_id'] ?? ($ahorro->stripe_subscription_id ?? '')));
            if ($oldSubId === '') {
                return response()->json(['ok' => false, 'error' => 'Este ahorro no tiene suscripción activa en Stripe para actualizar.'], 422);
            }

            $oldMonto = (float) ($ahorro->monto_ahorro ?? 0);
            $oldCuota = (float) ($ahorro->cuota ?? 0);

            $newMontoFinal = $oldMonto + max(0, $addMonto);
            $newCuotaFinal = $oldCuota + max(0, $addCuota);

            $chargeCuotaNow = $this->toBool($data['charge_cuota_now'] ?? null, true);
            $freq = (string) ($ahorro->frecuencia_pago ?: 'Mensual');

            $lineItems = [];

            if ($addMonto > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => 'mxn',
                        'unit_amount'  => (int) round($addMonto * 100),
                        'product_data' => ['name' => 'Aporte adicional ahorro #'.$ahorro->id],
                    ],
                    'quantity' => 1,
                ];
            }

            $chargedAddCuota = 0.0;
            if ($chargeCuotaNow && $addCuota > 0) {
                $chargedAddCuota = $addCuota;
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => 'mxn',
                        'unit_amount'  => (int) round($addCuota * 100),
                        'product_data' => ['name' => 'Incremento de cuota (cobro inmediato) ahorro #'.$ahorro->id],
                    ],
                    'quantity' => 1,
                ];
            }

            if (!$lineItems) {
                return response()->json(['ok' => false, 'error' => 'No hay nada que cobrar hoy.'], 422);
            }

            $meta = [
                'tipo'                => 'ahorro',
                'action'              => 'update',
                'ahorro_id'           => (string) $ahorro->id,
                'cliente_id'          => (string) $cliente->id,

                'old_monto'           => (string) $oldMonto,
                'old_cuota'           => (string) $oldCuota,

                'add_monto'           => (string) max(0, $addMonto),
                'add_cuota'           => (string) max(0, $addCuota),
                'charge_cuota_now'    => $chargeCuotaNow ? '1' : '0',
                'charged_add_cuota'   => (string) $chargedAddCuota,

                'new_monto_final'     => (string) $newMontoFinal,
                'new_cuota_final'     => (string) $newCuotaFinal,

                'old_subscription_id' => (string) $oldSubId,
                'freq'                => $freq,
                'interval'            => (string) $interval,
                'interval_count'      => (string) $intervalCount,
            ];

            try {
                $session = $stripe->checkout->sessions->create([
                    'mode'                => 'payment',
                    'customer_email'      => $cliente->email,
                    'line_items'          => $lineItems,

                    // ✅ también expira rápido
                    'expires_at' => $this->checkoutExpiresAt(),

                    'client_reference_id' => 'ahorro:update:'.$ahorro->id,
                    'metadata'            => $meta,
                    'payment_intent_data' => [
                        'metadata' => $meta,
                    ],
                    'success_url' => $frontBase.'/cliente/ahorros?status=success&action=update'
                        .'&ahorro_id='.$ahorro->id.'&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'  => $frontBase.'/cliente/ahorros?status=cancel&action=update&ahorro_id='.$ahorro->id,
                ]);

                // ✅ guardar session_id inmediatamente en BD
                $this->persistCheckoutSession($ahorro, (string)($session->id ?? ''), 'update');

                return response()->json([
                    'ok'           => true,
                    'checkout_url' => $session->url,
                    'session_id'   => $session->id,
                    'expires_at'   => $session->expires_at ?? null,
                ]);
            } catch (\Throwable $e) {
                Log::error('Stripe ahorro checkout update error', [
                    'ahorro_id'  => $ahorro->id,
                    'cliente_id' => $cliente->id,
                    'msg'        => $e->getMessage(),
                ]);
                return response()->json(['ok' => false, 'error' => 'Error al crear sesión Stripe (update): '.$e->getMessage()], 500);
            }
        }

        return response()->json(['ok' => false, 'error' => 'Acción no soportada.'], 422);
    }
}
