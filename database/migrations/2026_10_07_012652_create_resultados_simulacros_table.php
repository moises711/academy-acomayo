<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_simulacros', function (Blueprint $table): void {
            $table->increments('id_resultado');
            $table->unsignedInteger('id_simulacro');
            $table->unsignedInteger('id_matricula');
            $table->decimal('puntaje_obtenido', 6, 2);
            $table->integer('puesto_general')->nullable();
            $table->integer('puesto_carrera')->nullable();
            $table->boolean('es_rezagado')->default(false);
            $table->date('fecha_rendicion')->nullable();
            $table->string('estado_ranking', 22)->default('pendiente_actualizar');
            $table->index('id_simulacro');
            $table->foreign('id_simulacro')->references('id_simulacro')->on('simulacros')->restrictOnDelete();
            $table->foreign('id_matricula')->references('id_matricula')->on('matriculas')->restrictOnDelete();
            $table->unique(['id_simulacro', 'id_matricula']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_simulacros');
    }
};
