<?php

namespace Tests\Feature;

use App\Models\CategoriaIngreso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubcategoriaIngresoTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_subcategoria_ingreso_and_redirects(): void
    {
        $userId = DB::table('usuarios')->insertGetId([
            'nombre' => 'Usuario Prueba',
            'email' => 'usuario@example.com',
            'password' => Hash::make('secret'),
            'rol' => 'admin',
            'fecha_creacion' => now(),
            'activo' => true,
            'remember_token' => 'token',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::findOrFail($userId);

        $categoria = CategoriaIngreso::create([
            'nombre' => 'Ventas',
            'id_usuario' => $userId,
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware(\Spatie\Permission\Middlewares\PermissionMiddleware::class)
            ->post(route('subcategoria-ingresos.store'), [
                'id_cat_ing' => $categoria->id_cat_ing,
                'nombre' => 'Suscripción',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subcategorias_ingreso', [
            'nombre' => 'Suscripción',
            'id_cat_ing' => $categoria->id_cat_ing,
            'id_usuario' => $userId,
        ]);
    }
}
