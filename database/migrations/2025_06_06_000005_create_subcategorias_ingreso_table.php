<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategorias_ingreso', function (Blueprint $table) {
            $table->id('id_sub_ing');
            $table->foreignId('id_cat_ing')->constrained('categorias_ingreso', 'id_cat_ing');
            $table->string('nombre');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategorias_ingreso');
    }
};
