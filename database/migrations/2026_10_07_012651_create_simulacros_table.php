<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulacros', function (Blueprint $table): void {
            $table->increments('id_simulacro');
            $table->unsignedInteger('id_ciclo');
            $table->string('titulo', 150);
            $table->date('fecha_aplicacion');
            $table->decimal('puntaje_maximo', 6, 2);
            $table->foreign('id_ciclo')->references('id_ciclo')->on('ciclos')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulacros');
    }
};
