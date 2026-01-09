<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdCajaToUserPrestamosTable extends Migration
{
    public function up()
    {
        Schema::table('user_prestamos', function (Blueprint $table) {
            // 1. Añadimos la columna
            $table->unsignedBigInteger('id_caja')
                  ->nullable()
                  ->after('id_activo');

            // 2. Clave foránea apuntando a cajas.id_caja
            $table->foreign('id_caja')
                  ->references('id_caja')
                  ->on('cajas')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('user_prestamos', function (Blueprint $table) {
            // 1. Eliminamos la FK
            $table->dropForeign(['id_caja']);

            // 2. Eliminamos la columna
            $table->dropColumn('id_caja');
        });
    }
}
