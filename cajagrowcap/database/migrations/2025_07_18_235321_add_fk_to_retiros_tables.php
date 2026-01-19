<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFkToRetirosTables extends Migration
{
    public function up(): void
    {
        // 1) Agrega la FK en retiros
        Schema::table('retiros', function (Blueprint $table) {
            $table->foreign('id_caja')
                  ->references('id_caja')
                  ->on('cajas')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

    }

    public function down(): void
    {
        Schema::table('retiros', function (Blueprint $table) {
            $table->dropForeign(['id_caja']);
        });
    }
}
