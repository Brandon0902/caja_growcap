<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Services\OperacionRecipientsService;
use App\Mail\TransaccionCajasNotificacionMail;
use App\Mail\MovimientoDineroAdminMail;

class GastoController extends Controller
{
    public function index()
    {
        $gastos = Gasto::with('cajaOrigen', 'cajaDestino')
            ->latest()
            ->paginate(15);

        return view('gastos.index', compact('gastos'));
    }

    public function create()
    {
        // ✅ para que en la vista salga la sucursal (optional($c->sucursal)->nombre)
        $cajas = Caja::with('sucursal')->orderBy('nombre')->get();

        return view('gastos.create', compact('cajas'));
    }

    public function store(Request $request, OperacionRecipientsService $recipients)
    {
        $data = $request->validate([
            'caja_id'         => 'required|exists:cajas,id_caja',
            'destino_caja_id' => 'nullable|exists:cajas,id_caja',
            'tipo'            => 'required|string|max:50',
            'cantidad'        => 'required|numeric|min:0',
            'concepto'        => 'nullable|string',
            'comprobante'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        if ($request->hasFile('comprobante')) {
            $data['comprobante'] = $request->file('comprobante')->store('', 'gastos');
        }

        $gasto = Gasto::create($data);

        // ✅ Notificar: actor + MAIL_FROM_ADDRESS + (gerente/admin de la sucursal)
        try {
            $actor = Auth::user();

            $gasto->load(['cajaOrigen.sucursal', 'cajaDestino.sucursal']);

            $sucursalId = (int) ($gasto->cajaOrigen?->id_sucursal ?? 0);

            if ($sucursalId > 0 && $actor) {
                $to = $recipients->forSucursalAndActor($sucursalId, $actor);

                if (!empty($to)) {
                    Mail::to($to)->send(new TransaccionCajasNotificacionMail($gasto, $actor, 'creada'));
                }

                $adminEmail = trim((string) config('services.admin.email'));
                if ($adminEmail !== '') {
                    Mail::to($adminEmail)->send(new MovimientoDineroAdminMail($gasto, $actor, 'creada'));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar mail de transacción entre cajas (store): '.$e->getMessage(), [
                'gasto_id' => $gasto->id ?? null,
            ]);
        }

        return redirect()
            ->route('gastos.index')
            ->with('success', 'Gasto creado correctamente.');
    }

    public function show(Gasto $gasto)
    {
        $gasto->load('cajaOrigen', 'cajaDestino');

        return view('gastos.show', compact('gasto'));
    }

    public function edit(Gasto $gasto)
    {
        $cajas = Caja::with('sucursal')->orderBy('nombre')->get();

        return view('gastos.edit', compact('gasto', 'cajas'));
    }

    public function update(Request $request, Gasto $gasto, OperacionRecipientsService $recipients)
    {
        $data = $request->validate([
            'caja_id'         => 'required|exists:cajas,id_caja',
            'destino_caja_id' => 'nullable|exists:cajas,id_caja',
            'tipo'            => 'required|string|max:50',
            'cantidad'        => 'required|numeric|min:0',
            'concepto'        => 'nullable|string',
            'comprobante'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'remove_file'     => 'nullable|boolean',
        ]);

        // ¿Eliminar el archivo actual?
        if ($request->boolean('remove_file') && $gasto->comprobante) {
            Storage::disk('gastos')->delete($gasto->comprobante);
            $data['comprobante'] = null;
        }

        // ¿Subió uno nuevo?
        if ($request->hasFile('comprobante')) {
            if ($gasto->comprobante) {
                Storage::disk('gastos')->delete($gasto->comprobante);
            }
            $data['comprobante'] = $request->file('comprobante')->store('', 'gastos');
        }

        $gasto->update($data);

        // ✅ Notificar actualización
        try {
            $actor = Auth::user();

            $gasto->load(['cajaOrigen.sucursal', 'cajaDestino.sucursal']);

            $sucursalId = (int) ($gasto->cajaOrigen?->id_sucursal ?? 0);

            if ($sucursalId > 0 && $actor) {
                $to = $recipients->forSucursalAndActor($sucursalId, $actor);

                if (!empty($to)) {
                    Mail::to($to)->send(new TransaccionCajasNotificacionMail($gasto, $actor, 'actualizada'));
                }

                $adminEmail = trim((string) config('services.admin.email'));
                if ($adminEmail !== '') {
                    Mail::to($adminEmail)->send(new MovimientoDineroAdminMail($gasto, $actor, 'actualizada'));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar mail de transacción entre cajas (update): '.$e->getMessage(), [
                'gasto_id' => $gasto->id ?? null,
            ]);
        }

        return redirect()
            ->route('gastos.index')
            ->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(Gasto $gasto, OperacionRecipientsService $recipients)
    {
        // Cargar antes de borrar para mandar el correo con la info
        $gasto->load(['cajaOrigen.sucursal', 'cajaDestino.sucursal']);

        // ✅ Notificar eliminación
        try {
            $actor = Auth::user();

            $sucursalId = (int) ($gasto->cajaOrigen?->id_sucursal ?? 0);

            if ($sucursalId > 0 && $actor) {
                $to = $recipients->forSucursalAndActor($sucursalId, $actor);

                if (!empty($to)) {
                    Mail::to($to)->send(new TransaccionCajasNotificacionMail($gasto, $actor, 'eliminada'));
                }

                $adminEmail = trim((string) config('services.admin.email'));
                if ($adminEmail !== '') {
                    Mail::to($adminEmail)->send(new MovimientoDineroAdminMail($gasto, $actor, 'eliminada'));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar mail de transacción entre cajas (destroy): '.$e->getMessage(), [
                'gasto_id' => $gasto->id ?? null,
            ]);
        }

        if ($gasto->comprobante) {
            Storage::disk('gastos')->delete($gasto->comprobante);
        }

        $gasto->delete();

        return back()->with('success', 'Gasto eliminado.');
    }

    /**
     * Ver/descargar el comprobante (inline).
     */
    public function comprobante(Gasto $gasto)
    {
        abort_unless($gasto->comprobante, 404);

        return Storage::disk('gastos')->response($gasto->comprobante);
    }
}
