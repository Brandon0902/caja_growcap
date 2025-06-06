<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vista_ganancias', function (Blueprint $table) {
            $table->id('id_vista');
            $table->string('periodo');
            $table->foreignId('id_sucursal')->constrained('sucursales', 'id_sucursal');
            $table->foreignId('id_caja')->constrained('cajas', 'id_caja');
            $table->decimal('ingresos_negocio', 15, 2);
            $table->decimal('ingresos_personales', 15, 2);
            $table->decimal('costos_directos', 15, 2);
            $table->decimal('gastos_negocio', 15, 2);
            $table->decimal('gastos_personales', 15, 2);
            $table->decimal('otros_ingresos', 15, 2);
            $table->decimal('otros_gastos', 15, 2);
            $table->decimal('utilidad_neta', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vista_ganancias');
    }
};
