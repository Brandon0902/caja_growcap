<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdCajaToRetirosAhorroTable extends Migration
{
    public function up(): void
    {
        Schema::table('retiros_ahorro', function (Blueprint $table) {
            // 1) añadimos la columna id_caja justo después de status
            // 2) le ponemos un default (p. ej. 1) para poblar los registros viejos
            $table->unsignedBigInteger('id_caja')
                  ->after('status')
                  ->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('retiros_ahorro', function (Blueprint $table) {
            $table->dropColumn('id_caja');
        });
    }
}
