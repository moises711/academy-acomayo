<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congelamientos', function (Blueprint $table): void {
            $table->increments('id_congelamiento');
            $table->unsignedInteger('id_matricula');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('motivo', 255)->nullable();
            $table->decimal('monto_a_favor', 10, 2)->default(0);
            $table->foreign('id_matricula')->references('id_matricula')->on('matriculas')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congelamientos');
    }
};
