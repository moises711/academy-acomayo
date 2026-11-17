<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesor_cursos', function (Blueprint $table): void {
            $table->unsignedInteger('id_profesor');
            $table->unsignedInteger('id_curso');
            $table->foreign('id_profesor')->references('id_profesor')->on('profesores')->cascadeOnDelete();
            $table->foreign('id_curso')->references('id_curso')->on('cursos')->cascadeOnDelete();
            $table->primary(['id_profesor', 'id_curso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesor_cursos');
    }
};
