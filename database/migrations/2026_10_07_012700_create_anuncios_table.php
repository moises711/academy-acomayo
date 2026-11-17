<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anuncios', function (Blueprint $table): void {
            $table->increments('id_anuncio');
            $table->unsignedInteger('id_ciclo')->nullable();
            $table->unsignedInteger('id_grupo')->nullable();
            $table->unsignedInteger('id_usuario');
            $table->string('titulo', 150);
            $table->text('contenido');
            $table->timestamp('publicado_en')->useCurrent();
            $table->timestamp('vence_en')->nullable();
            $table->foreign('id_ciclo')->references('id_ciclo')->on('ciclos')->restrictOnDelete();
            $table->foreign('id_grupo')->references('id_grupo')->on('grupos')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncios');
    }
};
