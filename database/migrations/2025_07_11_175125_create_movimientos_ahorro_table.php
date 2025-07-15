<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMovimientosAhorroTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movimientos_ahorro', function (Blueprint $table) {
            // PK auto-increment INT UNSIGNED
            $table->increments('id');

            // FK hacia user_ahorro.id; en tu esquema es INT UNSIGNED
            $table->unsignedInteger('id_ahorro')->nullable()->index();

            $table->decimal('monto', 10, 2);
            $table->text('observaciones')->nullable();
            $table->decimal('saldo_resultante', 10, 2);
            $table->dateTime('fecha');
            $table->string('tipo', 50)->nullable();

            // FK hacia usuarios.id_usuario; en tu esquema es BIGINT UNSIGNED
            $table->unsignedBigInteger('id_usuario')->nullable()->index();

            // Índices y relaciones
            $table->foreign('id_ahorro')
                  ->references('id')
                  ->on('user_ahorro')
                  ->onDelete('cascade');

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
        Schema::table('movimientos_ahorro', function (Blueprint $table) {
            $table->dropForeign(['id_ahorro']);
            $table->dropForeign(['id_usuario']);
        });
        Schema::dropIfExists('movimientos_ahorro');
    }
}
