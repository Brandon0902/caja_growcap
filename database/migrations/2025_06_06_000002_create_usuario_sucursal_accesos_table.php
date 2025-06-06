<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_sucursal_accesos', function (Blueprint $table) {
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('id_sucursal');
            $table->boolean('acceso_activo')->default(true);

            $table->foreign('usuario_id')->references('id_usuario')->on('usuarios');
            $table->foreign('id_sucursal')->references('id_sucursal')->on('sucursales');

            $table->primary(['usuario_id', 'id_sucursal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_sucursal_accesos');
    }
};
