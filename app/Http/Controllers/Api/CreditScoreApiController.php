<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\UserData;
use App\Services\CreditScoreService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class CreditScoreApiController extends Controller
{
    public function __construct(private CreditScoreService $svc)
    {
        $this->middleware('auth:sanctum');
    }

    /** Resuelve el cliente autenticado (Sanctum). */
    private function cliente(Request $request): Cliente
    {
        $u = auth('sanctum')->user() ?? $request->user();
        if ($u instanceof Cliente) return $u;

        if ($u && isset($u->id_cliente) && $u->id_cliente) {
            if ($c = Cliente::find($u->id_cliente)) return $c;
        }
        throw new AuthenticationException('El token no corresponde a un cliente.');
    }

    /** GET /api/credit-score */
    public function show(Request $request)
    {
        $cliente = $this->cliente($request);
        $calc    = $this->svc->compute((int) $cliente->id);

        // Cachear últimos resultados en user_data (opcional)
        $ud = UserData::firstOrCreate(['id_cliente' => $cliente->id]);
        $ud->update([
            'credit_score'     => $calc['score'],
            'credit_range'     => $calc['range'],
            'credit_breakdown' => $calc['reasons'],
            'credit_last_calc' => now(),
            'credit_version'   => $calc['version'],
        ]);

        return response()->json([
            'ok'        => true,
            'score'     => $calc['score'],      // 300–850 (UI)
            'score_raw' => $calc['score_raw'],  // Puntaje crudo (auditoría)
            'range'     => $calc['range'],      // Malo/Regular/Bueno
            'reasons'   => $calc['reasons'],    // Cada motivo trae raw_points y ui_points
            'ui_meta'   => $calc['ui_meta'],    // <<< Base + suma de factores + k + total (para cuadrar el 300–850)
            'raw'       => $calc['raw'],        // Datos fuente del cálculo
            'last_calc' => now()->toIso8601String(),
            'version'   => $calc['version'],
        ]);
    }

    /** POST /api/credit-score/recalc */
    public function recalc(Request $request)
    {
        return $this->show($request);
    }
}
