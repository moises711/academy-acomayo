<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table): void {
            $table->increments('id_permiso');
            $table->string('codigo', 60)->unique();
            $table->string('modulo', 40);
            $table->string('descripcion', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
