<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apoderados', function (Blueprint $table): void {
            $table->increments('id_apoderado');
            $table->unsignedInteger('id_estudiante');
            $table->string('nombre', 80);
            $table->string('apellido', 80);
            $table->string('parentesco', 40)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 120)->nullable();
            $table->foreign('id_estudiante')->references('id_estudiante')->on('estudiantes')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apoderados');
    }
};
