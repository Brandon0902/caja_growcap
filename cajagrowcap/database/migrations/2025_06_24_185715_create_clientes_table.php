<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateClientesTable extends Migration
{
    public function up()
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_superior')->nullable();
            $table->integer('id_padre')->nullable();
            $table->string('nombre', 255);
            $table->string('apellido', 255)->nullable();
            $table->string('telefono', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('codigo_cliente', 8)->unique();
            $table->string('user', 255)->nullable();
            $table->string('pass_reset_guid', 255)->nullable();
            $table->string('pass', 255)->nullable();
            $table->string('tipo', 255)->default('Cliente');
            $table->dateTime('fecha')->nullable();
            $table->dateTime('fecha_edit')->nullable();
            $table->dateTime('ultimo_acceso')->nullable();
            $table->integer('id_usuario')->nullable();
            $table->integer('status')->default(1);
        });
    }

    public function down()
    {
        Schema::dropIfExists('clientes');
    }
}
