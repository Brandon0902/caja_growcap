<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPrestamosTable extends Migration
{
    public function up()
    {
        Schema::create('user_prestamos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_cliente')->nullable();
            $table->integer('aval_id')->nullable();
            $table->decimal('cantidad', 12, 2);
            $table->string('tipo_prestamo')->nullable();
            $table->integer('tiempo')->nullable();
            $table->integer('interes')->nullable();
            $table->string('interes_generado')->nullable();
            $table->string('doc_solicitud_aval')->nullable();
            $table->string('doc_comprobante_domicilio')->nullable();
            $table->string('doc_ine_frente')->nullable();
            $table->string('doc_ine_reverso')->nullable();
            $table->string('semanas')->nullable();
            $table->integer('abonos_echos')->default(0);
            $table->dateTime('fecha_solicitud')->nullable();
            $table->dateTime('aval_notified_at')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->dateTime('fecha_seguimiento')->nullable();
            $table->dateTime('fecha_edit')->nullable();
            $table->string('deposito')->nullable();
            $table->text('nota')->nullable();
            $table->integer('id_usuario')->nullable();
            $table->integer('id_activo')->nullable();
            $table->integer('status')->nullable();
            $table->tinyInteger('aval_status')
                  ->default(0)
                  ->comment('0=Pendiente,1=Aceptado,2=Rechazado');
            $table->dateTime('aval_responded_at')->nullable();
            $table->integer('num_atrasos')->nullable();
            $table->decimal('mora_acumulada', 10, 2)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_prestamos');
    }
}
