<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table): void {
            $table->increments('id_nota');
            $table->unsignedInteger('id_evaluacion');
            $table->unsignedInteger('id_matricula');
            $table->decimal('nota', 5, 2);
            $table->string('observacion', 200)->nullable();
            $table->unsignedInteger('id_usuario')->nullable();
            $table->foreign('id_evaluacion')->references('id_evaluacion')->on('evaluaciones')->cascadeOnDelete();
            $table->foreign('id_matricula')->references('id_matricula')->on('matriculas')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->unique(['id_evaluacion', 'id_matricula']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
