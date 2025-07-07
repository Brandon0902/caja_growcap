<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\UserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentoController extends Controller
{
    // 1) Muestra listado de clientes (user_data)
    public function index()
    {
        $users = UserData::with('cliente')
                         ->orderByDesc('id')
                         ->paginate(15);

        return view('documentos.index', compact('users'));
    }

    // 2) Muestra los documentos de un cliente
    public function show(UserData $userData)
    {
        $doc = $userData->documento;  // puede ser null
        return view('documentos.show', compact('userData','doc'));
    }

    // 3) Elimina un archivo concreto de la tabla y del disco
    public function destroyField(UserData $userData, $field)
    {
        $allowed = [
          'documento_01','documento_02','documento_02_02',
          'documento_03','documento_04','documento_05'
        ];

        if (! in_array($field, $allowed)) {
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

    // 4) Formulario de subida
    public function create(UserData $userData)
    {
        $doc = $userData->documento ?: new Documento([
            'id_cliente' => $userData->id_cliente,
            'id_usuario' => Auth::id(),
        ]);

        return view('documentos.create', compact('userData','doc'));
    }

    // 5) Guarda los documentos subidos (con depuración)
    public function store(Request $request, UserData $userData)
    {
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

        // (2) Inicio de la traza de depuración
        Log::debug('STORE INICIO', ['userData_id_cliente' => $userData->id_cliente]);

        // (3) Recuperar o instanciar el Documento para este userData
        $doc = Documento::firstOrNew(
            ['id_cliente' => $userData->id_cliente],
            ['id_usuario' => Auth::id()]
        );
        Log::debug('Documento instanciado', ['doc' => $doc->toArray()]);

        // (4) Procesar cada campo de archivo
        foreach ([
            'documento_01','documento_02','documento_02_02',
            'documento_03','documento_04','documento_05'
        ] as $field) {
            if ($request->hasFile($field)) {
                Log::debug("Recibido archivo en campo {$field}");
                // Borrar anterior si existía
                if ($doc->$field) {
                    Storage::disk('documentos')->delete($doc->$field);
                    Log::debug("Borrado anterior de {$field}", ['old' => $doc->$field]);
                }
                // Guardar nuevo archivo
                $path = $request->file($field)->store('', 'documentos');
                Log::debug("Guardado {$field} en disco", ['path' => $path]);
                $doc->$field = $path;
            }
        }

        // (5) Guardar la fecha si se proporcionó
        if (! empty($data['fecha'])) {
            $doc->fecha = $data['fecha'];
        }

        // (6) Asegurar que id_cliente e id_usuario están asignados
        $doc->id_cliente = $userData->id_cliente;
        $doc->id_usuario = Auth::id();

        // (7) Persistir el registro en la base de datos
        $saved = $doc->save();
        Log::debug('STORE FIN', ['saved' => $saved, 'doc' => $doc->toArray()]);

        // (8) Redirigir de vuelta a la vista de detalles
        return redirect()
            ->route('documentos.show', $userData)
            ->with('success', 'Documentos subidos correctamente.');
    }

    public function view(UserData $userData, string $field)
    {
        $doc = $userData->documento;
        abort_unless($doc && $doc->{$field}, 404);

        // Esto envía el archivo con Content-Disposition: inline
        return Storage::disk('documentos')->response($doc->{$field});
    }
}
