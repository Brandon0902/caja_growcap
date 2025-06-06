<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_asientos', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->foreignId('id_asiento')->constrained('asientos', 'id_asiento');
            $table->foreignId('id_cuenta')->constrained('cuentas', 'id_cuenta');
            $table->decimal('monto_debito', 15, 2)->nullable();
            $table->decimal('monto_credito', 15, 2)->nullable();
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_asientos');
    }
};
