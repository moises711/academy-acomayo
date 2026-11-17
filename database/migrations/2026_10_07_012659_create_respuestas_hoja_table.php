<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas_hoja', function (Blueprint $table): void {
            $table->unsignedInteger('id_hoja');
            $table->integer('numero_pregunta');
            $table->char('marcada', 1)->nullable();
            $table->string('estado', 10)->default('valida');
            $table->foreign('id_hoja')->references('id_hoja')->on('hojas_respuesta')->cascadeOnDelete();
            $table->primary(['id_hoja', 'numero_pregunta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_hoja');
    }
};
