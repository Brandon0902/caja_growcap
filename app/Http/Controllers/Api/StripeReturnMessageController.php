<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StripeReturnMessage;

class StripeReturnMessageController extends Controller
{
    /**
     * GET /api/stripe/return-messages/pull?tipo=ahorro&entity_id=123
     * Devuelve el último mensaje NO visto del usuario autenticado y lo marca como visto (seen=1).
     */
    public function pull(Request $request)
    {
        $data = $request->validate([
            'tipo'      => ['required','string','max:50'],
            'entity_id' => ['nullable','integer'],
        ]);

        $user = $request->user();

        $q = StripeReturnMessage::query()
            ->where('tipo', $data['tipo'])
            ->where('seen', 0)
            ->where('user_id', $user->id)      // ✅ IMPORTANTE
            ->orderByDesc('id');

        if (!empty($data['entity_id'])) {
            $q->where('entity_id', (int) $data['entity_id']);
        }

        $msg = $q->first();

        if (!$msg) {
            return response()->json(['data' => null]);
        }

        $msg->seen = 1;
        $msg->save();

        return response()->json([
            'data' => [
                'id'         => $msg->id,
                'status'     => $msg->status,
                'message'    => $msg->message,
                'entity_id'  => $msg->entity_id,
                'session_id' => $msg->session_id,
                'created_at' => $msg->created_at,
            ]
        ]);
    }

    // GET /api/stripe/return-messages/latest?tipo=ahorro&entity_id=123
    public function latest(Request $request)
    {
        $data = $request->validate([
            'tipo'      => ['required','string','max:50'],
            'entity_id' => ['nullable','integer'],
        ]);

        $user = $request->user();

        $q = StripeReturnMessage::query()
            ->where('tipo', $data['tipo'])
            ->where('seen', 0)
            ->where('user_id', $user->id)     // ✅ IMPORTANTE
            ->orderByDesc('id');

        if (!empty($data['entity_id'])) {
            $q->where('entity_id', (int) $data['entity_id']);
        }

        $msg = $q->first();

        return response()->json([
            'data' => $msg ? [
                'id'         => $msg->id,
                'status'     => $msg->status,
                'message'    => $msg->message,
                'entity_id'  => $msg->entity_id,
                'session_id' => $msg->session_id,
                'created_at' => $msg->created_at,
            ] : null
        ]);
    }

    // POST /api/stripe/return-messages/{id}/seen
    public function markSeen(Request $request, int $id)
    {
        $user = $request->user();

        $msg = StripeReturnMessage::where('id', $id)
            ->where('user_id', $user->id) // ✅ evita que otro “marque” ajenos
            ->firstOrFail();

        $msg->seen = 1;
        $msg->save();

        return response()->json(['ok' => true]);
    }
}
