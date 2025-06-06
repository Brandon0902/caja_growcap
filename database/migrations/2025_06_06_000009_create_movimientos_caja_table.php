<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id('id_mov');
            $table->foreignId('id_caja')->constrained('cajas', 'id_caja');
            $table->enum('tipo_mov', ['Ingreso', 'Egreso']);
            $table->foreignId('id_cat_ing')->nullable()->constrained('categorias_ingreso', 'id_cat_ing');
            $table->foreignId('id_sub_ing')->nullable()->constrained('subcategorias_ingreso', 'id_sub_ing');
            $table->foreignId('id_cat_gasto')->nullable()->constrained('categorias_gasto', 'id_cat_gasto');
            $table->foreignId('id_sub_gasto')->nullable()->constrained('subcategorias_gasto', 'id_sub_gasto');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores', 'id_proveedor');
            $table->decimal('monto', 15, 2);
            $table->dateTime('fecha');
            $table->text('descripcion');
            $table->decimal('monto_anterior', 15, 2);
            $table->decimal('monto_posterior', 15, 2);
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};
