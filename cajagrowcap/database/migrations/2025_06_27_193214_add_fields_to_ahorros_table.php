<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToAhorrosTable extends Migration
{
    public function up()
    {
        Schema::table('ahorros', function (Blueprint $table) {
            // Ajusta el after() según el orden de tus columnas
            $table->integer('meses_minimos')->after('id');
            $table->decimal('monto_minimo', 10, 2)->default(0)->after('meses_minimos');
            $table->string('tipo_ahorro', 50)->after('monto_minimo');
            $table->decimal('tasa_vigente', 10, 2)->default(0)->after('tipo_ahorro');
        });
    }

    public function down()
    {
        Schema::table('ahorros', function (Blueprint $table) {
            $table->dropColumn([
                'meses_minimos',
                'monto_minimo',
                'tipo_ahorro',
                'tasa_vigente',
            ]);
        });
    }
}
