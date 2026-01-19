<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategorias_gasto', function (Blueprint $table) {
            $table->id('id_sub_gasto');
            $table->foreignId('id_cat_gasto')->constrained('categorias_gasto', 'id_cat_gasto');
            $table->string('nombre');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategorias_gasto');
    }
};
