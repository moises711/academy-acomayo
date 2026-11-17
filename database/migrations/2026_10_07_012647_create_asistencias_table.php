<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table): void {
            $table->increments('id_asistencia');
            $table->unsignedInteger('id_matricula');
            $table->unsignedInteger('id_horario');
            $table->date('fecha');
            $table->string('estado', 12);
            $table->string('origen', 8)->default('manual');
            $table->unsignedInteger('id_usuario_registro')->nullable();
            $table->unsignedBigInteger('id_registro_ingreso')->nullable();
            $table->time('hora_registro')->nullable();
            $table->index('fecha');
            $table->foreign('id_matricula')->references('id_matricula')->on('matriculas')->restrictOnDelete();
            $table->foreign('id_horario')->references('id_horario')->on('horarios')->restrictOnDelete();
            $table->foreign('id_usuario_registro')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->foreign('id_registro_ingreso')->references('id_registro')->on('registros_ingreso')->restrictOnDelete();
            $table->unique(['id_matricula', 'id_horario', 'fecha']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
