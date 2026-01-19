<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\UserData;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Empresa;
use App\Models\User;
use App\Mail\UserDataActualizadaAdminMail;
use App\Mail\UserDataActualizadaClienteMail;
use App\Notifications\NuevaSolicitudNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserDataController extends Controller
{
    protected function pickCol(string $table, array $candidates): ?string
    {
        if (!Schema::hasTable($table)) return null;
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }

    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $tblInv = 'user_inversiones';
        $tblPre = 'user_prestamos';
        $tblAho = 'user_ahorro';
        $tblDep = 'user_depositos';

        $hasInv = Schema::hasTable($tblInv);
        $hasPre = Schema::hasTable($tblPre);
        $hasAho = Schema::hasTable($tblAho);
        $hasDep = Schema::hasTable($tblDep);

        $invPrincipalCol = $this->pickCol($tblInv, ['inversion','monto','capital','cantidad']);
        $invRendCol      = $this->pickCol($tblInv, ['rendimiento_generado','rend_gen','ganancia','interes_generado']);
        $invRet1Col      = $this->pickCol($tblInv, ['retiros','monto_retirado']);
        $invRet2Col      = $this->pickCol($tblInv, ['retiros_echos','retiros_hechos']);
        $invStatusCol    = $this->pickCol($tblInv, ['status']);

        $preCantCol   = $this->pickCol($tblPre, ['cantidad']);
        $prePendCol   = $this->pickCol($tblPre, ['saldo_pendiente','saldo_restante','monto_restante']);
        $preVencCol   = $this->pickCol($tblPre, ['saldo_vencido']);
        $preAbonosCol = $this->pickCol($tblPre, ['abonos_echos','abonos_hechos']);
        $preIntGenCol = $this->pickCol($tblPre, ['interes_generado']);

        $ahoMontoCol    = $this->pickCol($tblAho, ['monto_ahorro','monto','monto_deposito']);
        $ahoRgenCol     = $this->pickCol($tblAho, ['rendimiento_generado','rend_gen','intereses']);
        $ahoDispCol     = $this->pickCol($tblAho, ['saldo_disponible','saldo_disp']);
        $ahoStatusCol   = $this->pickCol($tblAho, ['status']);
        $ahoFechaFinCol = $this->pickCol($tblAho, ['fecha_fin']);

        $depMontoCol   = $this->pickCol($tblDep, ['cantidad','monto']);
        $depStatusCol  = $this->pickCol($tblDep, ['status']);

        $today = now()->toDateString();

        $query = Cliente::query()
            ->select('clientes.*')
            ->with('userData')
            ->when($search, function ($q) use ($search) {
                $q->where(function($qq) use($search){
                    $qq->where('nombre', 'like', "%{$search}%")
                       ->orWhere('apellido', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            });

        if ($hasInv) {
            $query->addSelect([
                'inv_count' => DB::table($tblInv)->selectRaw('COUNT(*)')
                    ->whereColumn("$tblInv.id_cliente", 'clientes.id'),
            ]);
            $invSumExpr = $invPrincipalCol ? "COALESCE(SUM($invPrincipalCol),0)" : "0";
            $query->addSelect([
                'inv_saldo' => DB::table($tblInv)->selectRaw($invSumExpr)
                    ->whereColumn("$tblInv.id_cliente", 'clientes.id'),
            ]);
        } else {
            $query->addSelect(DB::raw('0 as inv_count'))
                  ->addSelect(DB::raw('0 as inv_saldo'));
        }

        if ($hasPre) {
            $query->addSelect([
                'pres_count' => DB::table($tblPre)->selectRaw('COUNT(*)')
                    ->whereColumn("$tblPre.id_cliente", 'clientes.id'),
            ]);

            $prePendExpr = $prePendCol
                ? "COALESCE(SUM($prePendCol),0)"
                : "COALESCE(SUM(("
                    . ($preCantCol   ?: '0') . " + "
                    . ($preIntGenCol ?: '0') . " - "
                    . ($preAbonosCol ?: '0')
                    . ")),0)";

            $query->addSelect([
                'pres_pend' => DB::table($tblPre)->selectRaw($prePendExpr)
                    ->whereColumn("$tblPre.id_cliente", 'clientes.id'),
            ]);

            if ($preVencCol) {
                $query->addSelect([
                    'pres_venc' => DB::table($tblPre)->selectRaw("COALESCE(SUM($preVencCol),0)")
                        ->whereColumn("$tblPre.id_cliente", 'clientes.id'),
                ]);
            } else {
                $query->addSelect(DB::raw('0 as pres_venc'));
            }
        } else {
            $query->addSelect(DB::raw('0 as pres_count'))
                  ->addSelect(DB::raw('0 as pres_pend'))
                  ->addSelect(DB::raw('0 as pres_venc'));
        }

        if ($hasAho) {
            $ahorrando = DB::table($tblAho)
                ->selectRaw($ahoMontoCol ? "COALESCE(SUM($ahoMontoCol),0)" : "0")
                ->whereColumn("$tblAho.id_cliente", 'clientes.id');
            if ($ahoStatusCol) $ahorrando->where("$tblAho.$ahoStatusCol", 5);
            $query->addSelect(['ah_ahorrando' => $ahorrando]);

            $accExpr = ($ahoMontoCol && $ahoRgenCol)
                ? "COALESCE(SUM($ahoMontoCol + $ahoRgenCol),0)"
                : ($ahoMontoCol ? "COALESCE(SUM($ahoMontoCol),0)" : "0");

            $acumulado = DB::table($tblAho)
                ->selectRaw($accExpr)
                ->whereColumn("$tblAho.id_cliente", 'clientes.id');
            if ($ahoStatusCol) $acumulado->where("$tblAho.$ahoStatusCol", 5);
            $query->addSelect(['ah_acumulado' => $acumulado]);
        } else {
            $query->addSelect(DB::raw('0 as ah_ahorrando'))
                  ->addSelect(DB::raw('0 as ah_acumulado'));
        }

        if ($hasAho && $ahoDispCol) {
            $ahTerminados = DB::table($tblAho)
                ->selectRaw("COALESCE(SUM($ahoDispCol),0)")
                ->whereColumn("$tblAho.id_cliente", 'clientes.id');

            if ($ahoStatusCol || $ahoFechaFinCol) {
                $ahTerminados->where(function ($w) use ($tblAho, $ahoStatusCol, $ahoFechaFinCol, $today) {
                    if ($ahoStatusCol)   $w->orWhere("$tblAho.$ahoStatusCol", 6);
                    if ($ahoFechaFinCol) $w->orWhereDate("$tblAho.$ahoFechaFinCol", '<=', $today);
                });
            } else {
                $ahTerminados->whereRaw('1=0');
            }

            $query->addSelect(['sd_ahorros' => $ahTerminados]);
        } else {
            $query->addSelect(DB::raw('0 as sd_ahorros'));
        }

        if ($hasInv) {
            $exprInv =
                "COALESCE(SUM((" .
                ($invPrincipalCol ?: '0') . " + " .
                ($invRendCol      ?: '0') . " - " .
                ($invRet1Col      ?: '0') . " - " .
                ($invRet2Col      ?: '0') .
                ")),0)";

            $invActivas = DB::table($tblInv)
                ->selectRaw($exprInv)
                ->whereColumn("$tblInv.id_cliente", 'clientes.id');

            if ($invStatusCol) $invActivas->where("$tblInv.$invStatusCol", 1);

            $query->addSelect(['sd_inversiones' => $invActivas]);
        } else {
            $query->addSelect(DB::raw('0 as sd_inversiones'));
        }

        if ($hasDep && $depMontoCol) {
            $depNumExpr = "CAST(REPLACE(REPLACE($tblDep.$depMontoCol, ',', ''), '$','') AS DECIMAL(18,2))";
            $depSumExpr = "COALESCE(SUM($depNumExpr),0)";

            $depAprob1 = DB::table($tblDep)->selectRaw($depSumExpr)
                ->whereColumn("$tblDep.id_cliente", 'clientes.id');
            if ($depStatusCol) $depAprob1->where("$tblDep.$depStatusCol", 1);
            $query->addSelect(['sd_depositos' => $depAprob1]);

            $depAprob2 = DB::table($tblDep)->selectRaw($depSumExpr)
                ->whereColumn("$tblDep.id_cliente", 'clientes.id');
            if ($depStatusCol) $depAprob2->where("$tblDep.$depStatusCol", 1);
            $query->addSelect(['dep_total' => $depAprob2]);
        } else {
            $query->addSelect(DB::raw('0 as sd_depositos'))
                  ->addSelect(DB::raw('0 as dep_total'));
        }

        $clientes = $query->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('user_data.index', compact('clientes', 'search'));
    }

    public function form(Cliente $cliente)
    {
        $tab = request('tab', 'general');

        // puede venir null
        $existing = $cliente->userData;

        // ✅ Fix: si entra a laborales y aún no existe user_data, lo creamos para tener ID
        if (!$existing && $tab === 'laborales') {
            $existing = $cliente->userData()->create([
                'id_cliente' => $cliente->id,
                'id_usuario' => Auth::id(),
                'status'     => 1,
                'fecha_alta' => now(),
            ]);
        }

        // para el view: si existe úsalo, si no crea modelo "nuevo" (no guardado)
        $userData = $existing ?? new UserData(['id_cliente' => $cliente->id]);

        $estados = Estado::where('status', 1)->orderBy('nombre')->pluck('nombre', 'id');

        $selectedEstado = old('id_estado', $userData->id_estado);

        $municipios = $selectedEstado
            ? Municipio::where('id_estado', $selectedEstado)->orderBy('nombre')->pluck('nombre' , 'id')
            : collect();

        $empresas = Empresa::select('id','nombre','direccion','telefono')->get();

        return view('user_data.form', compact(
            'cliente', 'userData',
            'estados', 'municipios',
            'empresas'
        ));
    }

    public function save(Request $request, Cliente $cliente)
    {
        $activeTab = $request->input('tab', 'general');

        $rules = [
            'id_estado'                => 'nullable|exists:estados,id',
            'id_municipio'             => 'nullable|exists:municipios,id',
            'rfc'                      => 'nullable|string|max:255',
            'direccion'                => 'nullable|string|max:255',
            'colonia'                  => 'nullable|string|max:255',
            'cp'                       => 'nullable|string|max:20',
            'beneficiario'             => 'nullable|string|max:255',
            'beneficiario_telefono'    => 'nullable|string|max:50',
            'beneficiario_02'          => 'nullable|string|max:255',
            'beneficiario_telefono_02' => 'nullable|string|max:50',
            'banco'                    => 'nullable|string|max:255',
            'cuenta'                   => 'nullable|string|max:255',
            'nip'                      => 'nullable|string|min:4|max:4',
            'fecha_alta'               => 'nullable|date',
            'fecha_modificacion'       => 'nullable|date',
            'status'                   => 'nullable|in:0,1',
            'porcentaje_1'             => 'nullable|numeric|min:0|max:100',
            'porcentaje_2'             => 'nullable|numeric|min:0|max:100',
            'fecha_ingreso'            => 'nullable|date',
        ];

        if ($activeTab === 'acceso') {
            $rules['pass'] = 'required|string|min:8|confirmed';
        } else {
            $rules['pass'] = 'nullable|string|min:8|confirmed';
        }

        $data = $request->validate($rules);

        if ($activeTab === 'beneficiarios') {
            $p1 = (float) $request->input('porcentaje_1', 0);
            $p2 = (float) $request->input('porcentaje_2', 0);
            if (($p1 + $p2) !== 100) {
                return back()
                    ->withErrors(['porcentaje_2' => 'La suma de porcentajes debe ser 100 %.'])
                    ->withInput();
            }
        }

        if ($activeTab === 'acceso' && $request->filled('pass')) {
            $cliente->pass = Hash::make($request->input('pass'));
            $cliente->save();
        }

        unset($data['pass'], $data['pass_confirmation']);

        $data['id_usuario'] = Auth::id();

        $cliente->userData()->updateOrCreate(
            ['id_cliente' => $cliente->id],
            $data
        );

        try {
            $cliente->loadMissing('userData');

            $tabLabels = [
                'datos' => 'Datos de cliente',
                'beneficiarios' => 'Beneficiarios',
                'acceso' => 'Acceso',
                'laborales' => 'Datos laborales',
            ];
            $seccion = $tabLabels[$activeTab] ?? 'Datos de cliente';
            $actor = 'admin';

            Mail::to('admingrowcap@casabarrel.com')
                ->send(new UserDataActualizadaAdminMail($cliente, $seccion, $actor, $activeTab));

            if (!empty($cliente->email)) {
                Mail::to($cliente->email)
                    ->send(new UserDataActualizadaClienteMail($cliente, $seccion, $actor));
            }

            $clienteNombre = trim(sprintf('%s %s', (string)($cliente->nombre ?? ''), (string)($cliente->apellido ?? '')));
            $titulo = 'Datos del cliente actualizados';
            $mensaje = $clienteNombre !== ''
                ? "Un administrador actualizó datos del cliente {$clienteNombre}."
                : 'Un administrador actualizó datos de un cliente.';
            $url = route('clientes.datos.form', ['cliente' => $cliente->id, 'tab' => $activeTab]);

            User::role(['admin', 'gerente'])->each(function (User $admin) use ($titulo, $mensaje, $url) {
                $admin->notify(new NuevaSolicitudNotification($titulo, $mensaje, $url));
            });
        } catch (\Throwable $e) {
            Log::error('Error enviando correo/notificacion de actualización de datos (admin)', [
                'cliente_id' => $cliente->id ?? null,
                'ex' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('clientes.datos.form', ['cliente' => $cliente, 'tab' => $activeTab])
            ->with('success', 'Datos guardados correctamente.');
    }
}
