<?php

namespace App\Http\Controllers;

use App\Models\Ahorro;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\UserAhorro;
use App\Models\MovimientoCaja;
use App\Models\CategoriaIngreso;
use App\Models\CategoriaGasto;
use App\Services\ProveedorResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Services\ClienteMailService;
use App\Mail\AhorroActivadoClienteMail;
use App\Mail\AhorroInactivoClienteMail;
use App\Mail\AhorroActivadoAdminMail;
use App\Mail\AhorroInactivoAdminMail;

class UserAhorroController extends Controller
{
    private const CAT_INGRESO   = 'Ahorros';
    private const CAT_RETIRO_AH = 'Retiros de ahorro';

    protected ProveedorResolver $proveedorResolver;

    public function __construct(
        ProveedorResolver $proveedorResolver,
        private ClienteMailService $clienteMail
    ) {
        $this->proveedorResolver = $proveedorResolver;
    }

    /* ============ Acumulación diaria (compuesto) ============ */
    private function accrueUntil(UserAhorro $ahorro, ?Carbon $hasta = null): void
    {
        // Solo acumula si está Activo (1) y tiene base
        if ((int)$ahorro->status !== 1) return;

        $hasta = ($hasta ?: now())->copy()->startOfDay();
        if (empty($ahorro->ultimo_calculo) || $ahorro->capital_actual === null) return;

        $desde = Carbon::parse($ahorro->ultimo_calculo)->startOfDay();
        $dias  = max(0, $desde->diffInDays($hasta));
        if ($dias === 0) return;

        $tasaAnual = (float)($ahorro->rendimiento ?? 0) / 100.0;
        $r_dia     = $tasaAnual / 365.0;

        $capitalBase  = (float)$ahorro->capital_actual;
        $capitalNuevo = $capitalBase * pow(1 + $r_dia, $dias);
        $ganado       = $capitalNuevo - $capitalBase;

        $ahorro->update([
            'capital_actual'       => $capitalNuevo,
            'rendimiento_generado' => (float)($ahorro->rendimiento_generado ?? 0) + $ganado,
            'ultimo_calculo'       => $hasta->toDateString(),
        ]);
        $ahorro->refresh();
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status');
        $desde  = $request->input('desde');
        $hasta  = $request->input('hasta');
        $orden  = $request->input('orden', 'fecha_desc');

        $query = UserAhorro::query()
            ->with([
                'cliente:id,nombre,apellido,email',
                'ahorro:id,tipo_ahorro',
                'caja:id_caja,nombre',
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('id', $search)
                       ->orWhere('monto_ahorro', 'like', "%{$search}%")
                       ->orWhere('tipo', 'like', "%{$search}%")
                       ->orWhereHas('ahorro', function ($qa) use ($search) {
                           $qa->where('tipo_ahorro', 'like', "%{$search}%");
                       })
                       ->orWhereHas('cliente', function ($qc) use ($search) {
                           $qc->where('nombre', 'like', "%{$search}%")
                              ->orWhere('apellido', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                       });
                });
            })
            // ✅ incluye 0 Pendiente
            ->when(in_array((string)$status, ['0','1','2'], true), fn($q) => $q->where('status', (int)$status))
            ->when($desde, fn($q) => $q->whereDate('fecha_inicio', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha_inicio', '<=', $hasta));

        $query = match ($orden) {
            'monto_asc'  => $query->orderBy('monto_ahorro', 'asc'),
            'monto_desc' => $query->orderBy('monto_ahorro', 'desc'),
            'fecha_asc'  => $query->orderBy('fecha_inicio', 'asc'),
            default      => $query->orderBy('fecha_inicio', 'desc'),
        };

        $ahorros = $query->paginate(15)->withQueryString();

        $statusOptions = [null => 'Todos', 0 => 'Pendiente', 1 => 'Activo', 2 => 'Inactivo'];

        return view('adminuserahorros.index', compact('ahorros', 'search', 'status', 'statusOptions', 'desde', 'hasta', 'orden'));
    }

    // ✅ IMPORTANTE: nombre del parámetro debe ser $ahorro (igual que {ahorro})
    public function show(UserAhorro $ahorro)
    {
        $this->accrueUntil($ahorro, now());

        $ahorro->load(['cliente','ahorro:id,tipo_ahorro','caja']);
        $statusOptions = [0 => 'Pendiente', 1 => 'Activo', 2 => 'Inactivo'];

        return view('adminuserahorros.show', compact('ahorro','statusOptions'));
    }

    public function create()
    {
        return view('adminuserahorros.create', [
            'clientes' => Cliente::orderBy('nombre')->get(),
            'planes'   => Ahorro::where('status',1)->get(),
            'cajas'    => Caja::where('estado','abierta')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cliente'   => 'required|exists:clientes,id',
            'ahorro_id'    => 'required|exists:ahorros,id',
            'fecha_inicio' => 'required|date',
            'monto_ahorro' => 'required|numeric|min:0.01',
            'id_caja'      => 'required|exists:cajas,id_caja',
        ]);

        $plan = Ahorro::findOrFail($data['ahorro_id']);
        $tasa = (float)($plan->rendimiento ?? 0);

        $ahorro = UserAhorro::create([
            'id_cliente'           => (int)$data['id_cliente'],
            'ahorro_id'            => (int)$data['ahorro_id'],
            'fecha_solicitud'      => now(),
            'fecha_inicio'         => $data['fecha_inicio'],
            'monto_ahorro'         => (float)$data['monto_ahorro'],
            'rendimiento'          => $tasa,
            'rendimiento_generado' => 0.0,
            'capital_actual'       => (float)$data['monto_ahorro'],
            'ultimo_calculo'       => Carbon::parse($data['fecha_inicio'])->toDateString(),
            'status'               => 1,
            'id_usuario'           => Auth::id(),
            'id_caja'              => (int)$data['id_caja'],
        ]);

        $this->ingresarPagoEnCaja($ahorro);

        return redirect()->route('user_ahorros.index')
            ->with('success','Ahorro activado y registrado en caja.');
    }

    // ✅ IMPORTANTE: nombre del parámetro debe ser $ahorro (igual que {ahorro})
    public function edit(UserAhorro $ahorro)
    {
        $this->accrueUntil($ahorro, now());

        return view('adminuserahorros.edit', [
            'userAhorro'    => $ahorro->load(['cliente','ahorro:id,tipo_ahorro','caja']),
            'statusOptions' => [0 => 'Pendiente', 1 => 'Activo', 2 => 'Inactivo'],
            'cajas'         => Caja::where('estado','abierta')->get(),
        ]);
    }

    // ✅ IMPORTANTE: nombre del parámetro debe ser $ahorro (igual que {ahorro})
    public function update(Request $request, UserAhorro $ahorro)
    {
        $data = $request->validate([
            'status'  => 'required|in:0,1,2',
            'id_caja' => 'nullable|exists:cajas,id_caja',
            'nota'    => 'nullable|string|max:5000',
        ]);

        $old = (int)$ahorro->status;
        $new = (int)$data['status'];

        // Caja solo obligatoria si vas a operar (1 o 2)
        if (in_array($new, [1,2], true) && empty($data['id_caja']) && empty($ahorro->id_caja)) {
            return back()
                ->withErrors(['id_caja' => 'Debes seleccionar una caja para operar.'])
                ->withInput();
        }

        $ahorro->loadMissing(['cliente', 'ahorro']);

        $cliente = $ahorro->cliente ?: Cliente::find($ahorro->id_cliente);
        $clienteData = $cliente ? $this->clienteMail->mailData($cliente) : [
            'id' => (int)$ahorro->id_cliente,
            'nombre_completo' => 'Cliente '.$ahorro->id_cliente,
            'email' => '',
        ];

        $clienteEmail = trim((string)($clienteData['email'] ?? ''));
        $adminEmail   = trim((string)config('mail.from.address'));
        $planLabel    = $ahorro->ahorro?->tipo_ahorro ?? null;

        try {
            DB::transaction(function () use ($ahorro, $data, $old, $new) {

                $ahorro->update([
                    'status'  => $new,
                    'id_caja' => $data['id_caja'] ?? $ahorro->id_caja,
                    'nota'    => $data['nota'] ?? $ahorro->nota,
                ]);
                $ahorro->refresh();

                // Solo Activo(1) -> Inactivo(2): egreso
                if ($old === 1 && $new === 2) {
                    $this->accrueUntil($ahorro, now());
                    $this->descontarDeCaja($ahorro);
                }

                // Cualquier -> Activo(1): ingreso (si no existe mov)
                if ($old !== 1 && $new === 1) {
                    if ($ahorro->capital_actual === null) {
                        $ahorro->update([
                            'capital_actual' => (float)$ahorro->monto_ahorro,
                            'ultimo_calculo' => Carbon::parse($ahorro->fecha_inicio ?? now())->toDateString(),
                        ]);
                        $ahorro->refresh();
                    }
                    $this->ingresarPagoEnCaja($ahorro);
                }
            });

            if ($old !== $new) {
                DB::afterCommit(function () use ($new, $ahorro, $clienteData, $clienteEmail, $adminEmail, $planLabel) {
                    if ($new === 1) {
                        if ($clienteEmail !== '') Mail::to($clienteEmail)->send(new AhorroActivadoClienteMail($ahorro, $clienteData, $planLabel));
                        if ($adminEmail !== '')   Mail::to($adminEmail)->send(new AhorroActivadoAdminMail($ahorro, $clienteData, $planLabel));
                    }
                    if ($new === 2) {
                        if ($clienteEmail !== '') Mail::to($clienteEmail)->send(new AhorroInactivoClienteMail($ahorro, $clienteData, $planLabel));
                        if ($adminEmail !== '')   Mail::to($adminEmail)->send(new AhorroInactivoAdminMail($ahorro, $clienteData, $planLabel));
                    }
                });
            }

        } catch (\Throwable $e) {
            Log::warning('Admin ahorro update failed', [
                'ahorro_id' => $ahorro->id ?? null,
                'old'       => $old,
                'new'       => $new,
                'err'       => $e->getMessage(),
            ]);

            return back()->withErrors(['status' => 'No se pudo actualizar el ahorro. '.$e->getMessage()]);
        }

        return redirect()->route('user_ahorros.index')->with('success', 'Ahorro actualizado correctamente.');
    }

    /* ===== Movimientos de caja ===== */

    protected function ingresarPagoEnCaja(UserAhorro $ahorro): void
    {
        $catIng = CategoriaIngreso::firstOrCreate(
            ['nombre' => self::CAT_INGRESO],
            ['id_usuario' => 1]
        );

        $exists = MovimientoCaja::where('tipo_mov','Ingreso')
            ->where('id_cat_ing', $catIng->id_cat_ing)
            ->where('origen_id', $ahorro->id)
            ->exists();
        if ($exists) return;

        $caja = Caja::findOrFail($ahorro->id_caja);

        $last    = $caja->movimientos()->orderByDesc('fecha')->orderByDesc('id_mov')->first();
        $antes   = $last ? $last->monto_posterior : $caja->saldo_inicial;

        $monto   = (float) $ahorro->monto_ahorro;
        $despues = $antes + $monto;

        $proveedorId = $this->proveedorResolver->ensureFromCliente($ahorro->id_cliente);

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'tipo_mov'        => 'Ingreso',
            'id_cat_ing'      => $catIng->id_cat_ing,
            'id_sub_ing'      => null,
            'id_cat_gasto'    => null,
            'id_sub_gasto'    => null,
            'proveedor_id'    => $proveedorId,
            'origen_id'       => $ahorro->id,
            'monto'           => $monto,
            'fecha'           => $ahorro->fecha_inicio ?? now(),
            'descripcion'     => "Depósito ahorro #{$ahorro->id}",
            'monto_anterior'  => $antes,
            'monto_posterior' => $despues,
            'id_usuario'      => Auth::id(),
        ]);

        $caja->update(['saldo_final' => $despues]);
    }

    protected function descontarDeCaja(UserAhorro $ahorro): void
    {
        $catG = CategoriaGasto::firstOrCreate(
            ['nombre' => self::CAT_RETIRO_AH],
            ['id_usuario' => 1]
        );

        $exists = MovimientoCaja::where('tipo_mov','Egreso')
            ->where('id_cat_gasto', $catG->id_cat_gasto)
            ->where('origen_id', $ahorro->id)
            ->exists();
        if ($exists) return;

        $caja = Caja::findOrFail($ahorro->id_caja);

        $last    = $caja->movimientos()->orderByDesc('fecha')->orderByDesc('id_mov')->first();
        $antes   = $last ? $last->monto_posterior : $caja->saldo_inicial;

        $monto   = (float) $ahorro->monto_ahorro + (float) ($ahorro->rendimiento_generado ?? 0);
        $despues = $antes - $monto;

        $proveedorId = $this->proveedorResolver->ensureFromCliente($ahorro->id_cliente);

        MovimientoCaja::create([
            'id_caja'         => $caja->id_caja,
            'tipo_mov'        => 'Egreso',
            'id_cat_gasto'    => $catG->id_cat_gasto,
            'id_sub_gasto'    => null,
            'id_cat_ing'      => null,
            'id_sub_ing'      => null,
            'proveedor_id'    => $proveedorId,
            'origen_id'       => $ahorro->id,
            'monto'           => $monto,
            'fecha'           => now(),
            'descripcion'     => "Retiro ahorro #{$ahorro->id} (capital + interés)",
            'monto_anterior'  => $antes,
            'monto_posterior' => $despues,
            'id_usuario'      => Auth::id(),
        ]);

        $caja->update(['saldo_final' => $despues]);
    }
}
