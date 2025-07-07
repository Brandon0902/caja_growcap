<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAhorrosTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_ahorro', function (Blueprint $table) {
            // PK auto-incremental: INT UNSIGNED
            $table->increments('id');

            // FK a clientes.id (INT UNSIGNED)
            $table->unsignedInteger('id_cliente');

            // FK a ahorros.id (BIGINT UNSIGNED)
            $table->unsignedBigInteger('ahorro_id');

            // Resto de campos
            $table->decimal('monto_ahorro', 10, 2)->default(0);
            $table->integer('tipo')->nullable();
            $table->integer('tiempo');
            $table->decimal('rendimiento', 5, 2);
            $table->decimal('rendimiento_generado', 10, 2);
            $table->integer('retiros')->default(1);
            $table->integer('meses_minimos');
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_inicio');
            $table->integer('status');
            $table->decimal('saldo_fecha', 10, 2)->default(0);
            $table->date('fecha_ultimo_calculo')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->decimal('saldo_disponible', 10, 2)->default(0);
            $table->decimal('cuota', 10, 2)->default(0);
            $table->string('frecuencia_pago', 50);

            // Definición de las FKs
            $table->foreign('id_cliente')
                  ->references('id')
                  ->on('clientes')
                  ->onDelete('cascade');

            $table->foreign('ahorro_id')
                  ->references('id')
                  ->on('ahorros')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('user_ahorro');
    }
}
