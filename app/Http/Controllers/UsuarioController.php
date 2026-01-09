<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sucursal;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $roles = ['admin', 'cobrador', 'contador', 'gerente', 'otro'];

        $usuarios = User::when($request->filterRol, fn($q, $r) =>
                            $q->where('rol', $r)
                        )
                        ->when($request->search, fn($q, $s) =>
                            $q->where(fn($q2) =>
                                $q2->where('name', 'like', "%{$s}%")
                                   ->orWhere('email', 'like', "%{$s}%")
                            )
                        )
                        ->orderBy('name')
                        ->paginate(15)
                        ->appends($request->only('search', 'filterRol'));

        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    public function create()
    {
        $roles = ['admin', 'cobrador', 'contador', 'gerente', 'otro'];

        // Listas para asignación múltiple
        $sucursales = Sucursal::orderBy('nombre')
            ->get(['id_sucursal', 'nombre']);

        $cajas = Caja::orderBy('nombre')
            ->get(['id_caja', 'nombre']);

        return view('usuarios.create', compact('roles', 'sucursales', 'cajas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            // OJO: la tabla es "usuarios"
            'email'          => 'required|email|max:255|unique:usuarios,email',
            'password'       => 'required|string|min:8|confirmed',
            'rol'            => 'required|string|max:50',
            'activo'         => 'required|boolean',
            'fecha_creacion' => 'required|date',

            // Asignaciones múltiples
            'sucursales'     => 'nullable|array',
            'sucursales.*'   => 'integer|exists:sucursales,id_sucursal',
            'cajas'          => 'nullable|array',
            'cajas.*'        => 'integer|exists:cajas,id_caja',

            // (Opcional) Sucursal principal (columna id_sucursal en usuarios)
            'sucursal_principal' => 'sometimes|nullable|integer|exists:sucursales,id_sucursal',
        ]);

        // Hash de password (aunque el modelo tenga cast hashed, esto es seguro)
        $data['password'] = Hash::make($data['password']);

        // ✅ Sucursal principal: si viene vacío (""), guardamos null
        $data['id_sucursal'] = $request->filled('sucursal_principal')
            ? (int) $request->input('sucursal_principal')
            : null;

        unset($data['sucursal_principal']);

        // Creamos usuario
        $usuario = User::create($data);

        // Sincronizar pivotes (agregamos acceso_activo=1)
        $sucursalesIds = collect($request->input('sucursales', []))
            ->filter()->mapWithKeys(fn($id) => [$id => ['acceso_activo' => 1]])->all();

        $cajasIds = collect($request->input('cajas', []))
            ->filter()->mapWithKeys(fn($id) => [$id => ['acceso_activo' => 1]])->all();

        $usuario->sucursalesConAcceso()->sync($sucursalesIds);
        $usuario->cajasConAcceso()->sync($cajasIds);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function show($id)
    {
        $usuario = User::with(['sucursalesConAcceso', 'cajasConAcceso'])->findOrFail($id);
        return view('usuarios.show', compact('usuario'));
    }

    public function edit($id)
    {
        $usuario = User::with([
            'sucursalesConAcceso:id_sucursal,nombre',
            'cajasConAcceso:id_caja,nombre'
        ])->findOrFail($id);

        $roles = ['admin', 'cobrador', 'contador', 'gerente', 'otro'];

        $sucursales = Sucursal::orderBy('nombre')
            ->get(['id_sucursal', 'nombre']);

        $cajas = Caja::orderBy('nombre')
            ->get(['id_caja', 'nombre']);

        // Para checks preseleccionados
        $sucursalesChecked = $usuario->sucursalesConAcceso->pluck('id_sucursal')->map(fn($v)=>(int)$v)->all();
        $cajasChecked = $usuario->cajasConAcceso->pluck('id_caja')->map(fn($v)=>(int)$v)->all();

        return view('usuarios.edit', compact('usuario', 'roles', 'sucursales', 'cajas', 'sucursalesChecked', 'cajasChecked'));
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            // unique:usuarios,email,<id>,id_usuario
            'email'          => "required|email|max:255|unique:usuarios,email,{$usuario->id_usuario},id_usuario",
            'password'       => 'nullable|string|min:8|confirmed',
            'rol'            => 'required|string|max:50',
            'activo'         => 'required|boolean',
            'fecha_creacion' => 'required|date',

            'sucursales'     => 'nullable|array',
            'sucursales.*'   => 'integer|exists:sucursales,id_sucursal',
            'cajas'          => 'nullable|array',
            'cajas.*'        => 'integer|exists:cajas,id_caja',

            // ✅ Recomendado: sometimes para que si no viene el campo, no lo fuerce
            'sucursal_principal' => 'sometimes|nullable|integer|exists:sucursales,id_sucursal',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // ✅ Si el select llega con "" => null. Si llega con número => int. Si no llega => conserva.
        if ($request->has('sucursal_principal')) {
            $data['id_sucursal'] = $request->filled('sucursal_principal')
                ? (int) $request->input('sucursal_principal')
                : null;
        } else {
            $data['id_sucursal'] = $usuario->id_sucursal;
        }

        unset($data['sucursal_principal']);

        $usuario->update($data);

        // Sync pivotes
        $sucursalesIds = collect($request->input('sucursales', []))
            ->filter()->mapWithKeys(fn($id) => [$id => ['acceso_activo' => 1]])->all();

        $cajasIds = collect($request->input('cajas', []))
            ->filter()->mapWithKeys(fn($id) => [$id => ['acceso_activo' => 1]])->all();

        $usuario->sucursalesConAcceso()->sync($sucursalesIds);
        $usuario->cajasConAcceso()->sync($cajasIds);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->update(['activo' => false]);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario desactivado correctamente.');
    }

    public function toggle(User $usuario)
    {
        $usuario->update(['activo' => ! $usuario->activo]);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Estado del usuario actualizado correctamente.');
    }
}
