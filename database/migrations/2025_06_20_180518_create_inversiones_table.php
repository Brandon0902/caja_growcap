<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInversionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inversiones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('periodo', 255)->nullable();
            $table->string('meses_minimos', 11)->nullable();
            $table->string('monto_minimo', 255)->nullable();
            $table->string('monto_maximo', 255)->nullable();
            $table->string('rendimiento', 255)->nullable();
            $table->dateTime('fecha')->nullable();
            $table->dateTime('fecha_edit')->nullable();
            $table->unsignedInteger('id_usuario')->nullable();
            $table->integer('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inversiones');
    }
}
