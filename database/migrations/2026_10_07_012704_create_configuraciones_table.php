<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table): void {
            $table->string('clave', 60)->primary();
            $table->text('valor');
            $table->string('descripcion', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
