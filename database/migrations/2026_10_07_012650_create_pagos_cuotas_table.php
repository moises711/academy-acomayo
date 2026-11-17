<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_cuotas', function (Blueprint $table): void {
            $table->unsignedInteger('id_pago');
            $table->unsignedInteger('id_cuota');
            $table->decimal('monto_aplicado', 10, 2);
            $table->foreign('id_pago')->references('id_pago')->on('pagos')->restrictOnDelete();
            $table->foreign('id_cuota')->references('id_cuota')->on('cuotas')->restrictOnDelete();
            $table->primary(['id_pago', 'id_cuota']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_cuotas');
    }
};
