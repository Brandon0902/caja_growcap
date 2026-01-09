<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFkToRetirosAhorroTable extends Migration
{
    public function up(): void
    {
        Schema::table('retiros_ahorro', function (Blueprint $table) {
            $table->foreign('id_caja')
                  ->references('id_caja')
                  ->on('cajas')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('retiros_ahorro', function (Blueprint $table) {
            $table->dropForeign(['id_caja']);
        });
    }
}
