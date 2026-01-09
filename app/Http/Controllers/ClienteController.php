<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\VisibilityScope;

// ✅ NUEVO
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ClienteEmailActualizadoMail;

class ClienteController extends Controller
{
    private const VIEW = 'adminclientes.'; // Carpeta de vistas

    public function __construct()
    {
        $this->middleware('permission:clientes.ver|clientes.ver_todas|clientes.ver_sucursal|clientes.ver_asignadas')
             ->only(['index','show']);

        $this->middleware('permission:clientes.crear')->only(['create','store']);
        $this->middleware('permission:clientes.editar')->only(['edit','update']);
        $this->middleware('permission:clientes.eliminar')->only(['destroy']);
    }

    private function canChooseBranch($u): bool
    {
        return $u->hasRole('admin') || $u->can('clientes.cambiar_sucursal');
    }

    private function sucursalesDisponibles($u)
    {
        if ($u->hasRole('admin')) {
            return Sucursal::orderBy('nombre')->get(['id_sucursal','nombre']);
        }

        if ($this->canChooseBranch($u)) {
            if ($u->can('clientes.ver_asignadas')) {
                $ids = DB::table('usuario_sucursal_acceso')
                    ->where('usuario_id', $u->id_usuario ?? $u->id)
                    ->pluck('id_sucursal');

                if (!empty($u->id_sucursal)) {
                    $ids->push((int) $u->id_sucursal);
                }

                $ids = $ids->unique()->values();
                return Sucursal::whereIn('id_sucursal', $ids)->orderBy('nombre')->get(['id_sucursal','nombre']);
            }

            if ($u->can('clientes.ver_sucursal')) {
                $own = (int) ($u->id_sucursal ?? 0);
                return $own
                    ? Sucursal::where('id_sucursal', $own)->get(['id_sucursal','nombre'])
                    : collect();
            }

            return Sucursal::orderBy('nombre')->get(['id_sucursal','nombre']);
        }

        return collect();
    }

    public function index(Request $request)
    {
        $u = auth()->user();

        $q = Cliente::query();
        $q = VisibilityScope::clientes($q, $u);

        if ($s = trim($request->get('search', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('nombre', 'like', "%{$s}%")
                  ->orWhere('apellido', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('codigo_cliente', 'like', "%{$s}%")
                  ->orWhere('tipo', 'like', "%{$s}%");
            });
        }

        $clientes = $q->orderByDesc('id')->paginate(15)->withQueryString();

        return view(self::VIEW.'index', compact('clientes'));
    }

    public function create()
    {
        $u = auth()->user();
        $sucursales = $this->sucursalesDisponibles($u);

        return view(self::VIEW.'create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $u = auth()->user();
        $canChoose = $this->canChooseBranch($u);

        $rules = [
            'codigo_cliente' => 'nullable|string|max:8|unique:clientes,codigo_cliente',
            'nombre'         => 'required|string|max:255',
            'apellido'       => 'nullable|string|max:255',
            'email'          => 'required|email|max:255|unique:clientes,email',
            'telefono'       => 'nullable|string|max:255',
            'user'           => 'required|string|max:255|unique:clientes,user',
            'pass'           => 'required|string|min:8|confirmed',
            'tipo'           => 'nullable|string|max:255',
            'fecha'          => 'nullable|date',
        ];

        if ($canChoose) {
            $rules['id_sucursal'] = 'required|integer|exists:sucursales,id_sucursal';
        }

        $data = $request->validate($rules);

        $data['id_sucursal'] = $canChoose
            ? (int) $request->integer('id_sucursal')
            : (int) ($u->id_sucursal);

        $data['id_usuario'] = $u->id_usuario ?? $u->id;
        $data['status']     = 1;
        $data['fecha_edit'] = now();
        $data['pass']       = Hash::make($data['pass']);

        Cliente::create($data);

        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }

    public function show(string $id)
    {
        $q = Cliente::query();
        $q = VisibilityScope::clientes($q, auth()->user());

        $cliente = $q->where('id', $id)->firstOrFail();

        return view(self::VIEW.'show', compact('cliente'));
    }

    public function edit(string $id)
    {
        $q = Cliente::query();
        $q = VisibilityScope::clientes($q, auth()->user());

        $cliente = $q->where('id', $id)->firstOrFail();

        $u = auth()->user();
        $sucursales = $this->sucursalesDisponibles($u);

        return view(self::VIEW.'edit', compact('cliente','sucursales'));
    }

    public function update(Request $request, string $id)
    {
        $q = Cliente::query();
        $q = VisibilityScope::clientes($q, auth()->user());

        $cliente = $q->where('id', $id)->firstOrFail();

        $u = auth()->user();
        $canChoose = $this->canChooseBranch($u);

        // ✅ Guardar email anterior para comparar y para el mail
        $oldEmail = (string)($cliente->email ?? '');

        $rules = [
            'codigo_cliente' => 'nullable|string|max:8|unique:clientes,codigo_cliente,' . $cliente->id,
            'id_superior'    => 'nullable|integer',
            'id_padre'       => 'nullable|integer',
            'nombre'         => 'required|string|max:255',
            'apellido'       => 'nullable|string|max:255',
            'telefono'       => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255|unique:clientes,email,' . $cliente->id,
            'user'           => 'nullable|string|max:255|unique:clientes,user,' . $cliente->id,
            'pass'           => 'nullable|string|min:8|confirmed',
            'tipo'           => 'nullable|string|max:255',
            'fecha'          => 'nullable|date',
            'status'         => 'required|in:0,1',
        ];

        if ($canChoose) {
            $rules['id_sucursal'] = 'required|integer|exists:sucursales,id_sucursal';
        }

        $data = $request->validate($rules);

        if (!empty($data['pass'])) { $data['pass'] = Hash::make($data['pass']); }
        else                       { unset($data['pass']); }

        $data['id_sucursal'] = ($canChoose && $request->filled('id_sucursal'))
            ? (int) $request->id_sucursal
            : (int) $cliente->id_sucursal;

        $data['id_usuario'] = $u->id_usuario ?? $u->id;
        $data['fecha_edit'] = now();

        $cliente->update($data);
        $cliente->refresh();

        // ✅ Si se presionó el botón de "enviar correo"
        if ($request->has('notify_email_change')) {
            $newEmail = (string)($cliente->email ?? '');

            // Solo enviamos si hay nuevo email y cambió
            if ($newEmail !== '' && mb_strtolower($newEmail) !== mb_strtolower($oldEmail)) {
                try {
                    Mail::to($newEmail)->send(new ClienteEmailActualizadoMail($cliente, $oldEmail));

                    return redirect()
                        ->route('clientes.index')
                        ->with('success', 'Cliente actualizado y correo enviado al nuevo email.');
                } catch (\Throwable $e) {
                    Log::error('Error enviando correo de email actualizado a cliente', [
                        'cliente_id' => $cliente->id ?? null,
                        'old_email'  => $oldEmail,
                        'new_email'  => $newEmail,
                        'ex'         => $e->getMessage(),
                    ]);

                    return redirect()
                        ->route('clientes.index')
                        ->with('warning', 'Cliente actualizado, pero hubo un error al enviar el correo.');
                }
            }

            // Si no cambió o quedó vacío
            return redirect()
                ->route('clientes.index')
                ->with('warning', 'Cliente actualizado, pero no se envió correo porque el email no cambió o está vacío.');
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(string $id)
    {
        $q = Cliente::query();
        $q = VisibilityScope::clientes($q, auth()->user());

        $cliente = $q->where('id', $id)->firstOrFail();

        $u = auth()->user();
        $cliente->update([
            'status'     => 0,
            'id_usuario' => $u->id_usuario ?? $u->id,
            'fecha_edit' => now(),
        ]);

        return redirect()->route('clientes.index')->with('success', 'Cliente desactivado correctamente.');
    }
}
