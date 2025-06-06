<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id('id_cuenta');
            $table->string('codigo_cuenta')->unique();
            $table->string('nombre');
            $table->enum('tipo', ['Activo', 'Pasivo', 'Gasto', 'Ingreso', 'Patrimonio']);
            $table->decimal('balance_actual', 15, 2);
            $table->foreignId('id_padre')->nullable()->constrained('cuentas', 'id_cuenta');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
