<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregamos la columna id_caja a user_ahorro si no existe
        if (! Schema::hasColumn('user_ahorro', 'id_caja')) {
            Schema::table('user_ahorro', function (Blueprint $table) {
                $table->unsignedBigInteger('id_caja')
                      ->nullable()
                      ->after('status');
            });
        }

        // Intentamos crear la FK; si ya existe, capturamos la excepción y seguimos
        try {
            Schema::table('user_ahorro', function (Blueprint $table) {
                $table->foreign('id_caja')
                      ->references('id_caja')
                      ->on('cajas')
                      ->onDelete('restrict');
            });
        } catch (\Exception $e) {
            // Si la FK ya existía, simplemente la ignoramos
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminamos la FK si existe
        try {
            Schema::table('user_ahorro', function (Blueprint $table) {
                $table->dropForeign(['id_caja']);
            });
        } catch (\Exception $e) {
            // Si no existía, no hacemos nada
        }

        // Eliminamos la columna id_caja si existe
        if (Schema::hasColumn('user_ahorro', 'id_caja')) {
            Schema::table('user_ahorro', function (Blueprint $table) {
                $table->dropColumn('id_caja');
            });
        }
    }
};
