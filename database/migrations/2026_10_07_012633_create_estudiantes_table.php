<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table): void {
            $table->increments('id_estudiante');
            $table->string('dni', 15)->unique();
            $table->string('nombre', 80);
            $table->string('apellido', 80);
            $table->string('email', 120)->nullable()->unique();
            $table->string('telefono', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('foto_path', 255)->nullable();
            $table->string('colegio_procedencia', 150)->nullable();
            $table->integer('anio_egreso')->nullable();
            $table->string('estado', 10)->default('activo');
            $table->uuid('codigo_qr')->unique();
            $table->timestamp('qr_regenerado_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->index(['apellido', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
