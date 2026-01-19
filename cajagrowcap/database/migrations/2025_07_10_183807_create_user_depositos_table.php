<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDepositosTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_depositos', function (Blueprint $table) {
            // PK auto-increment BIGINT UNSIGNED
            $table->id();

            // Coincide con clientes.id (INT UNSIGNED)
            $table->unsignedInteger('id_cliente')
                  ->nullable()
                  ->index();

            $table->string('cantidad');
            $table->date('fecha_deposito')->nullable();
            $table->text('nota')->nullable();
            $table->string('deposito')->nullable();

            // Coincide con usuarios.id_usuario (BIGINT UNSIGNED)
            $table->unsignedBigInteger('id_usuario')
                  ->nullable()
                  ->index();

            $table->dateTime('fecha_alta')->nullable();
            $table->dateTime('fecha_edit')->nullable();
            $table->integer('status')->default(1);

            // Definición de las FK
            $table->foreign('id_cliente')
                  ->references('id')
                  ->on('clientes')
                  ->onDelete('set null');

            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primero eliminar las claves foráneas
        Schema::table('user_depositos', function (Blueprint $table) {
            $table->dropForeign(['id_cliente']);
            $table->dropForeign(['id_usuario']);
        });

        // Luego borrar la tabla
        Schema::dropIfExists('user_depositos');
    }
}
