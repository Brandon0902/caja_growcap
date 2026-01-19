<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();

            // llave foránea al cliente (nullable si es opcional)
            $table->unsignedBigInteger('id_cliente')->nullable();
            // si quieres la constraint:
            // $table->foreign('id_cliente')->references('id')->on('clientes')->cascadeOnDelete();

            $table->string('documento_01')->nullable();
            $table->string('documento_02')->nullable();
            $table->string('documento_02_02')->nullable();
            $table->string('documento_03')->nullable();
            $table->string('documento_04')->nullable();
            $table->string('documento_05')->nullable();

            // llave foránea al usuario (nullable si es opcional)
            $table->unsignedBigInteger('id_usuario')->nullable();
            // $table->foreign('id_usuario')->references('id')->on('users')->cascadeOnDelete();

            $table->dateTime('fecha')->nullable();

            // Si quieres timestamps automáticos:
            // $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
