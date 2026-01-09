<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PreguntasApiController extends Controller
{
    /** GET /api/cliente/preguntas */
    public function index(Request $request)
    {
        $cliente = auth()->user();
        if (!$cliente) {
            Log::warning('Preguntas@index: usuario no autenticado', [
                'ip'    => $request->ip(),
                'route' => $request->path(),
            ]);
            abort(401);
        }

        $trace   = (string) Str::uuid();
        $perPage = (int) ($request->integer('per_page') ?: 15);
        $catIn   = $request->query('categoria'); // opcional
        $qSearch = $request->query('q');         // opcional

        Log::info('Preguntas@index: IN', [
            'trace'   => $trace,
            'user_id' => $cliente->id,
            'params'  => [
                'per_page'  => $perPage,
                'categoria' => $catIn,
                'q'         => $qSearch,
                'page'      => $request->integer('page') ?: 1,
            ],
        ]);

        try {
            $tabla = (new Pregunta)->getTable();

            // Columnas disponibles dinámicamente
            $cols = ['id', 'pregunta', 'respuesta', 'status'];
            if (Schema::hasColumn($tabla, 'categoria')) $cols[] = 'categoria';
            if (Schema::hasColumn($tabla, 'img'))       $cols[] = 'img';
            if (Schema::hasColumn($tabla, 'fecha'))     $cols[] = 'fecha';

            $q = Pregunta::query()
                ->select($cols)
                ->where('status', 1);

            if (!empty($catIn) && Schema::hasColumn($tabla, 'categoria')) {
                $q->where('categoria', $catIn);
            }

            if (!empty($qSearch)) {
                $q->where(function ($w) use ($qSearch) {
                    $w->where('pregunta', 'like', "%{$qSearch}%")
                      ->orWhere('respuesta', 'like', "%{$qSearch}%");
                });
            }

            // Ordenar por fecha si existe; si no, por id desc
            if (Schema::hasColumn($tabla, 'fecha')) {
                $q->orderByDesc('fecha')->orderByDesc('id');
            } else {
                $q->orderByDesc('id');
            }

            $paginator = $q->paginate($perPage);

            $items = collect($paginator->items())->map(function (Pregunta $p) use ($trace) {
                $imgUrl = null;
                if (!empty($p->img)) {
                    try {
                        $imgUrl = Storage::disk('public')->url($p->img);
                    } catch (Throwable $e) {
                        Log::warning('Preguntas@index: error generando URL de imagen', [
                            'trace'       => $trace,
                            'pregunta_id' => $p->id,
                            'img'         => $p->img,
                            'ex'          => $e->getMessage(),
                        ]);
                    }
                }

                return [
                    'id'        => $p->id,
                    'pregunta'  => $p->pregunta,
                    'respuesta' => $p->respuesta,
                    'categoria' => $p->categoria ?? null,
                    'img_url'   => $imgUrl,
                    'fecha'     => isset($p->fecha) ? (string) $p->fecha : null,
                    'status'    => (int) $p->status,
                ];
            });

            $out = [
                'fecha'      => now()->toDateString(),
                'page'       => $paginator->currentPage(),
                'per_page'   => $paginator->perPage(),
                'total'      => $paginator->total(),
                'last_page'  => $paginator->lastPage(),
                'preguntas'  => $items,
            ];

            Log::info('Preguntas@index: OK', [
                'trace'   => $trace,
                'user_id' => $cliente->id,
                'total'   => $out['total'],
                'page'    => $out['page'],
            ]);

            return response()->json($out);

        } catch (Throwable $e) {
            Log::error('Preguntas@index: EXCEPTION', [
                'trace'   => $trace,
                'user_id' => $cliente->id,
                'ex'      => $e,
            ]);

            return response()->json([
                'message' => 'Error interno al obtener preguntas.',
                'trace'   => $trace,
            ], 500);
        }
    }

    /** GET /api/cliente/preguntas/{id} */
    public function show(int $id, Request $request)
    {
        $cliente = auth()->user();
        if (!$cliente) {
            Log::warning('Preguntas@show: usuario no autenticado', [
                'ip' => $request->ip(),
                'id' => $id,
            ]);
            abort(401);
        }

        $trace = (string) Str::uuid();
        Log::info('Preguntas@show: IN', [
            'trace'   => $trace,
            'user_id' => $cliente->id,
            'id'      => $id,
        ]);

        try {
            $p = Pregunta::where('status', 1)
                ->where('id', $id)
                ->first();

            if (!$p) {
                Log::notice('Preguntas@show: no encontrada', [
                    'trace'   => $trace,
                    'user_id' => $cliente->id,
                    'id'      => $id,
                ]);

                return response()->json([
                    'message' => 'Pregunta no disponible.',
                    'trace'   => $trace,
                ], 404);
            }

            $imgUrl = null;
            if (!empty($p->img)) {
                try {
                    $imgUrl = Storage::disk('public')->url($p->img);
                } catch (Throwable $e) {
                    Log::warning('Preguntas@show: error generando URL de imagen', [
                        'trace'       => $trace,
                        'pregunta_id' => $p->id,
                        'img'         => $p->img,
                        'ex'          => $e->getMessage(),
                    ]);
                }
            }

            $out = [
                'id'        => $p->id,
                'pregunta'  => $p->pregunta,
                'respuesta' => $p->respuesta,
                'categoria' => $p->categoria ?? null,
                'img_url'   => $imgUrl,
                'fecha'     => isset($p->fecha) ? (string) $p->fecha : null,
                'status'    => (int) $p->status,
            ];

            Log::info('Preguntas@show: OK', [
                'trace'   => $trace,
                'user_id' => $cliente->id,
                'id'      => $p->id,
            ]);

            return response()->json($out);

        } catch (Throwable $e) {
            Log::error('Preguntas@show: EXCEPTION', [
                'trace'   => $trace,
                'user_id' => $cliente->id,
                'id'      => $id,
                'ex'      => $e,
            ]);

            return response()->json([
                'message' => 'Error interno al cargar la pregunta.',
                'trace'   => $trace,
            ], 500);
        }
    }
}
