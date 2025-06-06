<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id('id_sucursal');
            $table->string('nombre')->unique();
            $table->string('direccion');
            $table->string('telefono');
            $table->foreignId('gerente_id')->constrained('usuarios', 'id_usuario');
            $table->text('politica_crediticia')->nullable();
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $table->boolean('acceso_activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
