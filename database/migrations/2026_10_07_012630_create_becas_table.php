<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('becas', function (Blueprint $table): void {
            $table->increments('id_beca');
            $table->string('nombre', 80);
            $table->string('tipo', 15);
            $table->decimal('porcentaje_descuento', 5, 2);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('becas');
    }
};
