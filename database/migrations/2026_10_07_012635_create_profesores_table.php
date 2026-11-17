<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesores', function (Blueprint $table): void {
            $table->increments('id_profesor');
            $table->string('dni', 15)->unique();
            $table->string('nombre', 80);
            $table->string('apellido', 80);
            $table->string('email', 120)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('estado', 10)->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesores');
    }
};
