<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProveedorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:proveedores.ver|proveedores.ver_asignadas')->only(['index','show']);
        $this->middleware('permission:proveedores.crear')->only(['create','store']);
        $this->middleware('permission:proveedores.editar')->only(['edit','update','toggle']);
        $this->middleware('permission:proveedores.eliminar')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = trim((string)$request->input('search', ''));
        $estado = $request->input('estado'); // 'activo' | 'inactivo' | null
        $orden  = $request->input('orden', 'recientes'); // 'recientes' | 'antiguos' | 'nombre_asc' | 'nombre_desc'

        $query = Proveedor::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('nombre', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('telefono', 'like', "%{$search}%")
                       ->orWhere('contacto', 'like', "%{$search}%")
                       ->orWhere('direccion', 'like', "%{$search}%");
                });
            })
            ->when(in_array($estado, ['activo','inactivo'], true), fn($q) => $q->where('estado', $estado));

        $query = match ($orden) {
            'antiguos'     => $query->orderBy('id_proveedor', 'asc'),
            'nombre_asc'   => $query->orderBy('nombre', 'asc'),
            'nombre_desc'  => $query->orderBy('nombre', 'desc'),
            default        => $query->orderBy('id_proveedor', 'desc'),
        };

        $proveedores = $query->paginate(15)->withQueryString();

        return view('proveedores.index', [
            'proveedores' => $proveedores,
            'search'      => $search,
            'estado'      => $estado,
            'orden'       => $orden,
        ]);
    }

    public function create()
    {
        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        $u = Auth::user();

        $data = $request->validate([
            'nombre'    => 'required|string|max:255',
            'email'     => 'nullable|email|max:255',
            'telefono'  => 'nullable|string|max:50',
            'contacto'  => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'estado'    => 'required|in:activo,inactivo',
        ]);

        // Auditoría por usuario (solo si existe la columna)
        if (\Illuminate\Support\Facades\Schema::hasColumn('proveedores', 'id_usuario')) {
            $data['id_usuario'] = $u->id_usuario ?? $u->id;
        }

        $proveedor = Proveedor::create($data);

        if ($request->filled('back')) {
            return redirect($request->input('back'))
                ->with('nuevo_proveedor_id', $proveedor->id_proveedor)
                ->with('success', 'Proveedor creado correctamente.');
        }

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    public function show(string $id)
    {
        $proveedor = Proveedor::query()
            ->whereKey($id)
            ->firstOrFail();

        return view('proveedores.show', ['proveedor' => $proveedor]);
    }

    public function edit(string $id)
    {
        $proveedor = Proveedor::query()
            ->whereKey($id)
            ->firstOrFail();

        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, string $id)
    {
        $u = Auth::user();

        $proveedor = Proveedor::query()
            ->whereKey($id)
            ->firstOrFail();

        $data = $request->validate([
            'nombre'    => 'required|string|max:255',
            'email'     => 'nullable|email|max:255',
            'telefono'  => 'nullable|string|max:50',
            'contacto'  => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'estado'    => 'required|in:activo,inactivo',
        ]);

        if (\Illuminate\Support\Facades\Schema::hasColumn('proveedores', 'id_usuario')) {
            $data['id_usuario'] = $u->id_usuario ?? $u->id;
        }

        $proveedor->update($data);

        if ($request->filled('back')) {
            return redirect($request->input('back'))
                ->with('nuevo_proveedor_id', $proveedor->id_proveedor)
                ->with('success', 'Proveedor actualizado correctamente.');
        }

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function toggle(string $id)
    {
        $proveedor = Proveedor::query()
            ->whereKey($id)
            ->firstOrFail();

        $proveedor->update([
            'estado' => $proveedor->estado === 'activo' ? 'inactivo' : 'activo',
        ]);

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy(string $id)
    {
        $proveedor = Proveedor::query()
            ->whereKey($id)
            ->firstOrFail();

        $proveedor->delete();

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }
}
