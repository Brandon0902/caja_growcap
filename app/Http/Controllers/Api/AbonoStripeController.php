<?php

// app/Http/Controllers/Api/AbonoStripeController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPrestamo;
use App\Models\UserAbono;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\StripeClient;

class AbonoStripeController extends Controller
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

    /**
     * POST /api/cliente/abonos/{abono}/stripe/checkout
     * Body: { monto: 123.45 }
     */
    public function checkout(Request $request, int $abonoId)
    {
        /** @var Cliente $cliente */
        $cliente = $this->cliente($request);

        $data = $request->validate([
            'monto' => ['required','numeric','min:0.01'],
        ]);

        $abono    = UserAbono::findOrFail($abonoId);
        $prestamo = UserPrestamo::findOrFail($abono->user_prestamo_id);

        if ((int)$prestamo->id_cliente !== (int)$cliente->id) {
            abort(403);
        }

        // ✅ No permitir pagar si ya está pagado (saldo) o pagado stripe pendiente caja
        $st = (int) ($abono->status ?? 0);
        if ($st === 1 || $st === 4) {
            return response()->json([
                'ok'    => false,
                'error' => 'Este abono ya está marcado como pagado.',
            ], 409);
        }

        $monto  = (float)$data['monto'];
        $secret = config('services.stripe.secret');

        if (!$secret) {
            return response()->json([
                'ok'    => false,
                'error' => 'Stripe no está configurado.',
            ], 500);
        }

        $stripe    = new StripeClient($secret);
        $frontBase = rtrim(config('app.front_url', config('app.url')), '/');

        $lineItems = [[
            'price_data' => [
                'currency'    => 'mxn',
                'unit_amount' => (int) round($monto * 100),
                'product_data'=> [
                    'name' => 'Abono préstamo #'.$prestamo->id.' (pago '.$abono->num_pago.')',
                ],
            ],
            'quantity' => 1,
        ]];

        try {
            $session = $stripe->checkout->sessions->create([
                'mode'           => 'payment',
                'customer_email' => $cliente->email,
                'line_items'     => $lineItems,
                'metadata'       => [
                    'tipo'        => 'abono',
                    'abono_id'    => $abono->id,
                    'prestamo_id' => $prestamo->id,
                    'cliente_id'  => $cliente->id,
                    'monto'       => (string) $monto, // respaldo
                ],
                'success_url' => $frontBase.'/cliente/abonos?status=success&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => $frontBase.'/cliente/abonos?status=cancel',
            ]);

            // ✅ Guardar checkout vigente para anti-stale + auditoría
            $table = $abono->getTable();

            if (Schema::hasColumn($table, 'stripe_checkout_session_id')) {
                $abono->stripe_checkout_session_id = $session->id;
            }
            if (Schema::hasColumn($table, 'payment_method')) {
                $abono->payment_method = 'stripe';
            }
            if (Schema::hasColumn($table, 'payment_status')) {
                $abono->payment_status = 'pending';
            }
            if (Schema::hasColumn($table, 'stripe_status')) {
                $abono->stripe_status = 'incomplete';
            }

            $abono->save();

            return response()->json([
                'ok'           => true,
                'checkout_url' => $session->url,
                'session_id'   => $session->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe abono checkout error', [
                'abono_id'    => $abono->id,
                'prestamo_id' => $prestamo->id,
                'cliente_id'  => $cliente->id,
                'msg'         => $e->getMessage(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => 'Error al crear sesión de Stripe: '.$e->getMessage(),
            ], 500);
        }
    }
}
