<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLaboralesTable extends Migration
{
    public function up()
    {
        Schema::create('user_laborales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_cliente');
            $table->unsignedInteger('empresa_id')->nullable();
            $table->string('direccion');
            $table->string('telefono', 50)->nullable();
            $table->string('puesto', 100)->nullable();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->decimal('salario_mensual', 10, 2)->default(0.00);
            $table->enum('tipo_salario', ['Asalariado','Independiente','No hay datos'])
                  ->default('No hay datos');
            $table->enum('estado_salario', ['Estable','Variable','Inestable'])
                  ->default('Inestable');
            $table->unsignedTinyInteger('tipo_salario_valor')->default(5);
            $table->enum('recurrencia_pago', ['Semanal','Quincenal','Mensual'])
                  ->default('Mensual');
            $table->unsignedTinyInteger('recurrencia_valor')->default(100);
            // Cambiado de unsignedInteger a unsignedBigInteger
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->timestamp('fecha')->useCurrent();

            // Foreign keys
            $table->foreign('id_cliente')
                  ->references('id')->on('clientes');
            $table->foreign('empresa_id')
                  ->references('id')->on('empresas');
            $table->foreign('id_usuario')
                  ->references('id_usuario')->on('usuarios');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_laborales');
    }
}
