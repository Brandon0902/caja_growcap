<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->id('id_cuentas_por_pagar');
            $table->foreignId('id_sucursal')->constrained('sucursales', 'id_sucursal');
            $table->foreignId('id_caja')->nullable()->constrained('cajas', 'id_caja');
            $table->foreignId('proveedor_id')->constrained('proveedores', 'id_proveedor');
            $table->decimal('monto_total', 15, 2);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['pendiente', 'pagado', 'vencido']);
            $table->text('descripcion');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_pagar');
    }
};
