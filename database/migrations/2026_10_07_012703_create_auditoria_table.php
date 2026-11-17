<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table): void {
            $table->bigIncrements('id_auditoria');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->string('accion', 10);
            $table->string('tabla', 60);
            $table->string('id_registro', 40);
            $table->json('datos_antes')->nullable();
            $table->json('datos_despues')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tabla', 'id_registro']);
            $table->foreign('id_usuario')->references('id_usuario')->on('users')->restrictOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
