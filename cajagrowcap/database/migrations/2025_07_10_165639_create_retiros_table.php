<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRetirosTable extends Migration
{
    public function up()
    {
        Schema::create('retiros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente')->nullable();
            $table->string('tipo')->nullable();
            $table->string('cantidad')->nullable();
            $table->dateTime('fecha_solicitud')->nullable();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->dateTime('fecha_transferencia')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->integer('status')->nullable();
            // No timestamps, según tu tabla original
        });
    }

    public function down()
    {
        Schema::dropIfExists('retiros');
    }
}
