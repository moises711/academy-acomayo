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
        Schema::create('pagos_docentes', function (Blueprint $table): void {
            $table->increments('id_pago_docente');
            $table->unsignedInteger('id_ciclo')->nullable()->index();
            $table->unsignedInteger('id_profesor');
            $table->unsignedInteger('id_usuario');
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->string('periodo', 7);
            $table->string('metodo_pago', 15);
            $table->string('nro_comprobante', 30)->nullable()->unique();
            $table->string('estado', 8)->default('valido');
            $table->string('motivo_anulacion', 200)->nullable();
            $table->unsignedInteger('anulado_por')->nullable();
            $table->timestamp('anulado_en')->nullable();
            $table->timestamps();

            $table->foreign('id_ciclo')->references('id_ciclo')->on('ciclos')->restrictOnDelete();
            $table->foreign('id_profesor')->references('id_profesor')->on('profesores')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->foreign('anulado_por')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->index(['id_profesor', 'fecha_pago']);
            $table->index(['periodo', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_docentes');
    }
};
