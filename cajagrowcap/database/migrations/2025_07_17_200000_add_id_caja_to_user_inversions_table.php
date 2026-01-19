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
        // Agregar columna id_caja si no existe
        if (!Schema::hasColumn('user_inversiones', 'id_caja')) {
            Schema::table('user_inversiones', function (Blueprint $table) {
                $table->unsignedBigInteger('id_caja')->nullable()->after('status');
            });
        }

        // Intentar añadir la clave foránea
        try {
            Schema::table('user_inversiones', function (Blueprint $table) {
                $table->foreign('id_caja')
                      ->references('id_caja')
                      ->on('cajas')
                      ->onDelete('restrict');
            });
        } catch (\Exception $e) {
            // Si ya existe la FK, ignorar el error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar clave foránea si existe
        try {
            Schema::table('user_inversiones', function (Blueprint $table) {
                $table->dropForeign(['id_caja']);
            });
        } catch (\Exception $e) {
            // Si no existe la FK, ignorar
        }

        // Eliminar columna id_caja si existe
        if (Schema::hasColumn('user_inversiones', 'id_caja')) {
            Schema::table('user_inversiones', function (Blueprint $table) {
                $table->dropColumn('id_caja');
            });
        }
    }
};
