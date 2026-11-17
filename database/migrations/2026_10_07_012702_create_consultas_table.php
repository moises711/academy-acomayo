<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table): void {
            $table->increments('id_consulta');
            $table->string('nombre', 120);
            $table->string('telefono', 20);
            $table->string('email', 120)->nullable();
            $table->string('interes', 150)->nullable();
            $table->text('mensaje')->nullable();
            $table->string('estado', 12)->default('Nueva');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
