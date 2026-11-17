<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claves_respuesta', function (Blueprint $table): void {
            $table->increments('id_clave');
            $table->unsignedInteger('id_simulacro');
            $table->unsignedInteger('id_curso');
            $table->integer('numero_pregunta');
            $table->char('respuesta_correcta', 1);
            $table->decimal('puntos_correcta', 5, 2)->default(1);
            $table->decimal('puntos_incorrecta', 5, 2)->default(0);
            $table->foreign('id_simulacro')->references('id_simulacro')->on('simulacros')->cascadeOnDelete();
            $table->foreign('id_curso')->references('id_curso')->on('cursos')->restrictOnDelete();
            $table->unique(['id_simulacro', 'numero_pregunta']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claves_respuesta');
    }
};
