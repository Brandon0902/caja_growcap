<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_caja_accesos', function (Blueprint $table) {
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('id_caja');
            $table->boolean('acceso_activo')->default(true);

            $table->foreign('usuario_id')->references('id_usuario')->on('usuarios');
            $table->foreign('id_caja')->references('id_caja')->on('cajas');

            $table->primary(['usuario_id', 'id_caja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_caja_accesos');
    }
};
