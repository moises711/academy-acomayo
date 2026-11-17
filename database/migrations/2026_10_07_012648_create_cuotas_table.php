<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuotas', function (Blueprint $table): void {
            $table->increments('id_cuota');
            $table->unsignedInteger('id_matricula');
            $table->string('concepto', 100);
            $table->integer('numero_cuota')->nullable();
            $table->decimal('monto_original', 10, 2);
            $table->decimal('monto_descuento', 10, 2)->default(0);
            $table->decimal('monto_final', 10, 2);
            $table->date('fecha_vencimiento');
            $table->string('estado_pago', 10)->default('Pendiente');
            $table->index('id_matricula');
            $table->index(['fecha_vencimiento', 'estado_pago']);
            $table->foreign('id_matricula')->references('id_matricula')->on('matriculas')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas');
    }
};
