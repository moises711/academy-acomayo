<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatorias_becas', function (Blueprint $table): void {
            $table->id('id_convocatoria');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('examen_becas_activo')->default(false);
            $table->date('fecha_examen')->nullable();
            $table->time('hora_inicio_examen')->nullable();
            $table->time('hora_fin_examen')->nullable();
            $table->decimal('puntaje_minimo', 10, 2);
            $table->enum('estado', ['Borrador', 'Abierta', 'Cerrada'])->default('Borrador');
            $table->unsignedInteger('id_simulacro_ab')->nullable()->unique();
            $table->unsignedInteger('id_simulacro_cd')->nullable()->unique();
            $table->foreign('id_simulacro_ab')->references('id_simulacro')->on('simulacros')->nullOnDelete();
            $table->foreign('id_simulacro_cd')->references('id_simulacro')->on('simulacros')->nullOnDelete();
            $table->index(['estado', 'fecha_inicio', 'fecha_fin']);
            $table->timestamp('depurada_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatorias_becas');
    }
};
