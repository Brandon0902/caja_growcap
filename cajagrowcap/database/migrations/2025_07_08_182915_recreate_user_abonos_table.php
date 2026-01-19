<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Si existe, la borramos primero
        Schema::dropIfExists('user_abonos');

        // Luego la volvemos a crear con las columnas y FKs correctos
        Schema::create('user_abonos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('tipo_abono', 50)->nullable();
            $table->date('fecha_vencimiento')->nullable();

            // FK a user_prestamos.id (integer unsigned)
            $table->unsignedInteger('user_prestamo_id')->nullable();

            // FK a clientes.id (integer unsigned)
            $table->unsignedInteger('id_cliente')->nullable();

            $table->integer('num_pago')->nullable();
            $table->decimal('mora_generada', 10, 2)->nullable();
            $table->dateTime('fecha')->nullable();
            $table->decimal('cantidad', 10, 2)->nullable();
            $table->string('status', 50)->nullable();
            $table->decimal('saldo_restante', 10, 2)->nullable();

            // Índices y FKs
            $table->index('user_prestamo_id');
            $table->index('id_cliente');

            $table->foreign('user_prestamo_id')
                  ->references('id')->on('user_prestamos')
                  ->onDelete('cascade');

            $table->foreign('id_cliente')
                  ->references('id')->on('clientes')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_abonos');
    }
};
