<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_mora', function (Blueprint $table) {
            $table->id();
            $table->decimal('cargo_fijo', 10, 2)->nullable();
            $table->decimal('porcentaje_mora', 5, 2)->nullable();
            $table->integer('periodo_gracia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_mora');
    }
};
