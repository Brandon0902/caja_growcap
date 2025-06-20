<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $roles = ['admin','cobrador','contador','gerente','otro'];

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
                            ->appends($request->only('search','filterRol'));

        return view('usuarios.index', compact('usuarios','roles'));
    }

    public function create()
    {
        $roles = ['admin','cobrador','contador','gerente','otro'];
        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|max:255|unique:usuarios,email',
            'password'        => 'required|string|min:8|confirmed',
            'rol'             => 'required|in:admin,cobrador,contador,gerente,otro',
            'activo'          => 'required|boolean',
            'fecha_creacion'  => 'required|date',
        ]);

        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function show($id)
    {
        $usuario = User::findOrFail($id);
        return view('usuarios.show', compact('usuario'));
    }

    public function edit($id)
    {
        $usuario = User::findOrFail($id);
        $roles   = ['admin','cobrador','contador','gerente','otro'];
        return view('usuarios.edit', compact('usuario','roles'));
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|max:255|unique:usuarios,email,'
                                 . $usuario->id_usuario . ',id_usuario',
            'password'        => 'nullable|string|min:8|confirmed',
            'rol'             => 'required|in:admin,cobrador,contador,gerente,otro',
            'activo'          => 'required|boolean',
            'fecha_creacion'  => 'required|date',
        ]);

        if ($data['password'] ?? false) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);

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
