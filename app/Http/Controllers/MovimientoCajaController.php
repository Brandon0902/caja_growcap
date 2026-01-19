<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCaja;
use App\Models\Caja;
use App\Models\CategoriaIngreso;
use App\Models\SubcategoriaIngreso;
use App\Models\CategoriaGasto;
use App\Models\SubcategoriaGasto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Services\VisibilityScope;
use App\Services\OperacionRecipientsService;

use App\Mail\MovimientoCajaNotificacionMail;
use App\Mail\EntradaDineroAdminMail;
use App\Mail\SalidaDineroAdminMail;

class MovimientoCajaController extends Controller
{
    public function index()
    {
        $u = Auth::user();

        $q = MovimientoCaja::with([
            'caja:id_caja,nombre,id_sucursal',
            'usuario:id_usuario,name,email',
            'categoriaIngreso:id_cat_ing,nombre',
            // ✅ id correcto
            'subcategoriaIngreso:id_sub_ing,nombre',
            'categoriaGasto:id_cat_gasto,nombre',
            'subcategoriaGasto:id_sub_gasto,nombre',
            'proveedor:id_proveedor,nombre',
        ]);

        $q = VisibilityScope::movimientos($q, $u);

        if ($s = trim(request('search', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('descripcion', 'like', "%{$s}%")
                  ->orWhereHas('caja', fn($cq) => $cq->where('nombre', 'like', "%{$s}%"));
            });
        }

        $movimientos = $q->orderBy('fecha', 'desc')->paginate(20)->withQueryString();

        return view('movimientos-caja.index', compact('movimientos'));
    }

    public function create()
    {
        $cajas = $this->cajasDisponibles();

        $catsIngreso = CategoriaIngreso::orderBy('nombre')->get();
        $subsIngreso = SubcategoriaIngreso::orderBy('nombre')->get();
        $catsGasto   = CategoriaGasto::orderBy('nombre')->get();
        $subsGasto   = SubcategoriaGasto::orderBy('nombre')->get();

        $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre')->get();

        return view('movimientos-caja.create', compact(
            'cajas', 'catsIngreso', 'subsIngreso', 'catsGasto', 'subsGasto', 'proveedores'
        ));
    }

    public function store(Request $request, OperacionRecipientsService $recipients)
    {
        $u = Auth::user();

        // ✅ Normaliza '' -> null antes de validar
        $this->normalizeOptionalIds($request);

        $tipoForm  = strtolower((string)$request->input('tipo_mov', ''));
        $esIngreso = ($tipoForm === 'ingreso');
        $esGasto   = in_array($tipoForm, ['gasto', 'egreso'], true);

        $data = $request->validate(
            [
                'id_caja'  => 'required|exists:cajas,id_caja',
                'tipo_mov' => 'required|in:ingreso,gasto,Ingreso,Egreso',

                'id_cat_ing' => [
                    Rule::requiredIf($esIngreso),
                    'nullable','integer','min:1',
                    Rule::exists((new CategoriaIngreso)->getTable(), 'id_cat_ing'),
                ],

                // ✅ CORRECCIÓN: exists contra id_sub_ing
                'id_sub_ing' => [
                    Rule::requiredIf($esIngreso),
                    'nullable','integer','min:1',
                    Rule::exists((new SubcategoriaIngreso)->getTable(), 'id_sub_ing'),
                ],

                'id_cat_gasto' => [
                    Rule::requiredIf($esGasto),
                    'nullable','integer','min:1',
                    Rule::exists((new CategoriaGasto)->getTable(), 'id_cat_gasto'),
                ],

                'id_sub_gasto' => [
                    Rule::requiredIf($esGasto),
                    'nullable','integer','min:1',
                    Rule::exists((new SubcategoriaGasto)->getTable(), 'id_sub_gasto'),
                ],

                'proveedor_id' => [
                    'nullable','integer','min:1',
                    Rule::exists((new Proveedor)->getTable(), 'id_proveedor'),
                ],

                'origen_id'   => ['nullable', 'integer', 'min:1'],
                'monto'       => 'required|numeric|min:0.01',
                'fecha'       => 'required|date',
                'descripcion' => 'nullable|string|max:500',
            ],
            [
                'id_caja.required' => 'Selecciona una caja.',
                'id_caja.exists'   => 'La caja seleccionada no es válida.',
                'tipo_mov.required' => 'Selecciona el tipo de movimiento.',
                'tipo_mov.in'       => 'El tipo de movimiento no es válido.',

                'id_cat_ing.required' => 'Para un ingreso debes seleccionar una categoría de ingreso.',
                'id_sub_ing.required' => 'Para un ingreso debes seleccionar una subcategoría de ingreso.',
                'id_cat_gasto.required' => 'Para un gasto debes seleccionar una categoría de gasto.',
                'id_sub_gasto.required' => 'Para un gasto debes seleccionar una subcategoría de gasto.',

                'id_cat_ing.exists' => 'La categoría de ingreso seleccionada no existe.',
                'id_sub_ing.exists' => 'La subcategoría de ingreso seleccionada no existe.',
                'id_cat_gasto.exists' => 'La categoría de gasto seleccionada no existe.',
                'id_sub_gasto.exists' => 'La subcategoría de gasto seleccionada no existe.',
                'proveedor_id.exists' => 'El proveedor seleccionado no existe.',

                'monto.required' => 'Captura el monto.',
                'monto.numeric'  => 'El monto debe ser numérico.',
                'monto.min'      => 'El monto debe ser mayor a 0.',
                'fecha.required' => 'Selecciona la fecha.',
                'fecha.date'     => 'La fecha no es válida.',
                'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
            ]
        );

        $caja = Caja::findOrFail($data['id_caja']);
        $this->authorizeCajaAccess($caja);

        $tipoForm  = strtolower($data['tipo_mov']);
        $esIngreso = ($tipoForm === 'ingreso');
        $data['tipo_mov']   = $esIngreso ? 'Ingreso' : 'Egreso';
        $data['id_usuario'] = Auth::id();

        $ultimoMov = $caja->movimientos()->latest('fecha')->first();
        $montoPrev = $ultimoMov ? $ultimoMov->monto_posterior : $caja->saldo_inicial;
        $montoPost = $esIngreso ? ($montoPrev + $data['monto']) : ($montoPrev - $data['monto']);

        $data['monto_anterior']  = $montoPrev;
        $data['monto_posterior'] = $montoPost;
        $data['id_sucursal']     = $caja->id_sucursal;

        // ✅ Limpieza extra (evita basura cruzada)
        if ($esIngreso) {
            $data['id_cat_gasto'] = null;
            $data['id_sub_gasto'] = null;
        } else {
            $data['id_cat_ing'] = null;
            $data['id_sub_ing'] = null;
        }

        $mov = MovimientoCaja::create($data);
        $caja->update(['saldo_final' => $montoPost]);

        // ✅ ENVIAR CORREOS (sucursal + admin)
        try {
            $actor = $u;

            $mov->load([
                'caja:id_caja,nombre,id_sucursal',
                'usuario:id_usuario,name,email',
                'categoriaIngreso:id_cat_ing,nombre',
                'subcategoriaIngreso:id_sub_ing,nombre',
                'categoriaGasto:id_cat_gasto,nombre',
                'subcategoriaGasto:id_sub_gasto,nombre',
                'proveedor:id_proveedor,nombre',
            ]);

            $sucursalId = (int) ($caja->id_sucursal ?? 0);

            if ($sucursalId > 0 && $actor) {
                // correos por sucursal (excluye actor según tu servicio)
                $to = $recipients->forSucursalAndActor($sucursalId, $actor);
                if (!empty($to)) {
                    Mail::to($to)->send(new MovimientoCajaNotificacionMail($mov, $actor, 'creado'));
                }

                // correo admin (config/services.php -> services.admin.email)
                $adminEmail = trim((string) config('services.admin.email'));
                if ($adminEmail !== '') {
                    $mailClass = $mov->tipo_mov === 'Ingreso'
                        ? new EntradaDineroAdminMail($mov, $actor, 'creado')
                        : new SalidaDineroAdminMail($mov, $actor, 'creado');

                    Mail::to($adminEmail)->send($mailClass);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar mail de movimiento caja (store): '.$e->getMessage(), [
                'mov_id' => $mov->getKey(),
            ]);
        }

        return redirect()
            ->route('movimientos-caja.index')
            ->with('success', 'Movimiento registrado correctamente.');
    }

    public function edit(MovimientoCaja $movimiento)
    {
        $this->authorizeMovimientoRecord($movimiento);

        $cajas = $this->cajasDisponibles();

        $catsIngreso = CategoriaIngreso::orderBy('nombre')->get();
        $subsIngreso = SubcategoriaIngreso::orderBy('nombre')->get();
        $catsGasto   = CategoriaGasto::orderBy('nombre')->get();
        $subsGasto   = SubcategoriaGasto::orderBy('nombre')->get();

        $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre')->get();

        return view('movimientos-caja.edit', compact(
            'movimiento', 'cajas', 'catsIngreso', 'subsIngreso', 'catsGasto', 'subsGasto', 'proveedores'
        ));
    }

    public function update(Request $request, MovimientoCaja $movimiento, OperacionRecipientsService $recipients)
    {
        $this->authorizeMovimientoRecord($movimiento);

        $this->normalizeOptionalIds($request);

        $tipoForm  = strtolower((string)$request->input('tipo_mov', ''));
        $esIngreso = ($tipoForm === 'ingreso');
        $esGasto   = in_array($tipoForm, ['gasto', 'egreso'], true);

        $data = $request->validate(
            [
                'id_caja'  => 'required|exists:cajas,id_caja',
                'tipo_mov' => 'required|in:ingreso,gasto,Ingreso,Egreso',

                'id_cat_ing' => [
                    Rule::requiredIf($esIngreso),
                    'nullable','integer','min:1',
                    Rule::exists((new CategoriaIngreso)->getTable(), 'id_cat_ing'),
                ],

                // ✅ CORRECCIÓN: exists contra id_sub_ing
                'id_sub_ing' => [
                    Rule::requiredIf($esIngreso),
                    'nullable','integer','min:1',
                    Rule::exists((new SubcategoriaIngreso)->getTable(), 'id_sub_ing'),
                ],

                'id_cat_gasto' => [
                    Rule::requiredIf($esGasto),
                    'nullable','integer','min:1',
                    Rule::exists((new CategoriaGasto)->getTable(), 'id_cat_gasto'),
                ],

                'id_sub_gasto' => [
                    Rule::requiredIf($esGasto),
                    'nullable','integer','min:1',
                    Rule::exists((new SubcategoriaGasto)->getTable(), 'id_sub_gasto'),
                ],

                'proveedor_id' => [
                    'nullable','integer','min:1',
                    Rule::exists((new Proveedor)->getTable(), 'id_proveedor'),
                ],

                'origen_id'   => ['nullable', 'integer', 'min:1'],
                'monto'       => 'required|numeric|min:0.01',
                'fecha'       => 'required|date',
                'descripcion' => 'nullable|string|max:500',
            ],
            [
                'id_cat_ing.required' => 'Para un ingreso debes seleccionar una categoría de ingreso.',
                'id_sub_ing.required' => 'Para un ingreso debes seleccionar una subcategoría de ingreso.',
                'id_cat_gasto.required' => 'Para un gasto debes seleccionar una categoría de gasto.',
                'id_sub_gasto.required' => 'Para un gasto debes seleccionar una subcategoría de gasto.',
            ]
        );

        $cajaNew = Caja::findOrFail($data['id_caja']);
        $this->authorizeCajaAccess($cajaNew);

        $tipoForm  = strtolower($data['tipo_mov']);
        $esIngreso = ($tipoForm === 'ingreso');
        $data['tipo_mov']   = $esIngreso ? 'Ingreso' : 'Egreso';
        $data['id_usuario'] = Auth::id();

        $pkName  = $movimiento->getKeyName();
        $pkValue = $movimiento->getKey();

        $ultimoMov = $cajaNew->movimientos()
            ->where($pkName, '!=', $pkValue)
            ->latest('fecha')
            ->first();

        $montoPrev = $ultimoMov ? $ultimoMov->monto_posterior : $cajaNew->saldo_inicial;
        $montoPost = $esIngreso ? ($montoPrev + $data['monto']) : ($montoPrev - $data['monto']);

        $data['monto_anterior']  = $montoPrev;
        $data['monto_posterior'] = $montoPost;
        $data['id_sucursal']     = $cajaNew->id_sucursal;

        // ✅ Limpieza extra
        if ($esIngreso) {
            $data['id_cat_gasto'] = null;
            $data['id_sub_gasto'] = null;
        } else {
            $data['id_cat_ing'] = null;
            $data['id_sub_ing'] = null;
        }

        $movimiento->update($data);
        $cajaNew->update(['saldo_final' => $montoPost]);

        // ✅ ENVIAR CORREOS (actualización) sucursal + admin
        try {
            $actor = Auth::user();

            $movimiento->load([
                'caja:id_caja,nombre,id_sucursal',
                'usuario:id_usuario,name,email',
                'categoriaIngreso:id_cat_ing,nombre',
                'subcategoriaIngreso:id_sub_ing,nombre',
                'categoriaGasto:id_cat_gasto,nombre',
                'subcategoriaGasto:id_sub_gasto,nombre',
                'proveedor:id_proveedor,nombre',
            ]);

            $sucursalId = (int) ($cajaNew->id_sucursal ?? $movimiento->id_sucursal ?? 0);

            if ($sucursalId > 0 && $actor) {
                $to = $recipients->forSucursalAndActor($sucursalId, $actor);
                if (!empty($to)) {
                    Mail::to($to)->send(new MovimientoCajaNotificacionMail($movimiento, $actor, 'actualizado'));
                }

                $adminEmail = trim((string) config('services.admin.email'));
                if ($adminEmail !== '') {
                    $mailClass = $movimiento->tipo_mov === 'Ingreso'
                        ? new EntradaDineroAdminMail($movimiento, $actor, 'actualizado')
                        : new SalidaDineroAdminMail($movimiento, $actor, 'actualizado');

                    Mail::to($adminEmail)->send($mailClass);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar mail de movimiento caja (update): '.$e->getMessage(), [
                'mov_id' => $movimiento->getKey(),
            ]);
        }

        return redirect()
            ->route('movimientos-caja.index')
            ->with('success', 'Movimiento actualizado correctamente.');
    }

    /* ===================== Helpers ===================== */

    protected function normalizeOptionalIds(Request $request): void
    {
        $keys = ['id_cat_ing','id_sub_ing','id_cat_gasto','id_sub_gasto','proveedor_id','origen_id'];

        foreach ($keys as $k) {
            $v = $request->input($k);
            if ($v === '' || (is_string($v) && trim($v) === '')) {
                $request->merge([$k => null]);
            }
        }
    }

    protected function authorizeMovimientoRecord(MovimientoCaja $mov): void
    {
        $u = Auth::user();

        if ($u->can('movimientos.ver_todos')) return;

        if ($u->can('movimientos.ver_sucursal')) {
            if ((int)$mov->id_sucursal === (int)$u->id_sucursal) return;
            abort(403, 'No puedes operar movimientos de otra sucursal.');
        }

        if ($u->can('movimientos.ver_asignados')) {
            $asignada = $u->cajasAsignadas()
                ->where('cajas.id_caja', $mov->id_caja)
                ->exists();

            if ($asignada) return;

            abort(403, 'No tienes permiso para este movimiento.');
        }

        abort(403, 'No tienes permiso para operar movimientos.');
    }

    protected function authorizeCajaAccess(Caja $caja): void
    {
        $u = Auth::user();

        if ($u->can('cajas.ver') || $u->can('cajas.ver_todas')) return;
        if ($u->can('cajas.ver_sucursal') && (int)$caja->id_sucursal === (int)$u->id_sucursal) return;
        if ($u->can('cajas.ver_asignadas') && $u->cajasAsignadas()->where('cajas.id_caja', $caja->id_caja)->exists()) return;

        abort(403, 'No tienes permiso para operar con esa caja.');
    }

    protected function cajasDisponibles()
    {
        $u = Auth::user();

        $baseQuery = Caja::query()
            ->with('sucursal:id_sucursal,nombre')
            ->orderBy('nombre');

        if ($u->can('cajas.ver') || $u->can('cajas.ver_todas')) return $baseQuery->get();
        if ($u->can('cajas.ver_sucursal')) return $baseQuery->where('id_sucursal', $u->id_sucursal)->get();

        if ($u->can('cajas.ver_asignadas')) {
            return $u->cajasAsignadas()
                ->with('sucursal:id_sucursal,nombre')
                ->orderBy('nombre')
                ->get();
        }

        return collect();
    }
}
