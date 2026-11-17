<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_hoja', function (Blueprint $table): void {
            $table->increments('id_plantilla');
            $table->string('nombre', 100);
            $table->integer('num_preguntas');
            $table->integer('opciones_por_pregunta')->default(5);
            $table->json('config');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_hoja');
    }
};
