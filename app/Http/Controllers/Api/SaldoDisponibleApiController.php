<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SaldoDisponibleService;
use Illuminate\Http\Request;

class SaldoDisponibleApiController extends Controller
{
    public function __construct(private SaldoDisponibleService $saldoService) {}

    /**
     * GET /api/cliente/saldo-disponible
     */
    public function show(Request $request)
    {
        $user = $request->user(); // sanctum
        abort_if(!$user, 401);

        // ✅ Soporta ambos: Cliente->id o User->id_cliente
        $clienteId = (int)($user->id_cliente ?? $user->id ?? 0);
        abort_if($clienteId <= 0, 422, 'No se pudo determinar id_cliente.');

        $detalle = $this->saldoService->forCliente($clienteId);

        $saldo = (float) round(max(0, (float)($detalle['total'] ?? 0)), 2);

        return response()->json([
            'fecha'            => now()->toDateString(),
            'saldo_disponible' => $saldo, // ✅ número directo
            'detalle'          => [
                'sd_ahorros'     => (float) ($detalle['sd_ahorros'] ?? 0),
                'sd_depositos'   => (float) ($detalle['sd_depositos'] ?? 0),
                'sd_inversiones' => (float) ($detalle['sd_inversiones'] ?? 0),
            ],
        ]);
    }
}
