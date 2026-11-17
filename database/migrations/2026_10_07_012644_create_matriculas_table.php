<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table): void {
            $table->increments('id_matricula');
            $table->unsignedInteger('id_estudiante');
            $table->unsignedInteger('id_ciclo');
            $table->unsignedInteger('id_grupo')->nullable();
            $table->unsignedInteger('id_beca')->nullable();
            $table->unsignedInteger('id_carrera_objetivo')->nullable();
            $table->date('fecha_matricula');
            $table->date('fecha_retiro')->nullable();
            $table->string('estado_matricula', 10)->default('inscrito');
            $table->index('id_ciclo');
            $table->index('id_grupo');
            $table->foreign('id_estudiante')->references('id_estudiante')->on('estudiantes')->restrictOnDelete();
            $table->foreign('id_ciclo')->references('id_ciclo')->on('ciclos')->restrictOnDelete();
            $table->foreign('id_grupo')->references('id_grupo')->on('grupos')->restrictOnDelete();
            $table->foreign('id_beca')->references('id_beca')->on('becas')->restrictOnDelete();
            $table->foreign('id_carrera_objetivo')->references('id_carrera')->on('carreras')->restrictOnDelete();
            $table->unique(['id_estudiante', 'id_ciclo']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
