<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_curso', function (Blueprint $table): void {
            $table->increments('id_resultado_curso');
            $table->unsignedInteger('id_resultado');
            $table->unsignedInteger('id_curso');
            $table->integer('correctas')->default(0);
            $table->integer('incorrectas')->default(0);
            $table->integer('en_blanco')->default(0);
            $table->decimal('puntaje', 6, 2);
            $table->foreign('id_resultado')->references('id_resultado')->on('resultados_simulacros')->cascadeOnDelete();
            $table->foreign('id_curso')->references('id_curso')->on('cursos')->restrictOnDelete();
            $table->unique(['id_resultado', 'id_curso']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_curso');
    }
};
