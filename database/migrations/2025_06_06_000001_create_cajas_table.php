<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id('id_caja');
            $table->foreignId('id_sucursal')->constrained('sucursales', 'id_sucursal');
            $table->string('nombre');
            $table->foreignId('responsable_id')->constrained('usuarios', 'id_usuario');
            $table->dateTime('fecha_apertura');
            $table->decimal('saldo_inicial', 15, 2);
            $table->dateTime('fecha_cierre')->nullable();
            $table->decimal('saldo_final', 15, 2)->nullable();
            $table->enum('estado', ['abierta', 'cerrada']);
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $table->boolean('acceso_activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
