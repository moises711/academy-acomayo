<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id_usuario');
            $table->string('email', 120)->unique();
            $table->string('password', 255);
            $table->unsignedInteger('id_rol');
            $table->unsignedInteger('id_profesor')->nullable()->unique();
            $table->unsignedInteger('id_estudiante')->nullable()->unique();
            $table->unsignedInteger('id_apoderado')->nullable()->unique();
            $table->timestamp('ultimo_login')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('name');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->foreign('id_rol')->references('id_rol')->on('roles')->restrictOnDelete();
            $table->foreign('id_profesor')->references('id_profesor')->on('profesores')->restrictOnDelete();
            $table->foreign('id_estudiante')->references('id_estudiante')->on('estudiantes')->restrictOnDelete();
            $table->foreign('id_apoderado')->references('id_apoderado')->on('apoderados')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
