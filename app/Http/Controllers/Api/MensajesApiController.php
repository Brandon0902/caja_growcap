<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mensaje;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MensajesApiController extends Controller
{
    /** GET /api/cliente/mensajes */
    public function index(Request $request)
    {
        $cliente = auth()->user();
        if (!$cliente) {
            Log::warning('Mensajes@index: usuario no autenticado', [
                'ip' => $request->ip(),
                'route' => $request->path(),
            ]);
            abort(401);
        }

        $trace = (string) Str::uuid();
        $perPage = (int) ($request->integer('per_page') ?: 15);
        $tipoIn  = $request->has('tipo') ? $request->integer('tipo') : null;

        Log::info('Mensajes@index: IN', [
            'trace'   => $trace,
            'user_id' => $cliente->id,
            'params'  => [
                'per_page' => $perPage,
                'tipo'     => $tipoIn,
                'page'     => $request->integer('page') ?: 1,
            ],
        ]);

        try {
            $q = Mensaje::query()
                ->where('status', 1)
                ->where(function ($w) use ($cliente) {
                    $w->where('id_cliente', $cliente->id)
                      ->orWhereNull('id_cliente');
                });

            if (!is_null($tipoIn)) {
                $q->where('tipo', (int) $tipoIn);
            }

            $q->orderByDesc('fecha')->orderByDesc('id');

            $paginator = $q->paginate($perPage);

            $items = collect($paginator->items())->map(function (Mensaje $m) {
                $imgUrl = null;
                if ($m->img) {
                    try {
                        $imgUrl = Storage::disk('mensajes')->url($m->img);
                    } catch (Throwable $e) {
                        Log::warning('Mensajes@index: error generando URL de imagen', [
                            'mensaje_id' => $m->id,
                            'img'        => $m->img,
                            'ex'         => $e->getMessage(),
                        ]);
                    }
                }

                return [
                    'id'           => $m->id,
                    'tipo'         => (int) $m->tipo,
                    'cliente_id'   => $m->id_cliente,
                    'asunto'       => $m->nombre ?? null,
                    'cuerpo'       => $m->descripcion ?? null,
                    'introduccion' => $m->introduccion ?? null,
                    'url'          => $m->url ?? null,
                    'img_url'      => $imgUrl,
                    'fecha'        => $m->fecha ? (string)$m->fecha : null,
                    'fecha_edit'   => $m->fecha_edit ? (string)$m->fecha_edit : null,
                    'status'       => (int) $m->status,
                ];
            });

            $out = [
                'fecha'      => now()->toDateString(),
                'page'       => $paginator->currentPage(),
                'per_page'   => $paginator->perPage(),
                'total'      => $paginator->total(),
                'last_page'  => $paginator->lastPage(),
                'mensajes'   => $items,
            ];

            Log::info('Mensajes@index: OK', [
                'trace'   => $trace,
                'user_id' => $cliente->id,
                'total'   => $out['total'],
                'page'    => $out['page'],
            ]);

            return response()->json($out);

        } catch (Throwable $e) {
            Log::error('Mensajes@index: EXCEPTION', [
                'trace'   => $trace,
                'user_id' => $cliente->id,
                'ex'      => $e,
            ]);

            return response()->json([
                'message' => 'Error interno al obtener mensajes.',
                'trace'   => $trace,
            ], 500);
        }
    }

    /** GET /api/cliente/mensajes/{id} */
    public function show(int $id, Request $request)
    {
        $cliente = auth()->user();
        if (!$cliente) {
            Log::warning('Mensajes@show: usuario no autenticado', [
                'ip' => $request->ip(),
                'id' => $id,
            ]);
            abort(401);
        }

        $trace = (string) Str::uuid();
        Log::info('Mensajes@show: IN', [
            'trace'   => $trace,
            'user_id' => $cliente->id,
            'id'      => $id,
        ]);

        try {
            $m = Mensaje::where('status', 1)
                ->where('id', $id)
                ->where(function ($w2) use ($cliente) {
                    $w2->where('id_cliente', $cliente->id)
                       ->orWhereNull('id_cliente');
                })
                ->first();

            if (!$m) {
                Log::notice('Mensajes@show: no encontrado o sin permiso', [
                    'trace'   => $trace,
                    'user_id' => $cliente->id,
                    'id'      => $id,
                ]);
                return response()->json([
                    'message' => 'Mensaje no disponible o no autorizado.',
                    'trace'   => $trace,
                ], 404);
            }

            $imgUrl = null;
            if ($m->img) {
                try {
                    $imgUrl = Storage::disk('mensajes')->url($m->img);
                } catch (Throwable $e) {
                    Log::warning('Mensajes@show: error generando URL de imagen', [
                        'trace'      => $trace,
                        'mensaje_id' => $m->id,
                        'img'        => $m->img,
                        'ex'         => $e->getMessage(),
                    ]);
                }
            }

            $out = [
                'id'           => $m->id,
                'tipo'         => (int) $m->tipo,
                'cliente_id'   => $m->id_cliente,
                'asunto'       => $m->nombre ?? null,
                'cuerpo'       => $m->descripcion ?? null,
                'introduccion' => $m->introduccion ?? null,
                'url'          => $m->url ?? null,
                'img_url'      => $imgUrl,
                'fecha'        => $m->fecha ? (string)$m->fecha : null,
                'fecha_edit'   => $m->fecha_edit ? (string)$m->fecha_edit : null,
                'status'       => (int) $m->status,
            ];

            Log::info('Mensajes@show: OK', [
                'trace'   => $trace,
                'user_id' => $cliente->id,
                'id'      => $m->id,
            ]);

            return response()->json($out);

        } catch (ModelNotFoundException $e) {
            Log::notice('Mensajes@show: ModelNotFoundException', [
                'trace'   => $trace,
                'user_id' => $cliente->id,
                'id'      => $id,
            ]);
            return response()->json([
                'message' => 'Mensaje no encontrado.',
                'trace'   => $trace,
            ], 404);

        } catch (Throwable $e) {
            Log::error('Mensajes@show: EXCEPTION', [
                'trace'   => $trace,
                'user_id' => $cliente->id,
                'id'      => $id,
                'ex'      => $e,
            ]);
            return response()->json([
                'message' => 'Error interno al cargar el mensaje.',
                'trace'   => $trace,
            ], 500);
        }
    }
}
