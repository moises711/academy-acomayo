<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table): void {
            $table->increments('id_horario');
            $table->unsignedInteger('id_grupo');
            $table->unsignedInteger('id_curso');
            $table->unsignedInteger('id_profesor');
            $table->unsignedInteger('id_aula');
            $table->smallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->index('id_grupo');
            $table->foreign('id_grupo')->references('id_grupo')->on('grupos')->restrictOnDelete();
            $table->foreign('id_curso')->references('id_curso')->on('cursos')->restrictOnDelete();
            $table->foreign('id_profesor')->references('id_profesor')->on('profesores')->restrictOnDelete();
            $table->foreign('id_aula')->references('id_aula')->on('aulas')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
