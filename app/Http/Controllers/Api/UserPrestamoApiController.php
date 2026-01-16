<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\MovimientoCaja;
use App\Models\Prestamo;           // Planes: PK id_prestamo
use App\Models\UserPrestamo;       // Préstamos del cliente
use App\Models\UserLaboral;        // salario_mensual (último registro)
use App\Models\UserAhorro;         // saldo_fecha (activos !=0)
use App\Models\UserInversion;      // capital_actual (activos)
use App\Models\UserAbono;          // para saldo_restante último por préstamo
use App\Models\User;
use App\Notifications\NuevaSolicitudNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

// NUEVOS USES PARA CORREO
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\NuevoPrestamoMail;

class UserPrestamoApiController extends Controller
{
    /* =========================
     * Reglas / Constantes
     * ========================= */
    private const MONTO_MINIMO           = 500.00;     // MXN
    private const AVAL_CLIENTE_STATUS_OK = 1;          // “activo” como en legacy
    private const AVAL_DOCS_PATH         = 'documentos'; // carpeta en disco 'public'

    /** Estados que consideramos “en curso” para bloquear nuevo préstamo por 50% */
    private function activeLoanStatuses(): array
    {
        // En tu legacy usabas 5 = “Depositado”. En el Laravel actual manejamos 1=Autorizado, 5=Pagado, 6=Terminado.
        // Para ser conservadores: activo si tiene fecha_inicio y status ∈ {1,5}??? (tu legacy decía 5).
        // Dejamos {5} (o ajusta si tu negocio necesita incluir 1)
        return [5];
    }

    /* =========================
     * Helpers de negocio
     * ========================= */
     
     
    /** Obtiene el último registro laboral del cliente (si existe) */
    private function getLatestLaboral(int $clienteId): ?UserLaboral
    {
        $q = UserLaboral::where('id_cliente', $clienteId);
    
        $table = (new UserLaboral())->getTable();
    
        if (Schema::hasColumn($table, 'fecha_registro')) {
            $q->orderByDesc('fecha_registro');
        } elseif (Schema::hasColumn($table, 'created_at')) {
            $q->orderByDesc('created_at');
        } else {
            $q->orderByDesc('id');
        }
    
        return $q->first();
    }
    
    /** Si no hay registro laboral o el salario viene vacío/0 */
    private function laboralMissingOrEmpty(int $clienteId): bool
    {
        $lab = $this->getLatestLaboral($clienteId);
        if (!$lab) return true;
    
        // intenta leer salario_mensual o salario (según tu tabla)
        $sal = (float) ($lab->salario_mensual ?? $lab->salario ?? 0);
    
        return $sal <= 0;
    }


    /** Último salario registrado o 0 */
    private function getMonthlySalary(int $clienteId): float
    {
        $q = UserLaboral::where('id_cliente', $clienteId)
            ->orderByDesc('fecha_registro')
            ->value('salario_mensual');

        return (float) ($q ?? 0);
    }

    /** Suma de ahorro activo (status != 0) en saldo_fecha */
    private function getActiveSavingsBalance(int $clienteId): float
    {
        return (float) (UserAhorro::where('id_cliente', $clienteId)
            ->where('status', '!=', 0)
            ->sum('saldo_fecha') ?? 0);
    }

    /** Inversión “acumulada” (usamos capital_actual si existe; si no, monto_pagado) en inversiones activas */
    private function getInvestmentsBalance(int $clienteId): float
    {
        $col = Schema::hasColumn('user_inversion', 'capital_actual') ? 'capital_actual' :
               (Schema::hasColumn('user_inversion', 'monto_pagado') ? 'monto_pagado' : null);

        if (!$col) return 0.0;

        return (float) (UserInversion::where('id_cliente', $clienteId)
            ->where('status', 1)
            ->sum($col) ?? 0);
    }

    /** Suma de saldo_restante (último abono) de préstamos activos del cliente */
    private function getActiveLoansBalance(int $clienteId): float
    {
        $ids = UserPrestamo::where('id_cliente', $clienteId)
            ->whereIn('status', $this->activeLoanStatuses())
            ->pluck('id');

        if ($ids->isEmpty()) return 0.0;

        $total = 0.0;
        foreach ($ids as $pid) {
            $saldo = UserAbono::where('id_prestamo', $pid)
                ->orderByDesc('id')
                ->value('saldo_restante');
            if ($saldo === null) {
                $saldo = UserPrestamo::where('id', $pid)->value('cantidad');
            }
            $total += (float) $saldo;
        }
        return $total;
    }

    /** Fecha de primera inversión del cliente (para antigüedad) */
    private function getFirstInvestmentDate(int $clienteId): ?Carbon
    {
        $f = UserInversion::where('id_cliente', $clienteId)
            ->orderBy('fecha_inicio', 'asc')
            ->value('fecha_inicio');

        return $f ? Carbon::parse($f) : null;
    }

    /** Meses entre la fecha dada y hoy (0 si null) */
    private function monthsAgo(?Carbon $date): int
    {
        if (!$date) return 0;
        $now = Carbon::now()->startOfDay();
        $d   = $date->copy()->startOfDay();
        return $d->diffInMonths($now);
    }

    /** Normaliza monto con comas/puntos y símbolos */
    private function normalizeAmount(string $raw): float
    {
        $norm = str_replace([' ', '$'], '', trim($raw));
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $norm)) {
            $norm = str_replace('.', '', $norm);
            $norm = str_replace(',', '.', $norm);
        } else {
            $norm = str_replace(',', '', $norm);
        }
        return (float) $norm;
    }

    /** Guarda un archivo del request en disco público/documentos; regresa ruta o null */
    private function saveAvalFile(Request $request, string $field): ?string
    {
        if (!$request->hasFile($field)) return null;
        $file = $request->file($field);
        if (!$file->isValid()) return null;

        // (Si quieres, podrías validar aquí, pero normalmente ya está validado en $request->validate)
        $path = $file->store(self::AVAL_DOCS_PATH, ['disk' => 'public']);
        return $path ?: null;
    }

    /* =========================
     * GET /api/prestamos
     * ========================= */
    public function index(Request $request)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        $search  = trim((string) $request->input('search', ''));
        $status  = $request->input('status');               // '1'..'6' o null
        $desde   = $request->input('desde');                // Y-m-d
        $hasta   = $request->input('hasta');                // Y-m-d
        $orden   = $request->input('orden', 'fecha_desc');  // fecha_asc|fecha_desc|monto_asc|monto_desc
        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $query = UserPrestamo::query()
            ->select('user_prestamos.*')
            ->leftJoin('prestamos', 'prestamos.id_prestamo', '=', 'user_prestamos.id_activo')
            ->with([
                'caja:id_caja,nombre',
                'plan' => function ($q) {
                    $q->select(
                        'id_prestamo',
                        'periodo',
                        'semanas',
                        'interes',
                        DB::raw('monto_minimo as monto_min'),
                        DB::raw('monto_maximo as monto_max'),
                        'status'
                    );
                },
            ])
            ->where('user_prestamos.id_cliente', $cliente->id)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('user_prestamos.id', $search)
                       ->orWhere('user_prestamos.cantidad', 'like', "%{$search}%")
                       ->orWhereHas('plan', function ($qp) use ($search) {
                           $qp->where('periodo', 'like', "%{$search}%");
                       });
                });
            })
            ->when(in_array($status, ['1','2','3','4','5','6'], true), fn($q) => $q->where('user_prestamos.status', (int) $status))
            ->when($desde, fn($q) => $q->whereDate('user_prestamos.fecha_inicio', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('user_prestamos.fecha_inicio', '<=', $hasta));

        $query = match ($orden) {
            'monto_asc'  => $query->orderBy('user_prestamos.cantidad', 'asc'),
            'monto_desc' => $query->orderBy('user_prestamos.cantidad', 'desc'),
            'fecha_asc'  => $query->orderBy('user_prestamos.fecha_inicio', 'asc'),
            'fecha_desc' => $query->orderBy('user_prestamos.fecha_inicio', 'desc'),
            default      => $query->orderBy('user_prestamos.fecha_inicio', 'desc'),
        };

        $paginator = $query->paginate($perPage)->appends($request->query());

        $items = $paginator->getCollection()->map(function (UserPrestamo $p) {
            return [
                'id'               => $p->id,
                'id_cliente'       => $p->id_cliente,
                'id_activo'        => $p->id_activo,
                'cantidad'         => (float) $p->cantidad,
                'interes'          => $p->interes !== null ? (float) $p->interes : null,
                'interes_generado' => $p->interes_generado !== null ? (float) $p->interes_generado : null,
                'semanas'          => $p->semanas !== null ? (int) $p->semanas : null,
                'status'           => (int) $p->status,
                'fecha_solicitud'  => $p->fecha_solicitud,
                'fecha_inicio'     => $p->fecha_inicio,
                'id_caja'          => $p->id_caja,
                'caja'             => $p->caja ? [
                    'id_caja' => $p->caja->id_caja,
                    'nombre'  => $p->caja->nombre,
                ] : null,
                'plan'             => $p->plan ? [
                    'id'        => $p->plan->id_prestamo,
                    'periodo'   => $p->plan->periodo,
                    'semanas'   => $p->plan->semanas,
                    'interes'   => $p->plan->interes,
                    'monto_min' => $p->plan->monto_min ?? null,
                    'monto_max' => $p->plan->monto_max ?? null,
                    'status'    => (int) ($p->plan->status ?? 0),
                ] : null,
                'created_at'       => $p->created_at?->toIso8601String(),
                'updated_at'       => $p->updated_at?->toIso8601String(),
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

    /* =========================
     * POST /api/prestamos
     * ========================= */
    public function store(Request $request)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();
    
        // Compat: permitir 'monto'
        if (!$request->has('cantidad') && $request->has('monto')) {
            $request->merge(['cantidad' => $request->input('monto')]);
        }
    
        // Validación básica + archivos del aval
        $request->validate([
            'id_activo'  => ['required','integer','exists:prestamos,id_prestamo'],
            'cantidad'   => ['required','string','max:50'],
            'codigo_aval'=> ['nullable','string','max:50'],
            'doc_solicitud_aval'        => ['nullable','file','mimetypes:application/pdf,image/jpeg,image/png','max:5120'],
            'doc_comprobante_domicilio' => ['nullable','file','mimetypes:application/pdf,image/jpeg,image/png','max:5120'],
            'doc_ine_frente'            => ['nullable','file','mimetypes:application/pdf,image/jpeg,image/png','max:5120'],
            'doc_ine_reverso'           => ['nullable','file','mimetypes:application/pdf,image/jpeg,image/png','max:5120'],
        ]);
    
        // Normalizar cantidad
        $monto = $this->normalizeAmount((string) $request->input('cantidad'));
        if ($monto < self::MONTO_MINIMO) {
            return response()->json(['ok'=>false,'error'=>'La cantidad mínima es $'.number_format(self::MONTO_MINIMO,2)], 422);
        }
    
        // Plan y revalidación de ACTIVO
        /** @var Prestamo $plan */
        $plan = Prestamo::where('id_prestamo', (int) $request->integer('id_activo'))->firstOrFail();
        if ((string)($plan->status ?? '') !== '1') {
            return response()->json(['ok'=>false,'error'=>'El plan seleccionado no está activo.'], 422);
        }
    
        $tasa       = (float) ($plan->interes ?? 0);
        $semanas    = (int)   ($plan->semanas ?? 0);
        $periodo    = $plan->periodo;
        $montoMax   = (float) ($plan->monto_maximo ?? $plan->monto_max ?? 0);
        $antigMin   = (int)   ($plan->antiguedad ?? 0); // si existe
    
        // Regla 50% pagado (si tiene préstamo activo en curso)
        $prev = UserPrestamo::where('id_cliente', $cliente->id)
            ->whereIn('status', $this->activeLoanStatuses())
            ->orderByDesc('id')
            ->first();
    
        if ($prev) {
            $pagadoRatio = null;
    
            if (Schema::hasColumn('user_prestamos', 'abonos_echos') && $prev->abonos_echos !== null && $prev->cantidad > 0) {
                $pagadoRatio = (float)$prev->abonos_echos / (float)$prev->cantidad;
            } else {
                $saldoRest = UserAbono::where('id_prestamo', $prev->id)->orderByDesc('id')->value('saldo_restante');
                if ($saldoRest === null) $saldoRest = $prev->cantidad;
                $pagadoRatio = $prev->cantidad > 0 ? (1.0 - ($saldoRest / $prev->cantidad)) : 0.0;
            }
    
            if ($pagadoRatio < 0.5) {
                return response()->json(['ok'=>false,'error'=>'Debes pagar al menos el 50% de tu préstamo actual.'], 422);
            }
        }
    
        // ✅ NUEVO: si no hay datos laborales (o salario <= 0), mandar mensaje claro
        // (lo hacemos aquí antes de calcular tope/capacidad)
        try {
            $labQ = UserLaboral::where('id_cliente', (int)$cliente->id);
    
            $tblLab = (new UserLaboral())->getTable();
            if (Schema::hasColumn($tblLab, 'fecha_registro')) {
                $labQ->orderByDesc('fecha_registro');
            } elseif (Schema::hasColumn($tblLab, 'created_at')) {
                $labQ->orderByDesc('created_at');
            } else {
                $labQ->orderByDesc('id');
            }
    
            $lab = $labQ->first();
    
            $salarioLab = 0.0;
            if ($lab) {
                $salarioLab = (float) ($lab->salario_mensual ?? $lab->salario ?? 0);
            }
    
            if (!$lab || $salarioLab <= 0) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'Para solicitar un préstamo primero debes completar tus Datos Laborales (Mis Datos → Datos laborales).',
                ], 422);
            }
        } catch (\Throwable $e) {
            // Si por alguna razón falla la lectura laboral, igual damos el mensaje (mejor UX)
            return response()->json([
                'ok'    => false,
                'error' => 'Para solicitar un préstamo primero debes completar tus Datos Laborales (Mis Datos → Datos laborales).',
            ], 422);
        }
    
        // Tope por capacidad
        $salario   = $this->getMonthlySalary($cliente->id);
        $ahorro    = $this->getActiveSavingsBalance($cliente->id);
        $inversion = $this->getInvestmentsBalance($cliente->id);
        $capacidad = max($salario, $ahorro, $inversion);
        $saldoPend = $this->getActiveLoansBalance($cliente->id);
        $baseTope  = max(0, $capacidad - $saldoPend);
        $tope      = $montoMax > 0 ? min($montoMax, $baseTope) : $baseTope;
    
        if ($monto > $tope) {
            $mot = $baseTope <= 0 ? 'capacidad disponible $0.00' : ('capacidad disponible $'.number_format($baseTope,2));
            return response()->json(['ok'=>false,'error'=>'El monto solicitado excede tu tope ('.number_format($tope,2)."). {$mot}"], 422);
        }
    
        // Aval: código O documentos (los 4)
        $codigoAval = trim((string) $request->input('codigo_aval'));
        $fSol  = $request->file('doc_solicitud_aval');
        $fDom  = $request->file('doc_comprobante_domicilio');
        $fFte  = $request->file('doc_ine_frente');
        $fRev  = $request->file('doc_ine_reverso');
    
        $usandoDocs   = ($fSol && $fDom && $fFte && $fRev);
        $usandoCodigo = ($codigoAval !== '');
    
        if (!$usandoCodigo && !$usandoDocs) {
            return response()->json(['ok'=>false,'error'=>'Debes ingresar código de aval o subir los 4 documentos requeridos.'], 422);
        }
    
        // Buscar aval por código (en tabla clientes)
        $avalId = null;
        if ($usandoCodigo) {
            $avalId = Cliente::where('codigo_cliente', $codigoAval)
                ->where('status', self::AVAL_CLIENTE_STATUS_OK)
                ->value('id');
    
            if (!$avalId) {
                return response()->json(['ok'=>false,'error'=>'Código de aval inválido.'], 422);
            }
        }
    
        // Caja por defecto si existe la columna
        $defaultCajaId = null;
        if (Schema::hasColumn('user_prestamos', 'id_caja')) {
            $defaultCajaId = (int) config('app.caja_pendientes_id', 1);
            if ($defaultCajaId <= 0) {
                $defaultCajaId = Caja::query()
                    ->when(Schema::hasColumn('cajas','estado'), fn($q)=>$q->where('estado','abierta'))
                    ->value('id_caja') ?? Caja::query()->value('id_caja');
            }
            if (!$defaultCajaId) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No hay caja por defecto para registrar el préstamo. Configure app.caja_pendientes_id o cree una caja.',
                ], 422);
            }
        }
    
        // Cálculo de interés generado inmediato
        $interesGen = round($monto * $tasa / 100, 2);
    
        // Subida de archivos (si ese camino se eligió)
        $docSolicitud = $usandoDocs ? $request->file('doc_solicitud_aval')->store(self::AVAL_DOCS_PATH, 'public') : null;
        $docDom       = $usandoDocs ? $request->file('doc_comprobante_domicilio')->store(self::AVAL_DOCS_PATH, 'public') : null;
        $docIneF      = $usandoDocs ? $request->file('doc_ine_frente')->store(self::AVAL_DOCS_PATH, 'public') : null;
        $docIneR      = $usandoDocs ? $request->file('doc_ine_reverso')->store(self::AVAL_DOCS_PATH, 'public') : null;
    
        $prestamo = DB::transaction(function () use (
            $cliente, $request, $monto, $tasa, $semanas, $periodo, $interesGen, $defaultCajaId,
            $avalId, $usandoCodigo, $docSolicitud, $docDom, $docIneF, $docIneR
        ) {
            $payload = [
                'id_cliente'       => $cliente->id,
                'id_activo'        => (int) $request->input('id_activo'),
                'fecha_solicitud'  => now(),
                'fecha_inicio'     => null,              // se establecerá al autorizar
                'cantidad'         => $monto,
                'tipo_prestamo'    => $periodo,
                'semanas'          => $semanas ?: null,
                'interes'          => $tasa,
                'interes_generado' => $interesGen,
                'status'           => 2,                 // Pendiente
                'id_caja'          => $defaultCajaId,
            ];
    
            // Aval por código
            if ($avalId) {
                $payload['aval_id']          = (int) $avalId;
                $payload['aval_status']      = 0;      // Pendiente
                $payload['aval_notified_at'] = now();
                $payload['codigo_aval']      = $request->input('codigo_aval');
            }
    
            // Aval por documentos
            if ($docSolicitud || $docDom || $docIneF || $docIneR) {
                $payload['doc_solicitud_aval']        = $docSolicitud;
                $payload['doc_comprobante_domicilio'] = $docDom;
                $payload['doc_ine_frente']            = $docIneF;
                $payload['doc_ine_reverso']           = $docIneR;
                $payload['aval_status']               = $avalId ? 0 : 2; // 2 = sin aval por código
            }
    
            /** @var UserPrestamo $nuevo */
            $nuevo = UserPrestamo::create($payload);
    
            return $nuevo->load([
                'caja:id_caja,nombre',
                'plan' => function ($q) {
                    $q->select(
                        'id_prestamo','periodo','semanas','interes',
                        DB::raw('monto_minimo as monto_min'),
                        DB::raw('monto_maximo as monto_max'),
                        'status'
                    );
                },
            ]);
        });
    
        // ========= Enviar correo a admin =========
        try {
            $docsUrls = null;
            if ($docSolicitud || $docDom || $docIneF || $docIneR) {
                $docsUrls = [
                    'solicitud'  => $docSolicitud ? asset('storage/'.$docSolicitud) : null,
                    'domicilio'  => $docDom       ? asset('storage/'.$docDom)       : null,
                    'ine_frente' => $docIneF      ? asset('storage/'.$docIneF)      : null,
                    'ine_reverso'=> $docIneR      ? asset('storage/'.$docIneR)      : null,
                ];
            }
    
            $adminEmail = trim((string) config('services.admin.email'));
            if ($adminEmail !== '') {
                Mail::to($adminEmail)
                    ->send(new NuevoPrestamoMail($prestamo, $cliente, $docsUrls));
            }
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de nueva solicitud de préstamo', [
                'prestamo_id' => $prestamo->id ?? null,
                'cliente_id'  => $cliente->id ?? null,
                'ex'          => $e->getMessage(),
            ]);
            // No rompemos la respuesta si el correo falla
        }
        // =========================================

        $clienteNombre = trim(sprintf('%s %s', (string)($cliente->nombre ?? ''), (string)($cliente->apellido ?? '')));
        $titulo = 'Nueva solicitud de préstamo';
        $mensaje = $clienteNombre !== '' ? "Cliente {$clienteNombre} creó una solicitud." : 'Se creó una nueva solicitud.';
        $url = route('user_prestamos.show', $prestamo);

        User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
            $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
        });

        return response()->json([
            'ok'      => true,
            'message' => 'Solicitud de préstamo creada en estado pendiente.',
            'prestamo' => [
                'id'               => $prestamo->id,
                'id_cliente'       => $prestamo->id_cliente,
                'id_activo'        => $prestamo->id_activo,
                'cantidad'         => (float) $prestamo->cantidad,
                'interes'          => (float) $prestamo->interes,
                'interes_generado' => (float) $prestamo->interes_generado,
                'semanas'          => $prestamo->semanas !== null ? (int) $prestamo->semanas : null,
                'status'           => (int) $prestamo->status,
                'fecha_solicitud'  => $prestamo->fecha_solicitud,
                'fecha_inicio'     => $prestamo->fecha_inicio,
                'id_caja'          => $prestamo->id_caja,
                'caja'             => $prestamo->caja ? [
                    'id_caja' => $prestamo->caja->id_caja,
                    'nombre'  => $prestamo->caja->nombre,
                ] : null,
                'plan'             => $prestamo->plan ? [
                    'id'        => $prestamo->plan->id_prestamo,
                    'periodo'   => $prestamo->plan->periodo,
                    'semanas'   => $prestamo->plan->semanas,
                    'interes'   => $prestamo->plan->interes,
                    'monto_min' => $prestamo->plan->monto_min ?? null,
                    'monto_max' => $prestamo->plan->monto_max ?? null,
                    'status'    => (int) ($prestamo->plan->status ?? 0),
                ] : null,
            ],
        ], 201);
    }


    /* =========================
     * DELETE /api/prestamos/{id}
     * ========================= */
    public function destroy(Request $request, int $id)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = $request->user();

        $p = UserPrestamo::where('id', $id)
            ->where('id_cliente', $cliente->id)
            ->first();

        if (!$p) {
            return response()->json(['ok' => false, 'error' => 'Préstamo no encontrado.'], 404);
        }

        if (!in_array((int) $p->status, [2, 3], true)) {
            return response()->json([
                'ok'    => false,
                'error' => 'Solo puedes eliminar préstamos en estado Pendiente o En revisión.',
            ], 409);
        }

        $tieneMov = MovimientoCaja::where('origen_id', $p->id)->exists();
        if (!$tieneMov) {
            $tieneMov = MovimientoCaja::where('descripcion', 'like', "%préstamo #{$p->id}%")->exists();
        }
        if ($tieneMov) {
            return response()->json([
                'ok'    => false,
                'error' => 'El préstamo ya está vinculado a movimientos de caja y no puede eliminarse.',
            ], 409);
        }

        $p->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Préstamo eliminado.',
        ], 200);
    }

    /* =========================
     * GET /api/prestamos/planes
     * ========================= */
    public function planes(Request $request)
    {
        $soloActivos = filter_var(
            $request->input('solo_activos', $request->input('solo_activas', '1')),
            FILTER_VALIDATE_BOOLEAN
        );

        $planes = Prestamo::query()
            ->when($soloActivos, fn($q) => $q->where('status', '1'))
            ->orderBy('periodo')
            ->get([
                'id_prestamo',
                'periodo',
                'semanas',
                'interes',
                DB::raw('monto_minimo as monto_min'),
                DB::raw('monto_maximo as monto_max'),
                'status'
            ])
            ->map(function (Prestamo $p) {
                return [
                    'id'        => $p->id_prestamo,
                    'label'     => trim($p->periodo ?: ('Plan #'.$p->id_prestamo)),
                    'periodo'   => $p->periodo,
                    'semanas'   => $p->semanas,
                    'interes'   => $p->interes,
                    'monto_min' => $p->monto_min,
                    'monto_max' => $p->monto_max,
                    'status'    => (int) ($p->status ?? 0),
                ];
            });

        return response()->json([
            'ok'   => true,
            'data' => $planes,
        ]);
    }
}
