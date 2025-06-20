<?php

// database/migrations/2025_06_12_000000_create_prestamos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrestamosTable extends Migration
{
    public function up()
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id('id_prestamo');
            $table->foreignId('id_usuario')->constrained('usuarios','id_usuario');
            $table->string('periodo');
            $table->integer('semanas');
            $table->decimal('interes', 5, 2);
            $table->decimal('monto_minimo', 12, 2);
            $table->decimal('monto_maximo', 12, 2);
            $table->integer('antiguedad');
            $table->enum('status',['1','2','3','4'])->default('1'); // 1=Activo,2=Pendiente,3=En revisión,4=Cancelado
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('prestamos');
    }
}

