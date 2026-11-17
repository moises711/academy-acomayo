<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aulas', function (Blueprint $table): void {
            $table->increments('id_aula');
            $table->string('nombre', 50)->unique();
            $table->string('tipo', 12);
            $table->integer('capacidad')->nullable();
            $table->string('enlace_virtual', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aulas');
    }
};
