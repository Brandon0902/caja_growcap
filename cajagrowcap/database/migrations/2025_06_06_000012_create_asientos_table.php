<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asientos', function (Blueprint $table) {
            $table->id('id_asiento');
            $table->foreignId('id_sucursal')->constrained('sucursales', 'id_sucursal');
            $table->dateTime('fecha');
            $table->text('descripcion');
            $table->enum('tipo', ['manual', 'automático', 'cierre']);
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asientos');
    }
};
