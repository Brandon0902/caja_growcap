<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketRespuestasTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_respuestas', function (Blueprint $table) {
            // PK auto-increment INT UNSIGNED
            $table->increments('id');

            // FK al ticket padre (tickets.id → INT UNSIGNED)
            $table->unsignedInteger('ticket_id')
                  ->index();

            // FK opcional al cliente que responde (clientes.id → INT UNSIGNED)
            $table->unsignedInteger('id_cliente')
                  ->nullable()
                  ->index();

            // FK opcional al usuario que responde (usuarios.id_usuario → BIGINT UNSIGNED)
            $table->unsignedBigInteger('id_usuario')
                  ->nullable()
                  ->index();

            // Texto de la respuesta
            $table->text('respuesta');

            // Para posibles hilos de conversación
            $table->unsignedInteger('parent_id')
                  ->nullable()
                  ->index();

            $table->dateTime('fecha')->nullable();
            $table->timestamps();

            // Constraints FK
            $table->foreign('ticket_id')
                  ->references('id')->on('tickets')
                  ->onDelete('cascade');

            $table->foreign('id_cliente')
                  ->references('id')->on('clientes')
                  ->onDelete('set null');

            $table->foreign('id_usuario')
                  ->references('id_usuario')->on('usuarios')
                  ->onDelete('set null');

            $table->foreign('parent_id')
                  ->references('id')->on('ticket_respuestas')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_respuestas', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropForeign(['id_cliente']);
            $table->dropForeign(['id_usuario']);
            $table->dropForeign(['parent_id']);
        });
        Schema::dropIfExists('ticket_respuestas');
    }
}
