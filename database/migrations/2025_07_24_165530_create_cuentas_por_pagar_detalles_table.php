<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_cuentas_por_pagar_detalles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('cuentas_por_pagar_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuenta_id');
            $table->unsignedInteger('numero_pago');
            $table->date('fecha_pago');
            $table->decimal('saldo_inicial',15,2);
            $table->decimal('amortizacion_cap',15,2);
            $table->decimal('pago_interes',15,2);
            $table->decimal('monto_pago',15,2);
            $table->decimal('saldo_restante',15,2);
            $table->enum('estado',['pendiente','pagado','vencido'])->default('pendiente');
            $table->unsignedBigInteger('caja_id')->nullable();
            $table->unsignedSmallInteger('semana')->nullable();
            $table->timestamps();

            $table->foreign('cuenta_id')
                  ->references('id_cuentas_por_pagar')
                  ->on('cuentas_por_pagar')
                  ->onDelete('cascade');

            $table->foreign('caja_id')
                  ->references('id_caja')
                  ->on('cajas');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cuentas_por_pagar_detalles');
    }
};

