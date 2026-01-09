<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

use Stripe\StripeClient;

use App\Models\UserAhorro;
use App\Models\MovimientoCaja;
use App\Models\MovimientoAhorro;
use App\Models\RetiroAhorro;
use App\Models\StripeReturnMessage;

class CleanupPendingAhorros extends Command
{
    protected $signature = 'ahorros:cleanup-pending
                            {--minutes=45 : Minutos mínimos para considerar un pending como abandonado}
                            {--limit=200 : Límite por corrida}
                            {--dry-run : No borra ni inactiva, solo muestra conteos}';

    protected $description = 'Elimina/Inactiva ahorros pendientes (status=0) abandonados y cancela suscripción Stripe si aplica.';

    private function stripe(): ?StripeClient
    {
        $secretKey = config('services.stripe.secret');
        if (!$secretKey) return null;
        return new StripeClient($secretKey);
    }

    private function cancelStripeSubscriptionSafely(?StripeClient $stripe, ?string $subId): void
    {
        $subId = trim((string)$subId);
        if ($subId === '' || !$stripe) return;

        try {
            $stripe->subscriptions->cancel($subId, []);
        } catch (\Throwable $e) {
            Log::warning('cleanup-pending: cancel sub failed', [
                'sub' => $subId,
                'err' => $e->getMessage(),
            ]);
        }
    }

    private function saveReturnMessage(array $data): void
    {
        try {
            StripeReturnMessage::create([
                'tipo'             => $data['tipo'] ?? 'unknown',
                'entity_id'         => $data['entity_id'] ?? null,
                'user_id'           => $data['user_id'] ?? null,
                'session_id'        => $data['session_id'] ?? null,
                'payment_intent_id' => $data['payment_intent_id'] ?? null,
                'status'            => $data['status'] ?? 'warning',
                'message'           => $data['message'] ?? '',
                'seen'              => 0,
            ]);
        } catch (\Throwable $e) {
            Log::warning('cleanup-pending: StripeReturnMessage failed', ['err' => $e->getMessage()]);
        }
    }

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $limit   = (int) $this->option('limit');
        $dryRun  = (bool) $this->option('dry-run');

        $tz   = config('app.timezone', 'UTC');
        $cut  = Carbon::now($tz)->subMinutes(max(5, $minutes)); // mínimo 5 min para evitar accidentes

        $stripe = $this->stripe();

        $pendientes = UserAhorro::query()
            ->where('status', 0)
            ->where(function ($q) use ($cut) {
                // Ajusta el campo de "creación" al que tengas (created_at o fecha_creacion)
                if (Schema::hasColumn('user_ahorro', 'created_at')) {
                    $q->where('created_at', '<', $cut);
                } elseif (Schema::hasColumn('user_ahorro', 'fecha_creacion')) {
                    $q->where('fecha_creacion', '<', $cut);
                } else {
                    $q->where('fecha_solicitud', '<', $cut);
                }
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $total = $pendientes->count();

        $deleted = 0;
        $inactivated = 0;
        $skipped = 0;

        foreach ($pendientes as $a) {
            // Revisar evidencias
            $tieneMovCaja = MovimientoCaja::where('origen_id', $a->id)
                ->orWhere(function ($q) use ($a) {
                    $q->where('descripcion', 'like', "%ahorro #{$a->id}%");
                })
                ->exists();

            $tieneMovAhorro = class_exists(MovimientoAhorro::class)
                ? MovimientoAhorro::where('id_ahorro', $a->id)->exists()
                : false;

            $tieneRetiros = class_exists(RetiroAhorro::class)
                ? RetiroAhorro::where('id_ahorro', $a->id)->exists()
                : false;

            $sinEvidencia = (!$tieneMovCaja && !$tieneMovAhorro && !$tieneRetiros);

            if ($dryRun) {
                $this->line("ID {$a->id} => ".($sinEvidencia ? 'DELETE' : 'INACTIVATE')." sub=".$a->stripe_subscription_id);
                continue;
            }

            // Lock por seguridad
            DB::transaction(function () use (
                $a, $stripe, $sinEvidencia,
                &$deleted, &$inactivated, &$skipped
            ) {
                $row = UserAhorro::where('id', $a->id)->lockForUpdate()->first();
                if (!$row) { $skipped++; return; }

                // Si ya no está pending, no tocar
                if ((int)$row->status !== 0) { $skipped++; return; }

                // Cancelar sub si existe
                if (!empty($row->stripe_subscription_id)) {
                    $this->cancelStripeSubscriptionSafely($stripe, $row->stripe_subscription_id);
                }

                if ($sinEvidencia) {
                    $clienteId = $row->id_cliente ?? null;
                    $ahorroId  = $row->id;

                    $row->delete();
                    $deleted++;

                    $this->saveReturnMessage([
                        'tipo'      => 'ahorro',
                        'entity_id' => $ahorroId,
                        'user_id'   => $clienteId,
                        'status'    => 'warning',
                        'message'   => 'El checkout fue abandonado. El ahorro pendiente se eliminó automáticamente.',
                    ]);
                } else {
                    $row->status = 2; // inactivo
                    $row->save();
                    $inactivated++;

                    $this->saveReturnMessage([
                        'tipo'      => 'ahorro',
                        'entity_id' => $row->id,
                        'user_id'   => $row->id_cliente ?? null,
                        'status'    => 'warning',
                        'message'   => 'El checkout fue abandonado. El ahorro pendiente se inactivó automáticamente.',
                    ]);
                }
            });
        }

        $this->info("Pendientes encontrados: {$total}");
        if ($dryRun) {
            $this->info("DRY RUN (sin cambios).");
            return Command::SUCCESS;
        }

        $this->info("Eliminados: {$deleted}");
        $this->info("Inactivados: {$inactivated}");
        $this->info("Saltados: {$skipped}");

        return Command::SUCCESS;
    }
}
