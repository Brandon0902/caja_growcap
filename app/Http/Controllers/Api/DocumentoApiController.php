<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Documento;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentoApiController extends Controller
{
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

    /** Devuelve el registro de documentos del cliente autenticado (o null). */
    public function show(Request $request)
    {
        $cliente = $this->cliente($request);
        $doc = Documento::where('id_cliente', $cliente->id)->first();

        return response()->json([
            'ok'  => true,
            'doc' => $doc ? $this->toApiPayload($doc) : null,
        ]);
    }

    /** Sube/actualiza documentos del cliente autenticado. */
    public function store(Request $request)
    {
        $cliente = $this->cliente($request);

        $data = $request->validate([
            'documento_01'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_02'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_02_02' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_03'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_04'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_05'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'fecha'           => 'nullable|date',
        ]);

        Log::debug('API Documento store INICIO', ['id_cliente' => $cliente->id]);

        $doc = Documento::firstOrNew(
            ['id_cliente' => $cliente->id],
            ['id_usuario' => optional($request->user())->id]
        );

        foreach (['documento_01','documento_02','documento_02_02','documento_03','documento_04','documento_05'] as $field) {
            if ($request->hasFile($field)) {
                if ($doc->$field) {
                    Storage::disk('documentos')->delete($doc->$field);
                    Log::debug("API Documento borrado anterior $field", ['old' => $doc->$field]);
                }
                $path = $request->file($field)->store('', 'documentos');
                $doc->$field = $path;
                Log::debug("API Documento guardado $field", ['path' => $path]);
            }
        }

        if (!empty($data['fecha'])) {
            $doc->fecha = $data['fecha'];
        }

        $doc->id_cliente = $cliente->id;
        $doc->id_usuario = optional($request->user())->id;
        $doc->save();

        Log::debug('API Documento store FIN', ['doc_id' => $doc->id ?? null]);

        return response()->json([
            'ok'  => true,
            'msg' => 'Documentos subidos correctamente.',
            'doc' => $this->toApiPayload($doc),
        ]);
    }

    /** Elimina un campo-documento del cliente autenticado. */
    public function destroyField(Request $request, string $field)
    {
        $cliente = $this->cliente($request);

        $allowed = [
            'documento_01','documento_02','documento_02_02',
            'documento_03','documento_04','documento_05',
        ];
        if (!in_array($field, $allowed, true)) {
            throw ValidationException::withMessages(['field' => 'Campo no permitido.']);
        }

        $doc = Documento::where('id_cliente', $cliente->id)->first();
        if (!$doc || !$doc->$field) {
            return response()->json(['ok' => true, 'msg' => 'Nada que eliminar.']);
        }

        Storage::disk('documentos')->delete($doc->$field);
        $doc->$field = null;
        $doc->save();

        return response()->json([
            'ok'  => true,
            'msg' => 'Documento eliminado correctamente.',
            'doc' => $this->toApiPayload($doc),
        ]);
    }

    /** Stream inline del archivo para el cliente autenticado. */
    public function view(Request $request, string $field)
    {
        $cliente = $this->cliente($request);

        $allowed = [
            'documento_01','documento_02','documento_02_02',
            'documento_03','documento_04','documento_05',
        ];
        if (!in_array($field, $allowed, true)) {
            abort(404);
        }

        $doc = Documento::where('id_cliente', $cliente->id)->first();
        abort_unless($doc && $doc->{$field}, 404);

        return Storage::disk('documentos')->response($doc->{$field});
    }

    /** Construye payload de API. */
    private function toApiPayload(Documento $doc): array
    {
        $fields = [
            'documento_01','documento_02','documento_02_02',
            'documento_03','documento_04','documento_05',
        ];

        $out = [
            'id_cliente' => $doc->id_cliente,
            'fecha'      => optional($doc->fecha)->format('Y-m-d'),
        ];

        foreach ($fields as $f) {
            $out[$f] = $doc->$f ? [
                'path'   => $doc->$f,
                'exists' => Storage::disk('documentos')->exists($doc->$f),
            ] : null;
        }

        return $out;
    }
}
