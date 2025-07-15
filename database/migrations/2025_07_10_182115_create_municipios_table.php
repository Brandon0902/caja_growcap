<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMunicipiosTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();                                 // id INT PRIMARY AUTO_INCREMENT
            $table->unsignedBigInteger('id_estado')
                  ->nullable()
                  ->index();                              // FK parcial a estados.id
            $table->string('nombre', 150);
            $table->tinyInteger('status')->default(1);    // status TINYINT(1) DEFAULT 1
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
}
