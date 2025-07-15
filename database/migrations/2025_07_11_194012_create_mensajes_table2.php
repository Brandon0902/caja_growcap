<?php

// database/migrations/2025_07_11_XXXXXX_create_mensajes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMensajesTable2 extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            // PK auto-increment INT UNSIGNED
            $table->increments('id');

            // Tipo de mensaje (1=info,2=alerta,…)
            $table->integer('tipo')->nullable();

            // FK al cliente que recibe/manda el mensaje
            $table->unsignedInteger('id_cliente')->nullable()->index();

            $table->string('url', 255)->nullable();
            $table->string('nombre', 255)->nullable();
            $table->text('descripcion')->nullable();
            $table->text('introduccion')->nullable();
            $table->string('img', 255)->nullable();

            // Fecha de envío o publicación
            $table->dateTime('fecha')->nullable();

            // Fecha de última edición
            $table->dateTime('fecha_edit')->nullable();

            // FK al usuario admin autor
            $table->unsignedBigInteger('id_usuario')->nullable()->index();

            // Status (1=activo,0=inactivo)
            $table->tinyInteger('status')->default(1);

            // Claves foráneas
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
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropForeign(['id_cliente']);
            $table->dropForeign(['id_usuario']);
        });
        Schema::dropIfExists('mensajes');
    }
}

