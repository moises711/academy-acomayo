<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclos', function (Blueprint $table): void {
            $table->increments('id_ciclo');
            $table->string('nombre_ciclo', 100);
            $table->string('tipo_ciclo', 10);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('modalidad', 12);
            $table->string('estado', 12)->default('Programado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos');
    }
};
