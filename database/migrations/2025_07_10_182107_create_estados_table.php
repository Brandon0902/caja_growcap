<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstadosTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estados', function (Blueprint $table) {
            $table->id();                                  // id INT PRIMARY AUTO_INCREMENT
            $table->string('nombre', 100)->nullable();     // nombre VARCHAR(100) NULL
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_edicion')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->dateTime('fecha_edit')->nullable();    // si lo necesitas distinto a fecha_edicion
            $table->integer('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados');
    }
}
