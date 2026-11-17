<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postulantes_becas', function (Blueprint $table): void {
            $table->id('id_postulante');
            $table->foreignId('id_convocatoria')->constrained('convocatorias_becas', 'id_convocatoria')->restrictOnDelete();
            $table->string('dni', 8)->index();
            $table->string('telefono', 9);
            $table->enum('grupo', ['A', 'B', 'C', 'D']);
            $table->decimal('nota', 10, 2)->nullable();
            $table->enum('estado', ['Inscrito', 'Revision', 'Calificado', 'Becario'])->default('Inscrito');
            $table->timestamps();
            $table->unique(['id_convocatoria', 'dni']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulantes_becas');
    }
};
