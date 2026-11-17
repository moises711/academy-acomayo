<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table): void {
            $table->increments('id_evaluacion');
            $table->unsignedInteger('id_grupo');
            $table->unsignedInteger('id_curso');
            $table->string('titulo', 150);
            $table->string('tipo', 10)->default('Práctica');
            $table->date('fecha');
            $table->decimal('nota_maxima', 5, 2)->default(20);
            $table->decimal('peso', 5, 2)->default(1);
            $table->foreign('id_grupo')->references('id_grupo')->on('grupos')->restrictOnDelete();
            $table->foreign('id_curso')->references('id_curso')->on('cursos')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};
