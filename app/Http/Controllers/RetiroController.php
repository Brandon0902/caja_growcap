<?php

namespace App\Http\Controllers;

use App\Services\VisibilityScope;
use App\Models\Cliente;

// ✅ Caja / Movimientos
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\CategoriaGasto;
use App\Services\ProveedorResolver;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use Carbon\Carbon;

// correo
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\RetiroInversionStatusActualizadoMail;
use App\Mail\RetiroAhorroStatusActualizadoMail;
use App\Mail\RetiroStatusActualizadoAdminMail;

class RetiroController extends Controller
{
    /** @var ProveedorResolver */
    protected $proveedorResolver;

    public function __construct(ProveedorResolver $proveedorResolver)
    {
        $this->proveedorResolver = $proveedorResolver;
    }

    /** Normaliza status (por si hay strings viejos en BD). */
    protected function statusToInt($status): int
    {
        if (is_numeric($status)) return (int)$status;

        $s = trim(mb_strtolower((string)$status));
        return match ($s) {
            'solicitado', 'pendiente' => 0,
            'aprobado'               => 1,
            'pagado'                 => 2,
            'rechazado'              => 3,
            default                  => 0,
        };
    }

    // =========================================================
    // ✅ HELPERS CAJAS / MOVIMIENTOS
    // =========================================================

    protected function assertCajaAllowed(int $idCaja): void
    {
        $ok = VisibilityScope::cajas(
            Caja::query()->where('id_caja', $idCaja),
            Auth::user()
        )->exists();

        if (!$ok) {
            abort(403, 'No puedes usar una caja fuera de tu alcance.');
        }
    }

    /**
     * ✅ Registra el egreso en movimientos_caja y actualiza saldo_final.
     * Evita duplicados por (id_caja + tipo_mov + origen_id + descripcion).
     */
    protected function registrarEgresoPorRetiro(
        int $idCaja,
        int $origenId,
        int $idCliente,
        float $monto,
        \DateTimeInterface $fechaMov,
        string $descripcion
    ): void {
        $exists = MovimientoCaja::where('id_caja', $idCaja)
            ->where('tipo_mov', 'Egreso')
            ->where('origen_id', $origenId)
            ->where('descripcion', $descripcion)
            ->exists();

        if ($exists) return;

        $caja = Caja::where('id_caja', $idCaja)->lockForUpdate()->first();
        if (!$caja) {
            throw new \RuntimeException('No se encontró la caja seleccionada.');
        }

        // saldo anterior = último movimiento o saldo_inicial
        $ultimoMov = MovimientoCaja::where('id_caja', $idCaja)
            ->orderByDesc('fecha')
            ->orderByDesc('id_mov')
            ->first();

        $saldoAnterior  = $ultimoMov ? (float)$ultimoMov->monto_posterior : (float)$caja->saldo_inicial;
        $saldoPosterior = $saldoAnterior - $monto;

        // Categoría gasto "Retiros"
        $cat = CategoriaGasto::firstOrCreate(
            ['nombre' => 'Retiros'],
            ['id_usuario' => 1]
        );

        // proveedor desde cliente
        $proveedorId = null;
        try {
            $proveedorId = $this->proveedorResolver->ensureFromCliente($idCliente);
        } catch (\Throwable $e) {
            Log::warning('[Retiros] No se pudo resolver proveedor para cliente', [
                'cliente_id' => $idCliente,
                'ex' => $e->getMessage(),
            ]);
        }

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'id_usuario'      => Auth::id(),
            'id_sucursal'     => (int)($caja->id_sucursal ?? 0),

            'tipo_mov'        => 'Egreso',

            'id_cat_ing'      => null,
            'id_sub_ing'      => null,
            'id_cat_gasto'    => $cat->id_cat_gasto ?? null,
            'id_sub_gasto'    => null,

            'proveedor_id'    => $proveedorId,
            'origen_id'       => $origenId,

            'monto'           => $monto,
            'fecha'           => $fechaMov,
            'descripcion'     => $descripcion,
            'monto_anterior'  => $saldoAnterior,
            'monto_posterior' => $saldoPosterior,
        ]);

        $caja->update(['saldo_final' => $saldoPosterior]);
    }

    // =========================================================
    // INDEX
    // =========================================================

    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $tab    = $request->get('tab', 'inv'); // 'inv' | 'ahorro'
        $u      = Auth::user();

        $clientesQ = DB::table('clientes')->select(
            'id',
            DB::raw('CONCAT(COALESCE(nombre,""), " ", COALESCE(apellido,"")) AS nombre'),
            'id_sucursal'
        );

        if ($u->can('retiros.ver_asignadas')) {
            $ids = DB::table('usuario_sucursal_acceso')
                ->where('usuario_id', $u->id_usuario ?? $u->id)
                ->pluck('id_sucursal')
                ->filter()
                ->toArray();

            if (!empty($u->id_sucursal)) {
                $ids[] = (int) $u->id_sucursal;
            }

            $ids = array_values(array_unique(array_map('intval', $ids)));
            $clientesQ->whereIn('id_sucursal', $ids ?: [-1]);
        } elseif (! $u->can('retiros.ver')) {
            $clientesQ->whereRaw('0=1');
        }

        $clientes = $clientesQ->orderBy('nombre')->get(['id','nombre']);

        // ✅ Cajas SOLO visibles + (si existe) abiertas
        $cajasQuery = VisibilityScope::cajas(Caja::query()->orderBy('nombre'), $u);
        if (Schema::hasColumn('cajas', 'estado')) {
            $cajasQuery->where('estado', 'abierta');
        }
        $cajas = $cajasQuery->get(['id_caja as id', 'nombre']);

        $retirosInvQ = DB::table('retiros as r')
            ->leftJoin('clientes as c', 'c.id', '=', 'r.id_cliente')
            ->select(
                'r.id',
                'r.tipo',
                'r.cantidad',
                'r.fecha_solicitud',
                'r.status',
                'r.id_caja',
                'c.nombre as cliente_nombre',
                'c.apellido as cliente_apellido',
                'c.email as cliente_email'
            )
            ->orderByDesc('r.id');

        $retirosInvQ = VisibilityScope::retirosInv($retirosInvQ, $u);

        if ($search !== '') {
            $retirosInvQ->where(function ($q) use ($search) {
                $q->where('c.nombre', 'like', "%{$search}%")
                  ->orWhere('c.apellido', 'like', "%{$search}%")
                  ->orWhere('c.email', 'like', "%{$search}%")
                  ->orWhere('r.id', $search)
                  ->orWhere('r.tipo', 'like', "%{$search}%");
            });
        }

        $retirosInv = $retirosInvQ->paginate(10, ['*'], 'page_inv');

        $retirosAhQ = DB::table('retiros_ahorro as ra')
            ->leftJoin('clientes as c', 'c.id', '=', 'ra.id_cliente')
            ->select(
                'ra.id',
                'ra.tipo',
                'ra.cantidad',
                DB::raw('COALESCE(ra.fecha_solicitud, ra.created_at) as fecha_solicitud'),
                'ra.status',
                'ra.id_caja',
                'c.nombre as cliente_nombre',
                'c.apellido as cliente_apellido',
                'c.email as cliente_email'
            )
            ->orderByDesc('ra.id');

        $retirosAhQ = VisibilityScope::retirosAhorro($retirosAhQ, $u);

        if ($search !== '') {
            $retirosAhQ->where(function ($q) use ($search) {
                $q->where('c.nombre', 'like', "%{$search}%")
                  ->orWhere('c.apellido', 'like', "%{$search}%")
                  ->orWhere('c.email', 'like', "%{$search}%")
                  ->orWhere('ra.id', $search)
                  ->orWhere('ra.tipo', 'like', "%{$search}%");
            });
        }

        $retirosAh = $retirosAhQ->paginate(10, ['*'], 'page_ah');

        return view('retiros.index', compact(
            'clientes',
            'cajas',
            'retirosInv',
            'retirosAh',
            'search',
            'tab'
        ));
    }

    // =========================================================
    // STORE: crea retiro solicitado (no descuenta) pero valida caja si mandan id_caja
    // =========================================================

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'tipo'       => ['required', Rule::in(['ahorro','inversion'])],
            'monto'      => ['required', 'numeric', 'min:0.01'],
            'id_caja'    => ['nullable', 'integer', 'exists:cajas,id_caja'],
            'nota'       => ['nullable', 'string', 'max:255'],
        ]);

        $u = Auth::user();

        $clienteSucursal = (int) DB::table('clientes')
            ->where('id', (int) $data['cliente_id'])
            ->value('id_sucursal');

        $puedeCrear = false;

        if ($u->can('retiros.ver_asignadas')) {
            $ids = DB::table('usuario_sucursal_acceso')
                ->where('usuario_id', $u->id_usuario ?? $u->id)
                ->pluck('id_sucursal')
                ->filter()
                ->toArray();
            if (!empty($u->id_sucursal)) $ids[] = (int) $u->id_sucursal;

            $ids = array_values(array_unique(array_map('intval', $ids)));
            $puedeCrear = in_array($clienteSucursal, $ids, true);
        } elseif ($u->can('retiros.ver')) {
            $puedeCrear = true;
        }

        if (!$puedeCrear) {
            throw ValidationException::withMessages([
                'cliente_id' => 'No puedes crear un retiro para un cliente de otra sucursal.',
            ]);
        }

        // ✅ si mandan caja, validar alcance
        if (!empty($data['id_caja'])) {
            $this->assertCajaAllowed((int)$data['id_caja']);
        }

        $uid = Auth::id() ?: null;
        $now = now();

        if ($data['tipo'] === 'ahorro') {
            DB::table('retiros_ahorro')->insert([
                'tipo'                => 'Transferencia',
                'id_cliente'          => (int) $data['cliente_id'],
                'created_at'          => $now,
                'fecha_transferencia' => null,
                'fecha_aprobacion'    => null,
                'fecha_solicitud'     => $now,
                'cantidad'            => (float) $data['monto'],
                'id_ahorro'           => null,
                'status'              => 0,
                'id_caja'             => $data['id_caja'] ?? null,
                'descuento_aplicado'  => 0,
                'rollback_at'         => null,
                'rollback_user_id'    => null,
            ]);
        } else {
            DB::table('retiros')->insert([
                'id_cliente'          => (int) $data['cliente_id'],
                'tipo'                => 'Transferencia',
                'cantidad'            => (string) $data['monto'],
                'fecha_solicitud'     => $now,
                'fecha_aprobacion'    => null,
                'fecha_transferencia' => null,
                'id_usuario'          => $uid,
                'status'              => 0,
                'id_caja'             => $data['id_caja'] ?? null,
                'id_user_inversion'   => null,
                'descuento_aplicado'  => 0,
                'rollback_at'         => null,
                'rollback_user_id'    => null,
            ]);
        }

        return redirect()
            ->route('retiros.index', ['tab' => $data['tipo'] === 'ahorro' ? 'ahorro' : 'inv'])
            ->with('ok', 'Retiro registrado como "Solicitado".');
    }

    // =========================================================
    // UPDATE INVERSION: aprobar/pagar/rechazar + rollback + CORREO
    // ✅ Al PAGAR: crea movimiento + descuenta caja + descuento_aplicado=1
    // =========================================================

    public function updateInversion(Request $request, int $id)
    {
        $data = $request->validate([
            'tipo'    => ['required','string','max:100'],
            'cantidad'=> ['required','numeric','min:0.01'],
            'status'  => ['required', Rule::in([0,1,2,3])],
            'id_caja' => ['nullable','integer','exists:cajas,id_caja'],
        ]);

        $uid = Auth::id() ?: null;
        $now = now();

        return DB::transaction(function () use ($id, $data, $uid, $now) {

            $row = DB::table('retiros')->where('id', $id)->lockForUpdate()->first();
            if (!$row) abort(404);

            $prevStatus = $this->statusToInt($row->status);
            $newStatus  = (int)$data['status'];

            // si ya rechazado, no lo muevas
            if ($prevStatus === 3 && $newStatus !== 3) {
                throw ValidationException::withMessages([
                    'status' => 'Este retiro ya fue rechazado y no puede cambiar de estado.',
                ]);
            }

            // ✅ si ya pagado, no puede cambiar a otro estado
            if ($prevStatus === 2 && $newStatus !== 2) {
                throw ValidationException::withMessages([
                    'status' => 'Este retiro ya está "Pagado" y no puede cambiar de estado.',
                ]);
            }

            $descuentoAplicado = (int)($row->descuento_aplicado ?? 0) === 1;
            $rollbackAt        = $row->rollback_at ?? null;

            // si ya hubo descuento, no permitas cambiar la cantidad
            if ($descuentoAplicado && (float)$data['cantidad'] != (float)$row->cantidad) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No puedes modificar la cantidad porque ya se descontó en el API.',
                ]);
            }

            // ✅ Si va a PAGADO: requiere caja (request o la ya guardada)
            if ($newStatus === 2) {
                $idCaja = (int)($data['id_caja'] ?? $row->id_caja ?? 0);
                if ($idCaja <= 0) {
                    throw ValidationException::withMessages([
                        'id_caja' => 'Selecciona una caja para marcar como "Pagado".',
                    ]);
                }

                $this->assertCajaAllowed($idCaja);

                // ✅ Descontar solo una vez
                if (!$descuentoAplicado) {
                    $this->registrarEgresoPorRetiro(
                        $idCaja,
                        (int)$id, // origen_id
                        (int)$row->id_cliente,
                        (float)$row->cantidad,
                        Carbon::parse($now),
                        "Pago retiro inversión #{$id}"
                    );
                    $descuentoAplicado = true;
                }
            }

            // ===== ROLLBACK si pasa a Rechazado (3) =====
            if ($newStatus === 3 && $descuentoAplicado && empty($rollbackAt)) {

                $detalle = [];
                if (Schema::hasColumn('retiros', 'detalle_consumo') && !empty($row->detalle_consumo)) {
                    $decoded = json_decode($row->detalle_consumo, true);
                    if (is_array($decoded)) {
                        $detalle = $decoded;
                    }
                }

                if (!empty($detalle)) {
                    Log::info('[Retiros] Rollback usando detalle_consumo', [
                        'retiro_id' => $id,
                        'detalle'   => $detalle,
                    ]);

                    // 1) DEPÓSITOS
                    if (Schema::hasTable('user_depositos')) {
                        $tblDep = 'user_depositos';
                        $depMontoCol  = Schema::hasColumn($tblDep, 'cantidad')
                            ? 'cantidad'
                            : (Schema::hasColumn($tblDep, 'monto') ? 'monto' : null);
                        $depStatusCol = Schema::hasColumn($tblDep, 'status') ? 'status' : null;

                        if ($depMontoCol) {
                            foreach ($detalle['deposits'] ?? [] as $d) {
                                $depId = (int)($d['id'] ?? 0);
                                $monto = (float)($d['monto'] ?? 0);
                                if ($depId <= 0 || $monto <= 0) continue;

                                $update = [
                                    $depMontoCol => DB::raw("$depMontoCol + {$monto}"),
                                ];
                                if ($depStatusCol) $update[$depStatusCol] = 1;

                                DB::table($tblDep)->where('id', $depId)->update($update);
                            }
                        }
                    }

                    // 2) INVERSIONES TERMINADAS (capital_actual)
                    if (Schema::hasTable('user_inversiones')) {
                        $tblInv = 'user_inversiones';
                        $capCol = Schema::hasColumn($tblInv, 'capital_actual')
                            ? 'capital_actual'
                            : (Schema::hasColumn($tblInv, 'inversion') ? 'inversion' : null);

                        if ($capCol) {
                            foreach ($detalle['inversiones'] ?? [] as $inv) {
                                $invId = (int)($inv['id'] ?? 0);
                                $monto = (float)($inv['monto'] ?? 0);
                                if ($invId <= 0 || $monto <= 0) continue;

                                DB::table($tblInv)->where('id', $invId)->update([
                                    $capCol => DB::raw("$capCol + {$monto}"),
                                ]);
                            }
                        }
                    }

                    // 3) AHORROS ACTIVOS (saldo_disponible)
                    if (Schema::hasTable('user_ahorro')) {
                        $tblAho = 'user_ahorro';
                        $ahoCol = Schema::hasColumn($tblAho, 'saldo_disponible')
                            ? 'saldo_disponible'
                            : (Schema::hasColumn($tblAho, 'saldo_disp') ? 'saldo_disp' : null);

                        if ($ahoCol) {
                            foreach ($detalle['ahorros'] ?? [] as $a) {
                                $ahId  = (int)($a['id'] ?? 0);
                                $monto = (float)($a['monto'] ?? 0);
                                if ($ahId <= 0 || $monto <= 0) continue;

                                DB::table($tblAho)->where('id', $ahId)->update([
                                    $ahoCol => DB::raw("$ahoCol + {$monto}"),
                                ]);
                            }
                        }
                    }

                    DB::table('retiros')->where('id', $id)->update([
                        'rollback_at'      => $now,
                        'rollback_user_id' => $uid,
                    ]);
                } else {
                    $monto = (float)$row->cantidad;
                    $invId = (int)($row->id_user_inversion ?? 0);

                    if (!$invId) {
                        throw ValidationException::withMessages([
                            'status' => 'No se puede hacer rollback: falta id_user_inversion o detalle_consumo en este retiro.',
                        ]);
                    }

                    if (Schema::hasColumn('user_inversiones', 'capital_actual')) {
                        DB::table('user_inversiones')->where('id', $invId)->update([
                            'capital_actual' => DB::raw('capital_actual + '.$monto),
                        ]);
                    } elseif (Schema::hasColumn('user_inversiones', 'inversion')) {
                        DB::table('user_inversiones')->where('id', $invId)->update([
                            'inversion' => DB::raw('inversion + '.$monto),
                        ]);
                    } else {
                        throw ValidationException::withMessages([
                            'status' => 'No se puede hacer rollback: no existe columna capital_actual/inversion.',
                        ]);
                    }

                    DB::table('retiros')->where('id', $id)->update([
                        'rollback_at'      => $now,
                        'rollback_user_id' => $uid,
                    ]);
                }
            }

            // ===== UPDATE NORMAL =====
            DB::table('retiros')->where('id', $id)->update([
                'tipo'               => $data['tipo'],
                'cantidad'           => (string)$data['cantidad'],
                'status'             => $newStatus,
                'id_caja'            => $data['id_caja'] ?? $row->id_caja ?? null,
                'id_usuario'         => $uid,
                'fecha_aprobacion'   => ($newStatus >= 1) ? $now : null,
                'fecha_transferencia'=> ($newStatus === 2) ? $now : null,
                'descuento_aplicado' => $descuentoAplicado ? 1 : 0,
            ]);

            // ===== CORREO AL CLIENTE =====
            try {
                Log::info('[Retiros] Entrando a envío de correo (inversión)', [
                    'retiro_id'    => $id,
                    'id_cliente'   => $row->id_cliente ?? null,
                    'nuevo_status' => $newStatus,
                ]);

                $cliente = Cliente::find($row->id_cliente);

                if ($cliente && !empty($cliente->email)) {
                    Mail::to($cliente->email)->send(
                        new RetiroInversionStatusActualizadoMail($cliente, $row, $newStatus)
                    );
                }

                $adminEmail = trim((string) config('services.admin.email'));
                if ($cliente && $adminEmail !== '') {
                    Mail::to($adminEmail)->send(
                        new RetiroStatusActualizadoAdminMail($cliente, $row, $newStatus, 'inversion')
                    );
                }
            } catch (\Throwable $e) {
                Log::error('[Retiros] Error enviando correo (inversión)', [
                    'retiro_id' => $id,
                    'error'     => $e->getMessage(),
                ]);
            }

            return back()->with('ok', 'Retiro de inversión actualizado.');
        });
    }

    // =========================================================
    // UPDATE AHORRO: aprobar/pagar/rechazar + rollback + CORREO
    // ✅ Al PAGAR: crea movimiento + descuenta caja + descuento_aplicado=1
    // =========================================================

    public function updateAhorro(Request $request, int $id)
    {
        $data = $request->validate([
            'tipo'    => ['required','string','max:100'],
            'cantidad'=> ['required','numeric','min:0.01'],
            'status'  => ['required', Rule::in([0,1,2,3])],
            'id_caja' => ['nullable','integer','exists:cajas,id_caja'],
        ]);

        $uid = Auth::id() ?: null;
        $now = now();

        return DB::transaction(function () use ($id, $data, $uid, $now) {

            $row = DB::table('retiros_ahorro')->where('id', $id)->lockForUpdate()->first();
            if (!$row) abort(404);

            $prevStatus = $this->statusToInt($row->status);
            $newStatus  = (int)$data['status'];

            if ($prevStatus === 3 && $newStatus !== 3) {
                throw ValidationException::withMessages([
                    'status' => 'Este retiro ya fue rechazado y no puede cambiar de estado.',
                ]);
            }

            // ✅ si ya pagado, no puede cambiar a otro estado
            if ($prevStatus === 2 && $newStatus !== 2) {
                throw ValidationException::withMessages([
                    'status' => 'Este retiro ya está "Pagado" y no puede cambiar de estado.',
                ]);
            }

            $descuentoAplicado = (int)($row->descuento_aplicado ?? 0) === 1;
            $rollbackAt        = $row->rollback_at ?? null;

            if ($descuentoAplicado && (float)$data['cantidad'] != (float)$row->cantidad) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No puedes modificar la cantidad porque ya se descontó en el API.',
                ]);
            }

            // ✅ Si va a PAGADO: requiere caja (request o la ya guardada)
            if ($newStatus === 2) {
                $idCaja = (int)($data['id_caja'] ?? $row->id_caja ?? 0);
                if ($idCaja <= 0) {
                    throw ValidationException::withMessages([
                        'id_caja' => 'Selecciona una caja para marcar como "Pagado".',
                    ]);
                }

                $this->assertCajaAllowed($idCaja);

                // ✅ Descontar solo una vez
                if (!$descuentoAplicado) {
                    $this->registrarEgresoPorRetiro(
                        $idCaja,
                        (int)$id, // origen_id
                        (int)$row->id_cliente,
                        (float)$row->cantidad,
                        Carbon::parse($now),
                        "Pago retiro ahorro #{$id}"
                    );
                    $descuentoAplicado = true;
                }
            }

            // ===== ROLLBACK si pasa a Rechazado (3) =====
            if ($newStatus === 3 && $descuentoAplicado && empty($rollbackAt)) {
                $monto   = (float)$row->cantidad;
                $uaId    = (int)($row->id_ahorro ?? 0); // user_ahorro.id

                if (!$uaId) {
                    throw ValidationException::withMessages([
                        'status' => 'No se puede hacer rollback: falta id_ahorro (user_ahorro.id) en este retiro.',
                    ]);
                }

                DB::table('user_ahorro')->where('id', $uaId)->update([
                    'saldo_disponible' => DB::raw('saldo_disponible + '.$monto),
                ]);

                DB::table('retiros_ahorro')->where('id', $id)->update([
                    'rollback_at'      => $now,
                    'rollback_user_id' => $uid,
                ]);
            }

            // ===== UPDATE NORMAL =====
            DB::table('retiros_ahorro')->where('id', $id)->update([
                'tipo'               => $data['tipo'],
                'cantidad'           => (float)$data['cantidad'],
                'status'             => $newStatus,
                'id_caja'            => $data['id_caja'] ?? $row->id_caja ?? null,
                'fecha_aprobacion'   => ($newStatus >= 1) ? $now : null,
                'fecha_transferencia'=> ($newStatus === 2) ? $now : null,
                'descuento_aplicado' => $descuentoAplicado ? 1 : 0,
            ]);

            // ===== CORREO AL CLIENTE =====
            try {
                $cliente = Cliente::find($row->id_cliente);

                if ($cliente && !empty($cliente->email)) {
                    Mail::to($cliente->email)->send(
                        new RetiroAhorroStatusActualizadoMail($cliente, $row, $newStatus)
                    );
                }

                $adminEmail = trim((string) config('services.admin.email'));
                if ($cliente && $adminEmail !== '') {
                    Mail::to($adminEmail)->send(
                        new RetiroStatusActualizadoAdminMail($cliente, $row, $newStatus, 'ahorro')
                    );
                }
            } catch (\Throwable $e) {
                Log::error('[Retiros] Error enviando correo (ahorro)', [
                    'retiro_ahorro_id' => $id,
                    'error'            => $e->getMessage(),
                ]);
            }

            return back()->with('ok', 'Retiro de ahorro actualizado.');
        });
    }
}