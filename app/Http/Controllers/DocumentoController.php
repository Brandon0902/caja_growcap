<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\UserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentoController extends Controller
{
    public function __construct()
    {
        // Permitir pasar si tiene ver O ver_asignadas
        $readPerms = 'documentos.ver|documentos.ver_asignadas';
        $this->middleware("permission:$readPerms")->only(['index','show','view','create','store','destroyField']);
    }

    /* -------------------------- Helpers de alcance -------------------------- */

    /** IDs de sucursales asignadas al usuario (pivote + principal). */
    protected function getUserSucursalIds(): array
    {
        $u = Auth::user();
        $ids = DB::table('usuario_sucursal_acceso')
            ->where('usuario_id', $u->id_usuario ?? $u->id)
            ->pluck('id_sucursal')
            ->filter()
            ->map(fn($x)=>(int)$x)
            ->all();

        if (!empty($u->id_sucursal)) {
            $ids[] = (int)$u->id_sucursal;
        }
        return array_values(array_unique($ids));
    }

    /** ¿Debo limitar por sucursal en este módulo? (si tiene ver_asignadas, siempre limita) */
    protected function mustLimitBySucursal(): bool
    {
        return Auth::user()->can('documentos.ver_asignadas');
    }

    /** Aborta si el cliente asociado a $userData NO pertenece a las sucursales visibles. */
    protected function assertVisibleUserData(UserData $userData): void
    {
        if (! $this->mustLimitBySucursal()) return;

        $ids = $this->getUserSucursalIds();
        $suc = (int) ($userData->cliente->id_sucursal ?? $userData->cliente()->value('id_sucursal') ?? -1);

        if (!in_array($suc, !empty($ids) ? $ids : [-1], true)) {
            abort(403, 'No tienes permiso para operar documentos de este cliente.');
        }
    }

    /* =============================== 1) Listado =============================== */

    // Muestra listado de clientes (user_data)
    public function index()
    {
        $q = UserData::query()
            ->with(['cliente:id,nombre,apellido,email,id_sucursal'])
            ->orderByDesc('id');

        if ($this->mustLimitBySucursal()) {
            $ids = $this->getUserSucursalIds();
            $q->whereHas('cliente', function ($qc) use ($ids) {
                $qc->whereIn('id_sucursal', !empty($ids) ? $ids : [-1]);
            });
        }

        $users = $q->paginate(15)->withQueryString();

        return view('documentos.index', compact('users'));
    }

    /* ======================= 2) Documentos de un cliente ====================== */

    // Muestra los documentos de un cliente
    public function show(UserData $userData)
    {
        $userData->loadMissing('cliente:id,id_sucursal,nombre,apellido,email');
        $this->assertVisibleUserData($userData);

        $doc = $userData->documento;  // puede ser null
        return view('documentos.show', compact('userData','doc'));
    }

    /* ================= Eliminar un archivo concreto del cliente =============== */

    public function destroyField(UserData $userData, $field)
    {
        $userData->loadMissing('cliente:id,id_sucursal');
        $this->assertVisibleUserData($userData);

        $allowed = [
            'documento_01','documento_02','documento_02_02',
            'documento_03','documento_04','documento_05'
        ];
        if (! in_array($field, $allowed, true)) {
            abort(404);
        }

        $doc = $userData->documento;
        if ($doc && $doc->$field) {
            Storage::disk('documentos')->delete($doc->$field);
            $doc->$field = null;
            $doc->save();
        }

        return back()->with('success','Documento eliminado correctamente.');
    }

    /* =========================== 4) Formulario subida ========================= */

    public function create(UserData $userData)
    {
        $userData->loadMissing('cliente:id,id_sucursal');
        $this->assertVisibleUserData($userData);

        $doc = $userData->documento ?: new Documento([
            'id_cliente' => $userData->id_cliente,
            'id_usuario' => Auth::id(),
        ]);

        return view('documentos.create', compact('userData','doc'));
    }

    /* ===================== 5) Guardar documentos subidos ===================== */

    public function store(Request $request, UserData $userData)
    {
        $userData->loadMissing('cliente:id,id_sucursal');
        $this->assertVisibleUserData($userData);

        // (1) Validar los archivos y la fecha opcional
        $data = $request->validate([
            'documento_01'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_02'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_02_02' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_03'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_04'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'documento_05'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'fecha'           => 'nullable|date',
        ]);

        Log::debug('DOC STORE INICIO', ['userData_id_cliente' => $userData->id_cliente]);

        $doc = Documento::firstOrNew(
            ['id_cliente' => $userData->id_cliente],
            ['id_usuario' => Auth::id()]
        );
        Log::debug('Documento instanciado', ['doc' => $doc->toArray()]);

        foreach ([
            'documento_01','documento_02','documento_02_02',
            'documento_03','documento_04','documento_05'
        ] as $field) {
            if ($request->hasFile($field)) {
                Log::debug("Recibido archivo en campo {$field}");
                if ($doc->$field) {
                    Storage::disk('documentos')->delete($doc->$field);
                    Log::debug("Borrado anterior de {$field}", ['old' => $doc->$field]);
                }
                $path = $request->file($field)->store('', 'documentos');
                Log::debug("Guardado {$field} en disco", ['path' => $path]);
                $doc->$field = $path;
            }
        }

        if (! empty($data['fecha'])) {
            $doc->fecha = $data['fecha'];
        }

        $doc->id_cliente = $userData->id_cliente;
        $doc->id_usuario = Auth::id();

        $saved = $doc->save();
        Log::debug('DOC STORE FIN', ['saved' => $saved, 'doc' => $doc->toArray()]);

        return redirect()
            ->route('documentos.show', $userData)
            ->with('success', 'Documentos subidos correctamente.');
    }

    /* ====================== 6) Ver (inline) un documento ====================== */

    public function view(UserData $userData, string $field)
    {
        $userData->loadMissing('cliente:id,id_sucursal');
        $this->assertVisibleUserData($userData);

        $doc = $userData->documento;
        abort_unless($doc && $doc->{$field}, 404);

        return Storage::disk('documentos')->response($doc->{$field}); // Content-Disposition: inline
    }
}
