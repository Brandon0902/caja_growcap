<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inversion;
use App\Models\UserInversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\StripeClient;

class UserInversionStripeController extends Controller
{
    private function withQuery(string $url, array $params): string
    {
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . http_build_query($params);
    }

    private function firstExistingCol(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }

    /**
     * POST /api/inversiones/{id}/stripe/checkout
     * Body: { return_url: "https://clientegrowcap.tld/cliente/inversiones" }
     */
    public function checkout(Request $request, int $id)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        $data = $request->validate([
            'return_url' => ['required', 'url'],
        ]);

        $inv = UserInversion::query()
            ->where('id', $id)
            ->where('id_cliente', $cliente->id)
            ->first();

        if (!$inv) return response()->json(['ok'=>false,'error'=>'Inversión no encontrada.'], 404);
        if ((int)$inv->status !== 1) return response()->json(['ok'=>false,'error'=>'Solo se puede pagar una inversión Pendiente.'], 409);

        $monto = (float)($inv->inversion ?? 0);
        if ($monto <= 0) return response()->json(['ok'=>false,'error'=>'Monto inválido.'], 422);

        $secret = config('services.stripe.secret');
        if (!$secret) return response()->json(['ok'=>false,'error'=>'Stripe no está configurado (services.stripe.secret).'], 500);

        $plan = Inversion::find($inv->id_activo);
        $labelPlan = $plan ? trim(($plan->nombre ?? '') . ' ' . ($plan->periodo ? "({$plan->periodo})" : '')) : '';
        if ($labelPlan === '') $labelPlan = "Plan #{$inv->id_activo}";

        $stripe = new StripeClient($secret);

        $successUrl = $this->withQuery($data['return_url'], [
            'status'       => 'success',
            'inversion_id' => $inv->id,
            'session_id'   => '{CHECKOUT_SESSION_ID}',
        ]);

        $cancelUrl = $this->withQuery($data['return_url'], [
            'status'       => 'cancel',
            'inversion_id' => $inv->id,
        ]);

        // ✅ Metadata alineada con tu webhook (tipo/action)
        $meta = [
            'tipo'        => 'inversion',
            'action'      => 'pay',
            'inversion_id'=> (string)$inv->id,
            'plan_id'     => (string)$inv->id_activo,
            'cliente_id'  => (string)$cliente->id,
        ];

        try {
            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'customer_email' => $cliente->email ?? null,

                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'mxn',
                        'unit_amount' => max(1, (int) round($monto * 100)),
                        'product_data' => [
                            'name' => "Inversión {$labelPlan}",
                            'description' => "Solicitud #{$inv->id}",
                        ],
                    ],
                ]],

                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,

                // ✅ metadata en Session
                'metadata' => $meta,

                // ✅ metadata también en PaymentIntent (recomendado)
                'payment_intent_data' => [
                    'metadata' => $meta,
                ],
            ]);

            // Guardar session_id si hay columna (opcional)
            $tbl = $inv->getTable();
            $colSession = $this->firstExistingCol($tbl, [
                'stripe_session_id', 'stripe_checkout_session_id', 'checkout_session_id'
            ]);
            if ($colSession) {
                $inv->{$colSession} = $session->id;
            }

            // (opcional) guardar PI si tienes columna
            $colPi = $this->firstExistingCol($tbl, [
                'stripe_payment_intent_id', 'payment_intent_id'
            ]);
            if ($colPi && !empty($session->payment_intent)) {
                $inv->{$colPi} = (string)$session->payment_intent;
            }

            if ($colSession || $colPi) $inv->save();

            return response()->json([
                'ok' => true,
                'url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Stripe checkout inversiones error', [
                'inv_id' => $inv->id,
                'ex' => $e->getMessage(),
            ]);
            return response()->json(['ok'=>false,'error'=>'No se pudo iniciar el pago con Stripe.'], 500);
        }
    }
}
