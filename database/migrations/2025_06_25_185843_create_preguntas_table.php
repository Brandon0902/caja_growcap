<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->string('pregunta', 255)->nullable();
            $table->text('respuesta')->nullable();
            $table->string('categoria', 50)->default('general');
            $table->string('img', 255)->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->dateTime('fecha')->nullable();
            $table->integer('status')->default(1);
            // no timestamps, ya usamos 'fecha'
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
