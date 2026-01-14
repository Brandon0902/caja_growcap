<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MovimientosCajaTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithCaja(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $userId = DB::table('usuarios')->insertGetId([
            'nombre' => 'Usuario Caja',
            'email' => 'usuario-caja@example.com',
            'password' => Hash::make('secret'),
            'rol' => 'admin',
            'fecha_creacion' => now(),
            'activo' => true,
            'remember_token' => 'token',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::findOrFail($userId);

        Permission::create(['name' => 'cajas.ver', 'guard_name' => 'web']);
        $user->givePermissionTo('cajas.ver');

        $sucursal = Sucursal::create([
            'nombre' => 'Sucursal Centro',
            'direccion' => 'Calle 123',
            'telefono' => '555-1234',
            'gerente_id' => $userId,
            'id_usuario' => $userId,
            'acceso_activo' => true,
        ]);

        $caja = Caja::create([
            'id_sucursal' => $sucursal->id_sucursal,
            'nombre' => 'Caja Principal',
            'responsable_id' => $userId,
            'fecha_apertura' => now(),
            'saldo_inicial' => 1000,
            'saldo_final' => null,
            'estado' => 'abierta',
            'id_usuario' => $userId,
            'acceso_activo' => true,
        ]);

        return [$user, $caja];
    }

    public function test_create_shows_caja_options(): void
    {
        [$user, $caja] = $this->createUserWithCaja();

        $response = $this->actingAs($user)
            ->withoutMiddleware(\Spatie\Permission\Middlewares\PermissionMiddleware::class)
            ->get(route('movimientos-caja.create'));

        $response->assertStatus(200);
        $response->assertSee('value="'.$caja->id_caja.'"', false);
        $response->assertSee($caja->nombre);
    }

    public function test_store_creates_movimiento_and_updates_caja(): void
    {
        Mail::fake();

        [$user, $caja] = $this->createUserWithCaja();

        $response = $this->actingAs($user)
            ->withoutMiddleware(\Spatie\Permission\Middlewares\PermissionMiddleware::class)
            ->post(route('movimientos-caja.store'), [
                'id_caja' => $caja->id_caja,
                'tipo_mov' => 'ingreso',
                'monto' => 150,
                'fecha' => now()->toDateString(),
                'descripcion' => 'Ingreso inicial',
            ]);

        $response->assertRedirect(route('movimientos-caja.index'));

        $this->assertDatabaseHas('movimientos_caja', [
            'id_caja' => $caja->id_caja,
            'tipo_mov' => 'Ingreso',
            'monto' => 150,
            'monto_anterior' => 1000,
            'monto_posterior' => 1150,
        ]);

        $this->assertDatabaseHas('cajas', [
            'id_caja' => $caja->id_caja,
            'saldo_final' => 1150,
        ]);
    }
}
