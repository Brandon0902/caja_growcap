<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDataTable extends Migration
{
    public function up()
    {
        Schema::create('user_data', function (Blueprint $table) {
            $table->increments('id');

            // Ahora como unsignedInteger para que coincida con clientes.id
            $table->unsignedInteger('id_cliente')->nullable();

            $table->string('id_estado')->nullable();
            $table->string('rfc')->nullable();
            $table->string('direccion')->nullable();

            $table->unsignedInteger('id_municipio')->nullable();
            $table->string('colonia')->nullable();
            $table->string('cp')->nullable();
            $table->string('beneficiario')->nullable();
            $table->string('beneficiario_telefono')->nullable();
            $table->string('beneficiario_02')->nullable();
            $table->string('beneficiario_telefono_02')->nullable();
            $table->string('banco')->nullable();
            $table->string('cuenta')->nullable();
            $table->text('nip')->nullable();
            $table->dateTime('fecha_alta')->nullable();
            $table->dateTime('fecha_modificacion')->nullable();

            // También como unsignedInteger para que coincida con usuarios.id_usuario
            $table->unsignedInteger('id_usuario')->nullable();

            $table->integer('status')->nullable();
            $table->decimal('porcentaje_1', 5, 2)->nullable();
            $table->decimal('porcentaje_2', 5, 2)->nullable();
            $table->date('fecha_ingreso')->nullable();

            // Claves foráneas (opcionales)
            // $table->foreign('id_cliente')->references('id')->on('clientes');
            // $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_data');
    }
}
