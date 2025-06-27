<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateEmpresasTable extends Migration
{
    public function up()
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 255);
            $table->string('rfc', 20)->nullable();
            $table->string('direccion', 500)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('estado', 100)->nullable();
            $table->string('codigo_postal', 20)->nullable();
            $table->string('pais', 100)->default('México');
            $table->string('telefono', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('estatus')->default(1);
            // DATETIME con timestamp y on update
            $table->dateTime('fecha_creacion')
                  ->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->dateTime('fecha_modificacion')
                  ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    public function down()
    {
        Schema::dropIfExists('empresas');
    }
}
