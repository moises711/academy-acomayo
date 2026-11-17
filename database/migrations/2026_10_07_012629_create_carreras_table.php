<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table): void {
            $table->increments('id_carrera');
            $table->unsignedInteger('id_universidad');
            $table->unsignedInteger('id_area');
            $table->string('nombre', 150);
            $table->decimal('puntaje_minimo', 6, 2)->nullable();
            $table->foreign('id_universidad')->references('id_universidad')->on('universidades')->restrictOnDelete();
            $table->foreign('id_area')->references('id_area')->on('areas_academicas')->restrictOnDelete();
            $table->unique(['id_universidad', 'nombre']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};
