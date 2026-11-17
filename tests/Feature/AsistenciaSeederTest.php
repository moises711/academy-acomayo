<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Auditoria;
use App\Models\RegistroIngreso;
use Database\Seeders\AsistenciaSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AsistenciaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_historical_attendance_without_duplicating_or_overwriting_records(): void
    {
        Carbon::setTestNow('2026-10-21 23:00:00');
        try {
            $this->seed(DatabaseSeeder::class);

            $this->assertTrue(Asistencia::query()->where('estado', 'Falta')->exists());
            $this->assertTrue(Asistencia::query()->where('estado', 'Justificada')->exists());
            $this->assertTrue(Asistencia::query()->where('estado', 'Tarde')->exists());
            $this->assertTrue(Asistencia::query()->where('estado', 'Presente')->exists());
            $this->assertFalse(Asistencia::query()->whereDate('fecha', '>', '2026-10-21')->exists());
            $this->assertFalse(Asistencia::query()->whereIn('estado', ['Falta', 'Justificada'])->whereNotNull('id_registro_ingreso')->exists());

            $attendance = Asistencia::query()->firstOrFail();
            $attendance->update(['estado' => 'Justificada', 'origen' => 'manual']);
            $counts = [Asistencia::count(), RegistroIngreso::count(), Auditoria::count()];

            $this->seed(AsistenciaSeeder::class);

            $this->assertSame($counts, [Asistencia::count(), RegistroIngreso::count(), Auditoria::count()]);
            $this->assertSame('Justificada', $attendance->fresh()->estado);
        } finally {
            Carbon::setTestNow();
        }
    }
}
