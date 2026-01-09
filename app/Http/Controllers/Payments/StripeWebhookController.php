<?php

// app/Http/Controllers/Payments/StripeWebhookController.php
namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\UserDeposito;
use App\Models\MovimientoCaja;
use Stripe\Webhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature invalid: '.$e->getMessage());
            return response()->json(['ok'=>false], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object; // \Stripe\Checkout\Session
            $meta = $session->metadata ?? (object)[];
            if (($meta->reason ?? null) === 'deposito') {
                $depositoId = (int)($meta->deposito_id ?? 0);
                $pi         = $session->payment_intent ?? null;
                $amount     = (float) (($session->amount_total ?? 0) / 100.0);

                DB::transaction(function () use ($depositoId, $pi, $amount, $session) {
                    $dep = UserDeposito::lockForUpdate()->find($depositoId);
                    if (!$dep) return;

                    // Idempotencia: si ya está aprobado, salimos
                    if ((int)$dep->status === 1) return;

                    $dep->stripe_payment_intent_id = $pi;
                    $dep->stripe_status            = 'succeeded';
                    $dep->paid_at                  = now();
                    $dep->status                   = 1; // Aprobado
                    $dep->save();

                    // Registrar movimiento de caja si aún no existe
                    $exists = MovimientoCaja::where('origen_id', $dep->id)
                        ->where('tipo_mov', 'Ingreso')
                        ->exists();

                    if (!$exists) {
                        MovimientoCaja::create([
                            'tipo_mov'   => 'Ingreso',
                            'monto'      => $amount,
                            'concepto'   => 'Depósito Stripe #'.$dep->id,
                            'origen'     => 'deposito',
                            'origen_id'  => $dep->id,
                            'id_caja'    => $dep->id_caja,   // ya lo fijaste en store()
                            'gateway'    => 'stripe',
                            'referencia' => $pi,
                            'fecha'      => Carbon::now(),
                        ]);
                    }
                });
            }
        }

        // (Opcional) atender payment_intent.succeeded/failed, checkout.session.expired, etc.
        return response()->json(['ok'=>true]);
    }
}
