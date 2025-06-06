<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---------------------------------------------------
        // Tabla "usuarios" (en lugar de "users")
        // ---------------------------------------------------
        Schema::create('usuarios', function (Blueprint $table) {
            // id_usuario: PK auto-incremental
            $table->id('id_usuario');

            // nombre completo del usuario
            $table->string('nombre');

            // correo electrónico, único
            $table->string('email')->unique();

            // timestamp en que el email fue verificado (puede quedar)
            $table->timestamp('email_verified_at')->nullable();

            // hash de la contraseña
            $table->string('password');

            // rol del usuario (admin, cobrador, contador, gerente u otro)
            $table->enum('rol', ['admin', 'cobrador', 'contador', 'gerente', 'otro'])
                  ->default('otro');

            // fecha de creación de la cuenta (igual que created_at, pero explícito)
            $table->dateTime('fecha_creacion')->default(DB::raw('CURRENT_TIMESTAMP'));

            // indicador de si está activo o no
            $table->boolean('activo')->default(true);

            // token para “remember me” (de Breeze)
            $table->rememberToken();

            // timestamps de Laravel (created_at, updated_at)
            // Nota: created_at quedaría duplicado con fecha_creacion, pero lo mantenemos
            // para preservar la compatibilidad con Breeze. Si prefieres, puedes borrarlo.
            $table->timestamps();
        });

        // ---------------------------------------------------
        // Resto de tablas que crea Breeze (sin cambios),
        // renombradas tal cual vienen en la migración original.
        // ---------------------------------------------------

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('usuarios', 'id_usuario')
                  ->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};
