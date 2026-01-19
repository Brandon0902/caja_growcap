<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            // Solo añadimos la FK que faltaba:
            $table->foreign('proveedor_id')
                  ->references('id_proveedor')
                  ->on('proveedores')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
        });
    }
};
