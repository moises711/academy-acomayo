<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_ingreso', function (Blueprint $table): void {
            $table->bigIncrements('id_registro');
            $table->unsignedInteger('id_matricula');
            $table->unsignedInteger('id_usuario');
            $table->string('tipo', 8)->default('ingreso');
            $table->string('metodo', 8)->default('qr');
            $table->timestamp('fecha_hora')->useCurrent();
            $table->string('observacion', 200)->nullable();
            $table->index(['id_matricula', 'fecha_hora']);
            $table->foreign('id_matricula')->references('id_matricula')->on('matriculas')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_ingreso');
    }
};
