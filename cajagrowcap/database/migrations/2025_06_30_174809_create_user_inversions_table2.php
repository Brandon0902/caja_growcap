<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserInversionsTable2 extends Migration
{
    public function up()
    {
        Schema::create('user_inversiones', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_cliente')->nullable();
            $table->integer('inversion')->nullable();
            $table->string('tipo')->nullable();
            $table->integer('tiempo')->nullable();
            $table->integer('rendimiento')->nullable();
            $table->string('rendimiento_generado')->nullable();
            $table->integer('retiros')->nullable();
            $table->string('meses_minimos')->nullable();
            $table->integer('retiros_echos')->default(0);
            $table->dateTime('fecha_solicitud')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->dateTime('fecha_alta')->nullable();
            $table->dateTime('fecha_edit')->nullable();
            $table->string('deposito')->nullable();
            $table->integer('id_usuario')->nullable();
            $table->integer('id_activo')->nullable();
            $table->integer('status')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_inversiones');
    }
}
