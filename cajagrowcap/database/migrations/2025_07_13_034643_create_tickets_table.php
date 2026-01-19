<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            // PK auto-increment INT UNSIGNED
            $table->increments('id');

            // FK al cliente (clientes.id → INT UNSIGNED)
            $table->unsignedInteger('id_cliente')
                  ->nullable()
                  ->index();

            // Área / Categoría del ticket
            $table->string('area', 100);

            // Asunto y cuerpo del ticket
            $table->string('asunto', 255);
            $table->text('mensaje');

            // Adjuntos (ruta en disco)
            $table->string('adjunto')->nullable();

            // Fechas: apertura, seguimiento opcional, cierre opcional
            $table->dateTime('fecha')->nullable();
            $table->dateTime('fecha_seguimiento')->nullable();
            $table->dateTime('fecha_cierre')->nullable();

            // FK al usuario admin que creó el ticket (usuarios.id_usuario → BIGINT UNSIGNED)
            $table->unsignedBigInteger('id_usuario')
                  ->nullable()
                  ->index();

            // Estado: 0=Abierto,1=En espera,2=Cerrado
            $table->tinyInteger('status')->default(0);

            $table->timestamps();

            // Constraints FK
            $table->foreign('id_cliente')
                  ->references('id')->on('clientes')
                  ->onDelete('set null');

            $table->foreign('id_usuario')
                  ->references('id_usuario')->on('usuarios')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['id_cliente']);
            $table->dropForeign(['id_usuario']);
        });
        Schema::dropIfExists('tickets');
    }
}
