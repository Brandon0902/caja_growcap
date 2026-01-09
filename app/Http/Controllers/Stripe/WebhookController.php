<?php

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;

use App\Models\UserAhorro;
use App\Models\UserInversion;
use App\Models\UserDeposito;
use App\Models\Inversion;
use App\Models\MovimientoCaja;
use App\Models\StripeReturnMessage;
use App\Models\Cliente;

use Stripe\Webhook;
use Stripe\StripeClient;

use App\Domain\Ahorros\Support\MovimientosHelper;

// ✅ NUEVOS MAILS
use App\Mail\NuevaInversionSolicitudMail;
use App\Mail\NuevaInversionSolicitudClienteMail;

// ✅ NUEVO MAIL DEPÓSITO STRIPE CLIENTE
use App\Mail\DepositoStripePaidClienteMail;
use App\Mail\DepositoStripePagadoAdminMail;
use App\Mail\DepositoStripePagadoClienteMail;

class WebhookController extends Controller
{
    private function stripeMetaToArray($meta): array
    {
        if (!$meta) return [];
        try {
            if (is_array($meta)) return $meta;
            if (is_object($meta) && method_exists($meta, 'toArray')) return $meta->toArray();
            return (array) $meta;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function stripe(): StripeClient
    {
        $secretKey = config('services.stripe.secret');
        if (!$secretKey) throw new \RuntimeException('Stripe secret no configurado.');
        return new StripeClient($secretKey);
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
        if ($pid === '') throw new \RuntimeException('Falta STRIPE_AHORROS_PRODUCT_ID (producto activo).');
        return $pid;
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

    private function updateStripeSubscriptionAmount(string $subscriptionId, float $newCuota, string $freq): void
    {
        [$interval, $intervalCount] = $this->intervalFromFreq($freq);

        $stripe = $this->stripe();

        $sub = $stripe->subscriptions->retrieve($subscriptionId, [
            'expand' => ['items.data.price'],
        ]);

        $item = $sub->items->data[0] ?? null;
        if (!$item) throw new \RuntimeException('No se encontró subscription item.');

        $productId = $this->ahorrosProductId();

        $priceId = $this->getOrCreateRecurringPrice(
            $stripe,
            $productId,
            (int) round($newCuota * 100),
            $interval,
            $intervalCount
        );

        $stripe->subscriptions->update($subscriptionId, [
            'items' => [[
                'id'    => $item->id,
                'price' => $priceId,
            ]],
            'proration_behavior' => 'none',
        ]);
    }

    private function fetchMetaFromStripe(string $sessionId): array
    {
        try {
            $stripe = $this->stripe();

            $s = $stripe->checkout->sessions->retrieve($sessionId, [
                'expand' => ['payment_intent'],
            ]);

            $meta = $this->stripeMetaToArray($s->metadata ?? []);

            if (empty($meta) && !empty($s->payment_intent)) {
                $meta = $this->stripeMetaToArray($s->payment_intent->metadata ?? []);
            }

            return $meta;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function fetchMetaFromSubscription(string $subscriptionId): array
    {
        try {
            $stripe = $this->stripe();
            $sub = $stripe->subscriptions->retrieve($subscriptionId, []);
            return $this->stripeMetaToArray($sub->metadata ?? []);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function saveReturnMessage(array $data): void
    {
        try {
            StripeReturnMessage::create([
                'tipo'              => $data['tipo'] ?? 'unknown',
                'entity_id'         => $data['entity_id'] ?? null,
                'user_id'           => $data['user_id'] ?? null,
                'session_id'        => $data['session_id'] ?? null,
                'payment_intent_id' => $data['payment_intent_id'] ?? null,
                'status'            => $data['status'] ?? 'success',
                'message'           => $data['message'] ?? '',
                'seen'              => 0,
            ]);
        } catch (\Throwable $e) {
            Log::warning('StripeReturnMessage::create failed', [
                'err'  => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    private function cancelStripeSubscriptionSafely(?string $subId): void
    {
        $subId = trim((string)$subId);
        if ($subId === '') return;

        try {
            $stripe = $this->stripe();
            $stripe->subscriptions->cancel($subId, []);
        } catch (\Throwable $e) {
            Log::warning('cancelStripeSubscriptionSafely failed', [
                'sub' => $subId,
                'err' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ✅ NUEVO (anti-stale):
     * Ignora eventos de checkout viejos cuando ya existe un checkout "vigente" guardado en BD.
     * - Si NO existe la columna => no bloquea.
     * - Si la columna existe pero está vacía => no bloquea (compat).
     */
    private function isCurrentAhorroCheckout(int $ahorroId, string $sessionId): bool
    {
        if ($ahorroId <= 0 || $sessionId === '') return true;

        $table = 'user_ahorro';
        if (!Schema::hasColumn($table, 'stripe_checkout_session_id')) return true;

        $stored = (string) (UserAhorro::where('id', $ahorroId)->value('stripe_checkout_session_id') ?? '');
        $stored = trim($stored);

        if ($stored === '') return true; // compat: si no lo guardaron, no bloqueamos

        return hash_equals($stored, $sessionId);
    }

    private function isCurrentDepositoCheckout(int $depositoId, string $sessionId): bool
    {
        if ($depositoId <= 0 || $sessionId === '') return true;

        $table = (new UserDeposito())->getTable();
        if (!Schema::hasColumn($table, 'stripe_checkout_session_id')) return true;

        $stored = (string) (UserDeposito::where('id', $depositoId)->value('stripe_checkout_session_id') ?? '');
        $stored = trim($stored);

        if ($stored === '') return true;

        return hash_equals($stored, $sessionId);
    }

    private function isCurrentInversionCheckout(int $invId, string $sessionId): bool
    {
        if ($invId <= 0 || $sessionId === '') return true;

        $table = (new UserInversion())->getTable();

        // tu tabla usa stripe_session_id (según tu código), pero por si acaso:
        $col = null;
        if (Schema::hasColumn($table, 'stripe_session_id')) $col = 'stripe_session_id';
        elseif (Schema::hasColumn($table, 'stripe_checkout_session_id')) $col = 'stripe_checkout_session_id';

        if (!$col) return true;

        $stored = (string) (UserInversion::where('id', $invId)->value($col) ?? '');
        $stored = trim($stored);

        if ($stored === '') return true;

        return hash_equals($stored, $sessionId);
    }
    
    // ✅ NUEVO: anti-stale guard para ABONO (checkout vigente)
    private function isCurrentAbonoCheckout(int $abonoId, string $sessionId): bool
    {
        if ($abonoId <= 0 || $sessionId === '') return true;
    
        $table = 'user_abonos';
        if (!Schema::hasColumn($table, 'stripe_checkout_session_id')) return true;
    
        $stored = (string) (DB::table($table)->where('id', $abonoId)->value('stripe_checkout_session_id') ?? '');
        $stored = trim($stored);
    
        // compat: si no lo guardaron, no bloqueamos
        if ($stored === '') return true;
    
        return hash_equals($stored, $sessionId);
    }


    private function rollbackDeletePendingAhorro(int $ahorroId, ?string $reason = null): void
    {
        try {
            DB::transaction(function () use ($ahorroId) {
                $a = UserAhorro::where('id', $ahorroId)->lockForUpdate()->first();
                if (!$a) return;

                $pending = ((int)($a->status ?? 0) === 0);
                if (!$pending) return;

                if (!empty($a->stripe_subscription_id)) {
                    $this->cancelStripeSubscriptionSafely($a->stripe_subscription_id);
                }

                $a->delete();
            });

            $this->saveReturnMessage([
                'tipo'      => 'ahorro',
                'entity_id' => $ahorroId,
                'status'    => 'warning',
                'message'   => $reason ?: 'El pago no se completó. Se eliminó el ahorro pendiente (rollback).',
            ]);
        } catch (\Throwable $e) {
            Log::warning('rollbackDeletePendingAhorro failed', [
                'ahorro_id' => $ahorroId,
                'err'       => $e->getMessage(),
            ]);
        }
    }

    private function activateAhorroFromMeta(
        int $ahorroId,
        string $subscriptionId,
        array $meta,
        ?string $invoiceOrPi = null
    ): void {
        $montoInicial = (float) (Arr::get($meta, 'monto_inicial', 0) ?: 0);
        $cuota        = (float) (Arr::get($meta, 'cuota', 0) ?: 0);

        // ✅ flags
        $chargeMontoNow = $this->toBool(Arr::get($meta, 'charge_monto_now', true), true);
        $chargeCuotaNow = $this->toBool(Arr::get($meta, 'charge_cuota_now', true), true);

        // ✅ monto/cuota realmente cobrados
        $chargedMonto = (float) (Arr::get($meta, 'charged_monto', 0) ?: 0);
        if ($chargedMonto <= 0 && $chargeMontoNow && $montoInicial > 0) $chargedMonto = $montoInicial;

        $chargedCuota = (float) (Arr::get($meta, 'charged_cuota', 0) ?: 0);
        if ($chargedCuota <= 0 && $chargeCuotaNow && $cuota > 0) $chargedCuota = $cuota;

        $deltaSaldoFecha = max(0, $chargedMonto) + max(0, $chargedCuota);

        $a = UserAhorro::find($ahorroId);
        if (!$a) return;

        DB::transaction(function () use ($a, $subscriptionId, $deltaSaldoFecha, $chargedMonto, $chargedCuota, $invoiceOrPi) {
            $row = UserAhorro::where('id', $a->id)->lockForUpdate()->first();
            if (!$row) return;

            // ✅ Solo si sigue PENDIENTE
            if ((int)($row->status ?? 0) !== 0) return;

            $table = $row->getTable();

            // ✅ Idempotencia EXTRA:
            if (Schema::hasColumn($table, 'stripe_paid_at') && !empty($row->stripe_paid_at)) return;
            if (Schema::hasColumn($table, 'fecha_pago') && !empty($row->fecha_pago)) return;
            if (Schema::hasColumn($table, 'payment_status') && ($row->payment_status === 'paid')) return;

            if ($subscriptionId !== '') {
                $row->stripe_subscription_id = $subscriptionId;
            }

            if ($invoiceOrPi && Schema::hasColumn($table, 'stripe_payment_intent_id')) {
                $row->stripe_payment_intent_id = $invoiceOrPi;
            }
            if ($invoiceOrPi && Schema::hasColumn($table, 'stripe_invoice_id')) {
                $row->stripe_invoice_id = $invoiceOrPi;
            }

            if (Schema::hasColumn($table, 'payment_method')) {
                $row->payment_method = 'stripe';
            }
            if (Schema::hasColumn($table, 'payment_status')) {
                $row->payment_status = 'paid';
            }
            if (Schema::hasColumn($table, 'stripe_status')) {
                $row->stripe_status = 'paid';
            }
            if (Schema::hasColumn($table, 'stripe_paid_at')) {
                $row->stripe_paid_at = now();
            }
            if (Schema::hasColumn($table, 'fecha_pago')) {
                $row->fecha_pago = now();
            }

            if ($deltaSaldoFecha > 0 && Schema::hasColumn($table, 'saldo_fecha')) {
                $row->saldo_fecha = (float)($row->saldo_fecha ?? 0) + $deltaSaldoFecha;
            }

            // ✅ NO activar aquí
            // $row->status = 1;

            // ✅ NO tocar saldo_disponible
            $row->save();

            try {
                if ($chargedMonto > 0) {
                    MovimientosHelper::registrar(
                        $row,
                        'STRIPE_MONTO_INICIAL',
                        $chargedMonto,
                        (float)($row->saldo_disponible ?? 0),
                        'Pago inicial (monto) confirmado por Stripe'
                    );
                }
                if ($chargedCuota > 0) {
                    MovimientosHelper::registrar(
                        $row,
                        'STRIPE_CUOTA_INICIAL',
                        $chargedCuota,
                        (float)($row->saldo_disponible ?? 0),
                        'Pago inicial (cuota) confirmado por Stripe'
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('MovimientosHelper registrar failed (ahorro create paid)', [
                    'ahorro_id' => $row->id,
                    'err'       => $e->getMessage(),
                ]);
            }
        });

        $this->saveReturnMessage([
            'tipo'              => 'ahorro',
            'entity_id'         => $ahorroId,
            'user_id'           => $a->id_cliente ?? null,
            'payment_intent_id' => $invoiceOrPi ?: null,
            'status'            => 'success',
            'message'           => 'Pago confirmado en Stripe. El ahorro quedó pendiente de revisión y aprobación por un admin.',
        ]);
    }

    private function confirmDepositoFromCheckout(
        int $depositoId,
        bool $paid,
        string $sessionId,
        string $piId
    ): void {
        $dep = UserDeposito::find($depositoId);
        if (!$dep) return;

        if (!$paid) {
            $this->saveReturnMessage([
                'tipo'              => 'deposito',
                'entity_id'         => $depositoId,
                'user_id'           => $dep->id_cliente ?? null,
                'session_id'        => $sessionId ?: null,
                'payment_intent_id' => $piId ?: null,
                'status'            => 'warning',
                'message'           => 'El pago de Stripe no se completó. El depósito sigue pendiente.',
            ]);
            return;
        }

        // ✅ Idempotencia extra (por si llega reintento con mismo PI)
        if ($piId !== '' && StripeReturnMessage::where('payment_intent_id', $piId)->where('tipo', 'deposito')->exists()) {
            return;
        }

        DB::transaction(function () use ($depositoId, $sessionId, $piId) {
            $row = UserDeposito::where('id', $depositoId)->lockForUpdate()->first();
            if (!$row) return;

            // Solo depósitos Pendientes
            if ((int)($row->status ?? 0) !== 0) return;

            $table = $row->getTable();

            // ✅ NO activar el depósito: se queda status=0 (Pendiente)
            $row->status = 0;

            // Guardar referencias Stripe
            if ($sessionId !== '' && Schema::hasColumn($table, 'stripe_checkout_session_id')) {
                $row->stripe_checkout_session_id = $sessionId;
            }
            if ($piId !== '' && Schema::hasColumn($table, 'stripe_payment_intent_id')) {
                $row->stripe_payment_intent_id = $piId;
            }

            if (Schema::hasColumn($table, 'payment_method')) {
                $row->payment_method = 'stripe';
            }
            if (Schema::hasColumn($table, 'payment_status')) {
                $row->payment_status = 'paid';
            }
            if (Schema::hasColumn($table, 'stripe_status')) {
                $row->stripe_status = 'paid';
            }
            if (Schema::hasColumn($table, 'fecha_pago')) {
                $row->fecha_pago = now();
            }

            $row->save();

            // ⛔ NO MovimientoCaja aquí; eso al aprobar admin.
        });

        try {
            $depFresh = UserDeposito::find($depositoId);
            if ($depFresh) {
                $cli = Cliente::find($depFresh->id_cliente);

                Mail::to('admingrowcap@casabarrel.com')
                    ->send(new DepositoStripePagadoAdminMail($depFresh, $cli, $piId ?: null, $sessionId ?: null));

                if ($cli && !empty($cli->email)) {
                    Mail::to($cli->email)
                        ->send(new DepositoStripePagadoClienteMail($depFresh, $cli));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Webhook deposito: no se pudieron enviar correos (admin/cliente)', [
                'deposito_id' => $depositoId,
                'err'         => $e->getMessage(),
            ]);
        }

        $this->saveReturnMessage([
            'tipo'              => 'deposito',
            'entity_id'         => $depositoId,
            'user_id'           => $dep->id_cliente ?? null,
            'session_id'        => $sessionId ?: null,
            'payment_intent_id' => $piId ?: null,
            'status'            => 'success',
            'message'           => 'El pago se realizó correctamente. Será revisado por un admin lo antes posible.',
        ]);
    }

    private function activateInversionFromCheckout(
        int $invId,
        array $meta,
        bool $paid,
        string $sessionId,
        string $piId
    ): void {
        $inv = UserInversion::find($invId);
        if (!$inv) return;

        if (!$paid) {
            $this->saveReturnMessage([
                'tipo'              => 'inversion',
                'entity_id'         => $invId,
                'user_id'           => $inv->id_cliente ?? null,
                'session_id'        => $sessionId ?: null,
                'payment_intent_id' => $piId ?: null,
                'status'            => 'warning',
                'message'           => 'El pago de Stripe no se completó. La inversión sigue en Pendiente.',
            ]);
            return;
        }

        DB::transaction(function () use ($invId, $sessionId, $piId, $paid) {
            $row = UserInversion::where('id', $invId)->lockForUpdate()->first();
            if (!$row) return;

            // Solo inversiones Pendientes
            if ((int)($row->status ?? 0) !== 1) return;

            $table = $row->getTable();

            if (Schema::hasColumn($table, 'payment_method')) {
                $row->payment_method = 'stripe';
            }
            if (Schema::hasColumn($table, 'payment_status')) {
                $row->payment_status = $paid ? 'paid' : ($row->payment_status ?? 'pending');
            }
            if (Schema::hasColumn($table, 'stripe_status')) {
                $row->stripe_status = 'paid';
            }
            if (Schema::hasColumn($table, 'stripe_paid_at')) {
                $row->stripe_paid_at = now();
            }
            if ($sessionId !== '' && Schema::hasColumn($table, 'stripe_session_id')) {
                $row->stripe_session_id = $sessionId;
            }
            if ($piId !== '' && Schema::hasColumn($table, 'stripe_payment_intent_id')) {
                $row->stripe_payment_intent_id = $piId;
            }

            $row->save();
        });

        if ($piId !== '' && StripeReturnMessage::where('payment_intent_id', $piId)->where('tipo', 'inversion')->exists()) {
            return;
        }

        try {
            $planCols = ['id', 'periodo', 'rendimiento'];
            if (Schema::hasColumn('inversiones', 'nombre')) $planCols[] = 'nombre';

            $invFresh = UserInversion::with([
                'plan' => fn($q) => $q->select($planCols),
                'caja' => fn($q) => $q->select(['id_caja', 'nombre']),
            ])->find($invId);

            $cli = $invFresh ? Cliente::find($invFresh->id_cliente) : null;

            if ($invFresh && $cli) {
                Mail::to('admingrowcap@casabarrel.com')
                    ->send(new NuevaInversionSolicitudMail($invFresh, $cli));

                if (!empty($cli->email)) {
                    Mail::to($cli->email)
                        ->send(new NuevaInversionSolicitudClienteMail($invFresh, $cli));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Webhook inversion: no se pudieron enviar correos (admin/cliente)', [
                'inv_id' => $invId,
                'err'    => $e->getMessage(),
            ]);
        }

        $this->saveReturnMessage([
            'tipo'              => 'inversion',
            'entity_id'         => $invId,
            'user_id'           => $inv->id_cliente ?? null,
            'session_id'        => $sessionId ?: null,
            'payment_intent_id' => $piId ?: null,
            'status'            => 'success',
            'message'           => 'Pago confirmado en Stripe. Inversión pagada y pendiente de revisión.',
        ]);
    }
    
    // ✅ NUEVO: confirmar ABONO pagado con Stripe (status=4) y pendiente asignar caja en admin
    private function confirmAbonoFromCheckout(
        int $abonoId,
        bool $paid,
        string $sessionId,
        string $piId,
        array $meta
    ): void {
        $abono = \App\Models\UserAbono::find($abonoId);
        if (!$abono) return;
    
        if (!$paid) {
            $this->saveReturnMessage([
                'tipo'              => 'abono',
                'entity_id'         => $abonoId,
                'user_id'           => $abono->id_cliente ?? null,
                'session_id'        => $sessionId ?: null,
                'payment_intent_id' => $piId ?: null,
                'status'            => 'warning',
                'message'           => 'El pago de Stripe no se completó. El abono sigue pendiente.',
            ]);
            return;
        }
    
        // ✅ Idempotencia extra por PI
        if ($piId !== '' && \App\Models\StripeReturnMessage::where('payment_intent_id', $piId)->where('tipo', 'abono')->exists()) {
            return;
        }
    
        $monto = (float) (\Illuminate\Support\Arr::get($meta, 'monto', 0) ?: 0);
    
        \Illuminate\Support\Facades\DB::transaction(function () use ($abonoId, $sessionId, $piId, $monto) {
            $row = \App\Models\UserAbono::where('id', $abonoId)->lockForUpdate()->first();
            if (!$row) return;
    
            $table = $row->getTable();
    
            $st = (int) ($row->status ?? 0);
            if ($st === 1 || $st === 4) return;
    
            // ✅ Pagado con Stripe, pendiente caja
            $row->status = 4;
    
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'pago_at')) {
                $row->pago_at = now();
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'pago_monto') && $monto > 0) {
                $row->pago_monto = $monto;
            }
    
            if ($sessionId !== '' && \Illuminate\Support\Facades\Schema::hasColumn($table, 'stripe_checkout_session_id')) {
                $row->stripe_checkout_session_id = $sessionId;
            }
            if ($piId !== '' && \Illuminate\Support\Facades\Schema::hasColumn($table, 'stripe_payment_intent_id')) {
                $row->stripe_payment_intent_id = $piId;
            }
    
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'payment_method')) {
                $row->payment_method = 'stripe';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'payment_status')) {
                $row->payment_status = 'paid';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'stripe_status')) {
                $row->stripe_status = 'paid';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'stripe_paid_at')) {
                $row->stripe_paid_at = now();
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'fecha_pago')) {
                $row->fecha_pago = now();
            }
    
            $row->save();
    
            // ⛔ NO MovimientoCaja aquí; eso será cuando admin asigne caja.
        });
    
        $this->saveReturnMessage([
            'tipo'              => 'abono',
            'entity_id'         => $abonoId,
            'user_id'           => $abono->id_cliente ?? null,
            'session_id'        => $sessionId ?: null,
            'payment_intent_id' => $piId ?: null,
            'status'            => 'success',
            'message'           => 'Abono pagado con Stripe. Pendiente de asignación de caja por admin.',
        ]);
    }


    public function __invoke(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature invalid', ['msg' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        $type = (string) ($event->type ?? 'unknown');

        Log::info('Stripe webhook received', [
            'type' => $type,
            'id'   => $event->id ?? null,
        ]);

        try {
            // ============================
            // ✅ EXPIRED (anti-stale guard)
            // ============================
            if ($type === 'checkout.session.expired') {
                $session   = $event->data->object;
                $sessionId = (string) ($session->id ?? '');

                $meta = $this->stripeMetaToArray($session->metadata ?? []);
                if (empty($meta) && $sessionId !== '') {
                    $meta = $this->fetchMetaFromStripe($sessionId);
                }

                $tipo     = (string) Arr::get($meta, 'tipo', '');
                $action   = (string) Arr::get($meta, 'action', 'create');

                // AHORRO (create): rollback SOLO si el sessionId es el checkout vigente en BD
                if ($tipo === 'ahorro' && $action === 'create') {
                    $ahorroId = (int) Arr::get($meta, 'ahorro_id', 0);

                    if ($ahorroId > 0) {
                        if (!$this->isCurrentAhorroCheckout($ahorroId, $sessionId)) {
                            Log::info('Expired ignored (stale checkout) ahorro', [
                                'ahorro_id'  => $ahorroId,
                                'session_id' => $sessionId,
                            ]);
                            return response()->json(['received' => true]);
                        }

                        $this->rollbackDeletePendingAhorro($ahorroId, 'El checkout expiró sin pago. Rollback aplicado.');
                    }
                }

                // (Opcional) si luego manejas expired para deposito/inversion, aplica guards igual:
                // - $this->isCurrentDepositoCheckout(...)
                // - $this->isCurrentInversionCheckout(...)

                return response()->json(['received' => true]);
            }

            if ($type === 'invoice.payment_succeeded') {
                $inv = $event->data->object;

                $subId = (string) ($inv->subscription ?? '');
                if ($subId === '') return response()->json(['received' => true]);

                $meta = $this->stripeMetaToArray($inv->metadata ?? []);
                if (empty($meta)) {
                    $meta = $this->fetchMetaFromSubscription($subId);
                }

                $tipo     = (string) Arr::get($meta, 'tipo', '');
                $action   = (string) Arr::get($meta, 'action', 'create');
                $ahorroId = (int) Arr::get($meta, 'ahorro_id', 0);

                if ($tipo === 'ahorro' && $action === 'create' && $ahorroId > 0) {
                    $pi = (string) ($inv->payment_intent ?? $inv->id ?? '');
                    if ($pi !== '' && StripeReturnMessage::where('payment_intent_id', $pi)->where('tipo', 'ahorro')->exists()) {
                        return response()->json(['received' => true]);
                    }

                    $this->activateAhorroFromMeta(
                        $ahorroId,
                        $subId,
                        $meta,
                        (string)($inv->payment_intent ?? $inv->id ?? '')
                    );
                }

                return response()->json(['received' => true]);
            }

            if ($type === 'invoice.payment_failed') {
                $inv = $event->data->object;
                $subId = (string) ($inv->subscription ?? '');
                if ($subId === '') return response()->json(['received' => true]);

                $meta = $this->fetchMetaFromSubscription($subId);

                $tipo     = (string) Arr::get($meta, 'tipo', '');
                $action   = (string) Arr::get($meta, 'action', 'create');
                $ahorroId = (int) Arr::get($meta, 'ahorro_id', 0);

                if ($tipo === 'ahorro' && $action === 'create' && $ahorroId > 0) {
                    $this->rollbackDeletePendingAhorro($ahorroId, 'El pago inicial falló. Se canceló la suscripción y se aplicó rollback.');
                }

                return response()->json(['received' => true]);
            }

            if ($type !== 'checkout.session.completed') {
                return response()->json(['received' => true]);
            }

            $session = $event->data->object;

            $paymentStatus = (string) ($session->payment_status ?? '');
            $paid = ($paymentStatus === 'paid');

            $meta = $this->stripeMetaToArray($session->metadata ?? []);
            $sessionId = (string) ($session->id ?? '');

            if (empty($meta) && $sessionId !== '') {
                $metaFetched = $this->fetchMetaFromStripe($sessionId);
                if (!empty($metaFetched)) $meta = $metaFetched;
            }

            $tipo   = (string) Arr::get($meta, 'tipo', '');
            $action = (string) Arr::get($meta, 'action', 'create');

            $piId = (string) ($session->payment_intent ?? '');

            // ✅ Idempotencia por tipo + payment_intent
            if ($tipo !== '' && $piId !== '' &&
                StripeReturnMessage::where('payment_intent_id', $piId)->where('tipo', $tipo)->exists()
            ) {
                return response()->json(['received' => true]);
            }

            // ==========================================
            // ✅ Guards anti-stale por tipo (COMPLETED)
            // ==========================================

            if ($tipo === 'deposito') {
                $depId = (int) (
                    Arr::get($meta, 'deposito_id', 0)
                    ?: Arr::get($meta, 'entity_id', 0)
                );

                if ($depId > 0 && !$this->isCurrentDepositoCheckout($depId, $sessionId)) {
                    Log::info('Completed ignored (stale checkout) deposito', [
                        'deposito_id' => $depId,
                        'session_id'  => $sessionId,
                    ]);
                    return response()->json(['received' => true]);
                }

                if ($depId > 0) {
                    $this->confirmDepositoFromCheckout($depId, $paid, $sessionId, $piId);
                }

                return response()->json(['received' => true]);
            }
            
            if ($tipo === 'abono') {
                $abonoId = (int) (
                    \Illuminate\Support\Arr::get($meta, 'abono_id', 0)
                    ?: \Illuminate\Support\Arr::get($meta, 'entity_id', 0)
                );
            
                if ($abonoId > 0 && !$this->isCurrentAbonoCheckout($abonoId, $sessionId)) {
                    \Illuminate\Support\Facades\Log::info('Completed ignored (stale checkout) abono', [
                        'abono_id'  => $abonoId,
                        'session_id'=> $sessionId,
                    ]);
                    return response()->json(['received' => true]);
                }
            
                if ($abonoId > 0) {
                    $this->confirmAbonoFromCheckout($abonoId, $paid, $sessionId, $piId, $meta);
                }
            
                return response()->json(['received' => true]);
            }

            if ($tipo === 'inversion') {
                $invId = (int) (
                    Arr::get($meta, 'user_inversion_id', 0)
                    ?: Arr::get($meta, 'inversion_id', 0)
                    ?: Arr::get($meta, 'entity_id', 0)
                );

                if ($invId > 0 && !$this->isCurrentInversionCheckout($invId, $sessionId)) {
                    Log::info('Completed ignored (stale checkout) inversion', [
                        'inv_id'     => $invId,
                        'session_id' => $sessionId,
                    ]);
                    return response()->json(['received' => true]);
                }

                if ($invId > 0) {
                    $this->activateInversionFromCheckout($invId, $meta, $paid, $sessionId, $piId);
                }

                return response()->json(['received' => true]);
            }

            // ✅ AHORRO CREATE: guardar session_id / pi_id en user_ahorro (con guard anti-stale)
            if ($tipo === 'ahorro' && $action === 'create') {
                $ahorroId       = (int) Arr::get($meta, 'ahorro_id', 0);
                $subscriptionId = (string) ($session->subscription ?? '');

                if ($ahorroId > 0 && !$this->isCurrentAhorroCheckout($ahorroId, $sessionId)) {
                    Log::info('Completed ignored (stale checkout) ahorro create', [
                        'ahorro_id'  => $ahorroId,
                        'session_id' => $sessionId,
                    ]);
                    return response()->json(['received' => true]);
                }

                if ($ahorroId > 0) {
                    try {
                        DB::transaction(function () use ($ahorroId, $subscriptionId, $sessionId, $piId) {
                            $row = UserAhorro::where('id', $ahorroId)->lockForUpdate()->first();
                            if (!$row) return;

                            if ($subscriptionId !== '' && empty($row->stripe_subscription_id)) {
                                $row->stripe_subscription_id = $subscriptionId;
                            }

                            if ($sessionId !== '' && Schema::hasColumn('user_ahorro', 'stripe_checkout_session_id')) {
                                $row->stripe_checkout_session_id = $sessionId;
                            }

                            if ($piId !== '' && Schema::hasColumn('user_ahorro', 'stripe_payment_intent_id')) {
                                $row->stripe_payment_intent_id = $piId;
                            }

                            if (Schema::hasColumn('user_ahorro', 'stripe_status')) {
                                $row->stripe_status = 'incomplete';
                            }

                            $row->save();
                        });
                    } catch (\Throwable $e) {
                        Log::warning('ahorro create: save refs failed', [
                            'ahorro_id' => $ahorroId,
                            'err'       => $e->getMessage(),
                        ]);
                    }
                }

                return response()->json(['received' => true]);
            }

            // ✅ AHORRO UPDATE: además guardar session_id / pi_id (con guard anti-stale)
            if ($tipo === 'ahorro' && $action === 'update') {
                $ahorroId = (int) Arr::get($meta, 'ahorro_id', 0);
                if ($ahorroId <= 0) return response()->json(['received' => true]);

                if (!$this->isCurrentAhorroCheckout($ahorroId, $sessionId)) {
                    Log::info('Completed ignored (stale checkout) ahorro update', [
                        'ahorro_id'  => $ahorroId,
                        'session_id' => $sessionId,
                    ]);
                    return response()->json(['received' => true]);
                }

                $a = UserAhorro::find($ahorroId);
                if (!$a) return response()->json(['received' => true]);

                if (!$paid) {
                    $this->saveReturnMessage([
                        'tipo'             => 'ahorro',
                        'entity_id'        => $ahorroId,
                        'user_id'          => $a->id_cliente ?? null,
                        'session_id'       => $sessionId ?: null,
                        'payment_intent_id'=> $piId ?: null,
                        'status'           => 'warning',
                        'message'          => 'El pago de la actualización no se completó. No se aplicaron cambios.',
                    ]);
                    return response()->json(['received' => true]);
                }

                $oldSubId = (string) Arr::get($meta, 'old_subscription_id', '');
                $freq     = (string) Arr::get($meta, 'freq', ($a->frecuencia_pago ?: 'Mensual'));

                $addMonto = (float) (Arr::get($meta, 'add_monto', 0) ?: 0);
                $addCuota = (float) (Arr::get($meta, 'add_cuota', 0) ?: 0);

                $chargeCuotaNow  = $this->toBool(Arr::get($meta, 'charge_cuota_now', '1'), true);
                $chargedAddCuota = (float) (Arr::get($meta, 'charged_add_cuota', 0) ?: 0);
                if ($chargedAddCuota <= 0 && $chargeCuotaNow && $addCuota > 0) {
                    $chargedAddCuota = $addCuota;
                }

                $newMontoFinal = (float) (Arr::get($meta, 'new_monto_final', 0) ?: ((float)($a->monto_ahorro ?? 0) + $addMonto));
                $newCuotaFinal = (float) (Arr::get($meta, 'new_cuota_final', 0) ?: ((float)($a->cuota ?? 0) + $addCuota));

                if ($oldSubId === '') throw new \RuntimeException('Falta old_subscription_id en metadata para update.');

                $this->updateStripeSubscriptionAmount($oldSubId, $newCuotaFinal, $freq);

                DB::transaction(function () use (
                    $ahorroId,
                    $oldSubId,
                    $newMontoFinal,
                    $newCuotaFinal,
                    $addMonto,
                    $chargedAddCuota,
                    $sessionId,
                    $piId
                ) {
                    $row = UserAhorro::where('id', $ahorroId)->lockForUpdate()->first();
                    if (!$row) return;

                    $row->monto_ahorro = $newMontoFinal;
                    $row->cuota        = $newCuotaFinal;
                    $row->status       = 1;

                    if (Schema::hasColumn('user_ahorro', 'stripe_status')) {
                        $row->stripe_status = 'active';
                    }

                    $row->stripe_subscription_id = $oldSubId;

                    $deltaSaldoFecha = 0.0;
                    if ($addMonto > 0) $deltaSaldoFecha += $addMonto;
                    if ($chargedAddCuota > 0) $deltaSaldoFecha += $chargedAddCuota;

                    if ($deltaSaldoFecha > 0) {
                        $row->saldo_fecha = (float)($row->saldo_fecha ?? 0) + $deltaSaldoFecha;
                    }

                    if ($sessionId !== '' && Schema::hasColumn('user_ahorro', 'stripe_checkout_session_id')) {
                        $row->stripe_checkout_session_id = $sessionId;
                    }
                    if ($piId !== '' && Schema::hasColumn('user_ahorro', 'stripe_payment_intent_id')) {
                        $row->stripe_payment_intent_id = $piId;
                    }

                    $row->save();

                    try {
                        if ($addMonto > 0) {
                            MovimientosHelper::registrar(
                                $row,
                                'UPDATE_AHORRO_ADD_MONTO',
                                $addMonto,
                                (float) ($row->saldo_disponible ?? 0),
                                'Update vía Stripe — aporte adicional — checkout '.$sessionId
                            );
                        }
                        if ($chargedAddCuota > 0) {
                            MovimientosHelper::registrar(
                                $row,
                                'UPDATE_AHORRO_ADD_CUOTA',
                                $chargedAddCuota,
                                (float) ($row->saldo_disponible ?? 0),
                                'Update vía Stripe — incremento cuota (cobro inmediato) — checkout '.$sessionId
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::warning('MovimientosHelper::registrar failed (update)', [
                            'ahorro_id' => $row->id,
                            'err'       => $e->getMessage(),
                        ]);
                    }
                });

                $this->saveReturnMessage([
                    'tipo'             => 'ahorro',
                    'entity_id'        => $ahorroId,
                    'user_id'          => $a->id_cliente ?? null,
                    'session_id'       => $sessionId ?: null,
                    'payment_intent_id'=> $piId ?: null,
                    'status'           => 'success',
                    'message'          => 'Actualización pagada. Se sumó el aporte y se incrementó la cuota de la suscripción.',
                ]);

                return response()->json(['received' => true]);
            }

            return response()->json(['received' => true]);

        } catch (\Throwable $e) {
            Log::error('Stripe webhook handler error', [
                'type' => $type,
                'err'  => $e->getMessage(),
            ]);
            return response('Handler error', 500);
        }
    }
}
