<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDeposito;
use App\Models\MovimientoCaja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

// NUEVOS USES (como en el servidor)
use App\Models\Cliente;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\NuevoDepositoMail;
use App\Notifications\NuevaSolicitudNotification;

// ✅ NUEVO MAIL CLIENTE
use App\Mail\DepositoSolicitudClienteMail;

class UserDepositoController extends Controller
{
    /**
     * GET /api/depositos
     * Lista los depósitos del cliente autenticado (Sanctum).
     * Filtros: search, status (0|1|2), desde (Y-m-d), hasta (Y-m-d), orden, per_page.
     * orden: monto_asc|monto_desc|fecha_asc|fecha_desc (default: fecha_desc).
     */
    public function index(Request $request)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        $search   = trim((string) $request->input('search', ''));
        $status   = $request->input('status');   // '0','1','2' o null
        $desde    = $request->input('desde');    // Y-m-d
        $hasta    = $request->input('hasta');    // Y-m-d
        $orden    = $request->input('orden', 'fecha_desc');
        $perPage  = (int) $request->input('per_page', 15);
        $perPage  = $perPage > 0 ? min($perPage, 100) : 15;

        $query = UserDeposito::query()
            ->where('id_cliente', $cliente->id)
            ->with(['caja:id_caja,nombre'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('id', $search)
                       ->orWhere('cantidad', 'like', "%{$search}%")
                       ->orWhere('nota', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['0','1','2'], true), fn($q) => $q->where('status', (int) $status))
            ->when($desde, fn($q) => $q->whereDate('fecha_deposito', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha_deposito', '<=', $hasta));

        $query = match ($orden) {
            'monto_asc'  => $query->orderBy('cantidad', 'asc'),
            'monto_desc' => $query->orderBy('cantidad', 'desc'),
            'fecha_asc'  => $query->orderBy('fecha_deposito', 'asc'),
            'fecha_desc' => $query->orderBy('fecha_deposito', 'desc'),
            default      => $query->orderBy('fecha_deposito', 'desc'),
        };

        $paginator = $query->paginate($perPage)->appends($request->query());

        $items = $paginator->getCollection()->map(function (UserDeposito $d) {
            $archivoUrl = null;
            if (
                $d->deposito &&
                Schema::hasColumn('user_depositos', 'deposito') &&
                Storage::disk('public')->exists('depositos/'.$d->deposito)
            ) {
                $archivoUrl = Storage::disk('public')->url('depositos/'.$d->deposito);
            }

            return [
                'id'             => $d->id,
                'id_cliente'     => $d->id_cliente,
                'cantidad'       => (float) $d->cantidad,
                'fecha_deposito' => $d->fecha_deposito,
                'nota'           => $d->nota,
                'status'         => (int) $d->status,
                'id_caja'        => $d->id_caja,
                'caja'           => $d->caja ? [
                    'id_caja' => $d->caja->id_caja,
                    'nombre'  => $d->caja->nombre,
                ] : null,
                'archivo_url'    => $archivoUrl,
                'created_at'     => $d->created_at?->toIso8601String(),
                'updated_at'     => $d->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'ok'   => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/depositos
     * Crea un depósito PENDIENTE (status=0) para el cliente autenticado.
     * Soporta cantidad en formato local y archivo de comprobante.
     * IMPORTANTE: si id_caja es NOT NULL en DB, se usa una caja por defecto (config/app.php → caja_pendientes_id, por defecto 1).
     */
    public function store(Request $request)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        // Compatibilidad legacy: mapear cantidad_deposito -> cantidad si aplica
        if (!$request->has('cantidad') && $request->has('cantidad_deposito')) {
            $request->merge(['cantidad' => $request->input('cantidad_deposito')]);
        }

        $request->validate([
            'fecha_deposito' => ['required', 'date'],
            'cantidad'       => ['required'], // normalizamos abajo
            'nota'           => ['nullable', 'string', 'max:500'],
            'deposito'       => ['nullable','file','mimetypes:image/jpeg,image/png,application/pdf','max:4096'],
        ]);

        // Normalización de cantidad ("1.234,56" | "1,234.56" | "1234,56" | "1234.56")
        $raw  = trim((string) $request->input('cantidad'));
        $norm = str_replace([' ', '$'], '', $raw);

        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $norm)) {
            // Formato europeo: 1.234,56
            $norm = str_replace('.', '', $norm);
            $norm = str_replace(',', '.', $norm);
        } else {
            // US o simple: 1,234.56 / 1234.56
            $norm = str_replace(',', '', $norm);
        }

        $cantidad = (float) $norm;
        if ($cantidad <= 0) {
            return response()->json([
                'ok'    => false,
                'error' => 'La cantidad debe ser mayor a 0.',
            ], 422);
        }

        // Archivo (opcional)
        $filename  = null;
        $publicUrl = null;
        $hasFile   = $request->hasFile('deposito');

        if ($hasFile) {
            $path      = $request->file('deposito')->store('depositos', 'public');
            $filename  = basename($path);
            $publicUrl = asset('storage/'.$path);
        }

        // Usuario sistema/API (para traza) — configurable via APP_API_USER_ID
        $apiUserId = (int) config('app.api_user_id', 1);

        // Caja por defecto si el esquema NOT NULL exige valor (DEPOSITOS_CAJA_PENDIENTE_ID o 1)
        $defaultCajaId = (int) config('app.caja_pendientes_id', 1);
        if ($defaultCajaId <= 0) {
            // fallback: primera caja abierta o cualquiera
            $defaultCajaId = \App\Models\Caja::query()
                ->when(Schema::hasColumn('cajas','estado'), fn($q)=>$q->where('estado','abierta'))
                ->value('id_caja') ?? \App\Models\Caja::query()->value('id_caja');
        }
        if (!$defaultCajaId) {
            return response()->json([
                'ok' => false,
                'error' => 'No hay caja por defecto para registrar el depósito pendiente. Configure app.caja_pendientes_id o cree una caja.',
            ], 422);
        }

        $dep = DB::transaction(function () use ($cliente, $request, $cantidad, $filename, $apiUserId, $defaultCajaId) {
            $payload = [
                'id_cliente'     => $cliente->id,
                'cantidad'       => $cantidad,
                'fecha_deposito' => $request->input('fecha_deposito'),
                'nota'           => $request->input('nota') ?: null,
                'status'         => 0,                // Pendiente
                'id_usuario'     => $apiUserId,       // usuario sistema/API
                'id_caja'        => $defaultCajaId,   // <<-- clave: no enviar NULL
            ];

            if ($filename && Schema::hasColumn('user_depositos', 'deposito')) {
                $payload['deposito'] = $filename;
            }

            $nuevo = UserDeposito::create($payload);

            return $nuevo->load(['caja:id_caja,nombre']);
        });

        // ========= Enviar correo a admin =========
        try {
            Mail::to('admingrowcap@casabarrel.com')
                ->send(new NuevoDepositoMail($dep, $cliente, $publicUrl));
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de nuevo depósito', [
                'deposito_id' => $dep->id ?? null,
                'cliente_id'  => $cliente->id ?? null,
                'ex'          => $e->getMessage(),
            ]);
        }

        // ========= Enviar correo al cliente (solo comprobante) =========
        if ($hasFile) {
            try {
                $clienteEmail = $cliente->email
                    ?? $cliente->correo
                    ?? $cliente->mail
                    ?? null;

                if (!empty($clienteEmail)) {
                    Mail::to($clienteEmail)->send(new DepositoSolicitudClienteMail($dep, $cliente));
                }
            } catch (\Throwable $e) {
                Log::warning('Error enviando correo cliente (depósito comprobante)', [
                    'deposito_id' => $dep->id ?? null,
                    'cliente_id'  => $cliente->id ?? null,
                    'ex'          => $e->getMessage(),
                ]);
            }
        }

        $clienteNombre = trim(sprintf('%s %s', (string)($cliente->nombre ?? ''), (string)($cliente->apellido ?? '')));
        $titulo = 'Nuevo depósito registrado';
        $mensaje = $clienteNombre !== '' ? "Cliente {$clienteNombre} registró un depósito." : 'Se registró un nuevo depósito.';
        $url = route('depositos.show', $dep);

        User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
            $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
        });

        return response()->json([
            'ok'       => true,
            'message'  => 'Depósito creado en estado pendiente.',
            'deposito' => [
                'id'             => $dep->id,
                'id_cliente'     => $dep->id_cliente,
                'cantidad'       => (float) $dep->cantidad,
                'fecha_deposito' => $dep->fecha_deposito,
                'nota'           => $dep->nota,
                'status'         => (int) $dep->status,
                'id_caja'        => $dep->id_caja,
                'caja'           => $dep->caja ? [
                    'id_caja' => $dep->caja->id_caja,
                    'nombre'  => $dep->caja->nombre,
                ] : null,
            ],
            'archivo'  => $publicUrl,
        ], 201);
    }

    /**
     * DELETE /api/depositos/{id}
     * Elimina un depósito del propio cliente SOLO si está Pendiente (status=0)
     * y no tiene movimiento de caja asociado.
     */
    public function destroy(Request $request, int $id)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        $deposito = UserDeposito::where('id', $id)
            ->where('id_cliente', $cliente->id)
            ->first();

        if (!$deposito) {
            return response()->json([
                'ok'    => false,
                'error' => 'Depósito no encontrado.',
            ], 404);
        }

        if ((int) $deposito->status !== 0) {
            return response()->json([
                'ok'    => false,
                'error' => 'Solo puedes eliminar depósitos en estado pendiente.',
            ], 409);
        }

        $tieneMovimiento = MovimientoCaja::where('origen_id', $deposito->id)
            ->where('tipo_mov', 'Ingreso')
            ->exists();

        if ($tieneMovimiento) {
            return response()->json([
                'ok'    => false,
                'error' => 'El depósito ya está vinculado a movimientos de caja y no puede eliminarse.',
            ], 409);
        }

        // Borrar comprobante si existe
        if ($deposito->deposito && Schema::hasColumn('user_depositos', 'deposito')) {
            $path = 'depositos/'.$deposito->deposito;
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $deposito->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Depósito eliminado.',
        ], 200);
    }
}
