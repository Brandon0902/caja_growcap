<?php
// database/migrations/2025_07_18_000000_add_id_caja_to_user_depositos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdCajaToUserDepositosTable extends Migration
{
    public function up(): void
    {
        Schema::table('user_depositos', function (Blueprint $table) {
            // 1) añadimos la columna
            $table->unsignedBigInteger('id_caja')->after('status');
            // 2) añadimos la FK apuntando a cajas(id_caja)
            $table->foreign('id_caja')
                  ->references('id_caja')
                  ->on('cajas')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('user_depositos', function (Blueprint $table) {
            $table->dropForeign(['id_caja']);
            $table->dropColumn('id_caja');
        });
    }
}
