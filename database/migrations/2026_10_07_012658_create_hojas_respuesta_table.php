<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hojas_respuesta', function (Blueprint $table): void {
            $table->increments('id_hoja');
            $table->unsignedInteger('id_simulacro');
            $table->unsignedInteger('id_matricula')->nullable();
            $table->foreignId('id_postulante')->nullable()->unique()->constrained('postulantes_becas', 'id_postulante')->restrictOnDelete();
            $table->unsignedInteger('id_plantilla');
            $table->string('codigo_hoja', 40)->unique();
            $table->string('archivo_path', 255)->nullable();
            $table->string('estado', 10)->default('pendiente');
            $table->decimal('confianza', 5, 2)->nullable();
            $table->timestamp('procesada_en')->nullable();
            $table->unsignedInteger('id_usuario')->nullable();
            $table->index(['id_simulacro', 'estado']);
            $table->foreign('id_simulacro')->references('id_simulacro')->on('simulacros')->restrictOnDelete();
            $table->foreign('id_matricula')->references('id_matricula')->on('matriculas')->restrictOnDelete();
            $table->foreign('id_plantilla')->references('id_plantilla')->on('plantillas_hoja')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hojas_respuesta');
    }
};
