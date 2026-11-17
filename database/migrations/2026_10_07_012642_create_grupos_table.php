<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table): void {
            $table->increments('id_grupo');
            $table->unsignedInteger('id_ciclo');
            $table->unsignedInteger('id_area')->nullable();
            $table->string('nombre', 50);
            $table->string('turno', 8);
            $table->integer('capacidad')->nullable();
            $table->foreign('id_ciclo')->references('id_ciclo')->on('ciclos')->restrictOnDelete();
            $table->foreign('id_area')->references('id_area')->on('areas_academicas')->restrictOnDelete();
            $table->unique(['id_ciclo', 'nombre']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
