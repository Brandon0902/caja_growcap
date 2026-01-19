<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRetirosAhorroTable extends Migration
{
    public function up()
    {
        Schema::create('retiros_ahorro', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50)->nullable();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->unsignedBigInteger('id_cliente')->nullable();
            $table->dateTime('fecha_transferencia')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('fecha_solicitud')->nullable();
            $table->decimal('cantidad', 10, 2)->nullable();
            $table->unsignedBigInteger('id_ahorro')->nullable();
            $table->string('status', 50)->nullable();
            // Tampoco agregamos updated_at para que coincida con tu esquema
        });
    }

    public function down()
    {
        Schema::dropIfExists('retiros_ahorro');
    }
}
