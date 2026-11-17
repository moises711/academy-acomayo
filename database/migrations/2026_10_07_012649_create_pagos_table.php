<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table): void {
            $table->increments('id_pago');
            $table->unsignedInteger('id_matricula');
            $table->unsignedInteger('id_usuario');
            $table->timestamp('fecha_pago')->useCurrent();
            $table->decimal('monto', 10, 2);
            $table->string('metodo_pago', 15);
            $table->string('tipo_comprobante', 10)->nullable();
            $table->string('nro_comprobante', 30)->nullable()->unique();
            $table->string('estado', 8)->default('valido');
            $table->string('motivo_anulacion', 200)->nullable();
            $table->unsignedInteger('anulado_por')->nullable();
            $table->timestamp('anulado_en')->nullable();
            $table->index('id_matricula');
            $table->foreign('id_matricula')->references('id_matricula')->on('matriculas')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->foreign('anulado_por')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
