<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales', function (Blueprint $table): void {
            $table->increments('id_material');
            $table->unsignedInteger('id_curso');
            $table->unsignedInteger('id_grupo')->nullable();
            $table->unsignedInteger('id_usuario');
            $table->string('titulo', 150);
            $table->string('tipo', 8);
            $table->string('url', 500);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('id_curso')->references('id_curso')->on('cursos')->restrictOnDelete();
            $table->foreign('id_grupo')->references('id_grupo')->on('grupos')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales');
    }
};
