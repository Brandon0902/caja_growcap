<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDeposito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckout;

class DepositosCheckoutController extends Controller
{
    /**
     * POST /api/depositos/{id}/checkout
     * Body: { return_url: "https://clientegrowcap.casabarrel.com/cliente/depositos/123/status" }
     * La API AGREGA: ?status=success|cancel&session_id=...
     */
    public function create(Request $request, int $id)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        $data = $request->validate([
            'return_url' => ['required', 'url'],
        ]);

        $dep = UserDeposito::where('id', $id)
            ->where('id_cliente', $cliente->id)
            ->firstOrFail();

        if ((int) $dep->status !== 0) {
            return response()->json([
                'ok'    => false,
                'error' => 'El depósito no está pendiente.',
            ], 409);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $amountCents = (int) round(((float)$dep->cantidad) * 100);

        // Base de la URL de regreso que manda el cliente
        $baseReturn = rtrim((string)$data['return_url'], '/');

        // Si ya tiene "?" usamos "&"; si no, "?"
        $separator = str_contains($baseReturn, '?') ? '&' : '?';

        $successUrl = $baseReturn . $separator . 'status=success&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = $baseReturn . $separator . 'status=cancel';

        Log::info('API CAJA DepositosCheckout create', [
            'deposito_id'   => $dep->id,
            'cliente_id'    => $cliente->id,
            'cliente_email' => $cliente->email ?? null,
            'baseReturn'    => $baseReturn,
            'success_url'   => $successUrl,
            'cancel_url'    => $cancelUrl,
        ]);

        try {
            $session = StripeCheckout::create([
                'mode'                 => 'payment',
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'     => 'mxn',
                        'product_data' => [
                            'name' => 'Depósito Growcap #' . $dep->id,
                        ],
                        'unit_amount'  => $amountCents,
                    ],
                ]],

                // ✅ metadata útil para webhook/depuración
                'metadata' => [
                    'tipo'        => 'deposito',
                    'action'      => 'create',
                    'deposito_id' => (string) $dep->id,
                    'entity_id'   => (string) $dep->id,
                    'cliente_id'  => (string) $dep->id_cliente,
                    'reason'      => 'deposito',
                ],

                // prellenar el correo en Checkout
                'customer_email' => $cliente->email ?? null,

                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe checkout create error', [
                'deposito_id' => $dep->id,
                'cliente_id'  => $cliente->id,
                'ex'          => $e->getMessage(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => 'No se pudo crear la sesión de pago.',
            ], 500);
        }

        // Guardamos datos de Stripe en el depósito (solo si existen las columnas)
        $table = $dep->getTable();

        if (Schema::hasColumn($table, 'stripe_checkout_session_id')) {
            $dep->stripe_checkout_session_id = $session->id;
        }

        if (Schema::hasColumn($table, 'stripe_status')) {
            $dep->stripe_status = 'created';
        }

        // (opcional) tu tabla quizá maneja payment_method/payment_status
        if (Schema::hasColumn($table, 'payment_method')) {
            $dep->payment_method = 'stripe';
        }
        if (Schema::hasColumn($table, 'payment_status')) {
            $dep->payment_status = 'created';
        }

        $dep->save();

        return response()->json([
            'ok'  => true,
            'url' => $session->url,
        ]);
    }
}
