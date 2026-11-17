<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Apoderado;
use App\Models\Asistencia;
use App\Models\Cuota;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Material;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Pago;
use App\Models\ResultadosSimulacro;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['estudiante', 'apoderado'], true), 403);

        $students = $this->studentsFor($user->role, $user->id_estudiante, $user->id_apoderado);
        abort_if($students->isEmpty(), 404, 'No hay estudiantes vinculados a esta cuenta.');

        $validated = $request->validate([
            'id_estudiante' => ['nullable', 'integer'],
        ]);
        $student = isset($validated['id_estudiante'])
            ? $students->firstWhere('id_estudiante', (int) $validated['id_estudiante'])
            : $students->first();
        abort_if(! $student, 404);

        $matriculas = Matricula::query()
            ->with(['ciclo', 'grupo', 'beca', 'carrera'])
            ->where('id_estudiante', $student->id_estudiante)
            ->orderByDesc('fecha_matricula')
            ->get();
        $matricula = $matriculas->first(fn (Matricula $item): bool => $item->estado_matricula === 'inscrito') ?? $matriculas->first();
        $schedule = collect();
        $announcements = collect();
        $materials = collect();
        $attendances = collect();
        $notes = collect();
        $simulacros = collect();
        $payments = collect();
        $fees = collect();
        $attendancePercent = 0;
        $balanceCents = 0;
        $paidCents = 0;

        if ($matricula) {
            $schedule = $matricula->id_grupo
                ? Horario::query()->with(['curso', 'profesor', 'aula'])
                    ->where('id_grupo', $matricula->id_grupo)
                    ->orderBy('dia_semana')->orderBy('hora_inicio')->get()
                : collect();
            $announcements = Anuncio::query()->with('user:id_usuario,name')
                ->where(function ($query) use ($matricula): void {
                    $query->whereNull('id_ciclo')->orWhere('id_ciclo', $matricula->id_ciclo);
                })
                ->where(function ($query) use ($matricula): void {
                    $query->whereNull('id_grupo')->orWhere('id_grupo', $matricula->id_grupo);
                })
                ->where('publicado_en', '<=', now())
                ->where(fn ($query) => $query->whereNull('vence_en')->orWhere('vence_en', '>=', now()))
                ->latest('publicado_en')->limit(8)->get();
            $materialQuery = Material::query()->with('curso:id_curso,nombre_curso')
                ->where(function ($query) use ($matricula): void {
                    $query->whereNull('id_grupo')->orWhere('id_grupo', $matricula->id_grupo);
                });
            $courseIds = $schedule->pluck('id_curso');
            $materials = $materialQuery->whereIn('id_curso', $courseIds)->latest('created_at')->limit(30)->get();
            $attendances = Asistencia::query()->with('horario.curso:id_curso,nombre_curso')
                ->where('id_matricula', $matricula->id_matricula)
                ->latest('fecha')->limit(60)->get();
            $attendanceCounts = Asistencia::query()->where('id_matricula', $matricula->id_matricula)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN estado IN ('Presente', 'Tarde') THEN 1 ELSE 0 END) as attended")
                ->first();
            $attendancePercent = (int) $attendanceCounts->total > 0
                ? (int) round(((int) $attendanceCounts->attended / (int) $attendanceCounts->total) * 100)
                : 0;
            $notes = Nota::query()->with('evaluacion.curso:id_curso,nombre_curso')
                ->where('id_matricula', $matricula->id_matricula)
                ->latest('created_at')->limit(30)->get();
            $simulacros = ResultadosSimulacro::query()->with(['simulacro', 'matricula'])
                ->where('id_matricula', $matricula->id_matricula)->latest('fecha_rendicion')->limit(12)->get();
            $fees = Cuota::query()->with(['pagos' => fn ($query) => $query->where('estado', 'valido')])
                ->where('id_matricula', $matricula->id_matricula)->orderBy('fecha_vencimiento')->get();
            foreach ($fees as $fee) {
                $feePaidCents = $fee->pagos->sum(fn (Pago $payment): int => $this->decimalToCents((string) $payment->pivot->monto_aplicado));
                $feeFinalCents = $this->decimalToCents((string) $fee->monto_final);
                $paidCents += $feePaidCents;
                $balanceCents += max(0, $feeFinalCents - $feePaidCents);
                $fee->setAttribute('monto_pagado', $this->centsToDecimal($feePaidCents));
                $fee->setAttribute('monto_pendiente', $this->centsToDecimal(max(0, $feeFinalCents - $feePaidCents)));
            }
            $payments = Pago::query()->with('cuotas:id_cuota,concepto')
                ->where('id_matricula', $matricula->id_matricula)->where('estado', 'valido')
                ->latest('fecha_pago')->limit(20)->get();
        }

        return Inertia::render('Mi/Dashboard', [
            'estudiantes' => $students->map(fn (Estudiante $item): array => [
                'id_estudiante' => $item->id_estudiante,
                'nombre_completo' => trim($item->nombre.' '.$item->apellido),
            ])->values(),
            'estudiante' => [
                'id_estudiante' => $student->id_estudiante,
                'nombre' => $student->nombre,
                'apellido' => $student->apellido,
                'dni' => $student->dni,
                'email' => $student->email,
                'telefono' => $student->telefono,
                'codigo_qr' => $student->codigo_qr,
            ],
            'matriculas' => $matriculas,
            'matricula' => $matricula,
            'horario' => $schedule,
            'anuncios' => $announcements,
            'materiales' => $materials,
            'asistencias' => $attendances,
            'notas' => $notes,
            'simulacros' => $simulacros,
            'cuotas' => $fees,
            'pagos' => $payments,
            'resumen' => [
                'asistencia_porcentaje' => $attendancePercent,
                'pagado' => $this->centsToDecimal($paidCents),
                'deuda' => $this->centsToDecimal($balanceCents),
                'promedio' => $notes->isEmpty() ? null : round($notes->avg(fn (Nota $note): float => (float) $note->nota), 2),
            ],
            'esApoderado' => $user->role === 'apoderado',
            'diaSemanaHoy' => now()->dayOfWeekIso,
        ]);
    }

    public function comprobante(Request $request, Pago $pago): Response
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['estudiante', 'apoderado'], true), 403);
        abort_if($pago->estado !== 'valido', 404);

        $allowedStudentIds = $this->studentsFor($user->role, $user->id_estudiante, $user->id_apoderado)
            ->pluck('id_estudiante');
        $belongsToUser = Matricula::query()
            ->whereKey($pago->id_matricula)
            ->whereIn('id_estudiante', $allowedStudentIds)
            ->exists();
        abort_unless($belongsToUser, 404);

        return Inertia::render('Pagos/Comprobante', [
            'pago' => $pago->load(['matricula.estudiante', 'matricula.ciclo', 'usuario', 'cuotas']),
            'portal' => true,
            'returnUrl' => route('mi.index').'#pagos',
        ]);
    }

    /** @return Collection<int, Estudiante> */
    private function studentsFor(string $role, ?int $studentId, ?int $guardianId): Collection
    {
        if ($role === 'estudiante') {
            return $studentId ? Estudiante::query()->whereKey($studentId)->get() : collect();
        }

        $guardian = $guardianId ? Apoderado::query()->find($guardianId) : null;
        if (! $guardian) {
            return collect();
        }

        $guardianRecords = Apoderado::query()->where('id_apoderado', $guardian->id_apoderado)
            ->when($guardian->email, fn ($query) => $query->orWhere('email', $guardian->email))
            ->with('estudiante')
            ->get();

        return $guardianRecords->pluck('estudiante')->filter()->unique('id_estudiante')->values();
    }

    private function decimalToCents(string $amount): int
    {
        [$units, $fraction] = array_pad(explode('.', ltrim($amount, '-'), 2), 2, '0');
        $cents = ((int) $units * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return str_starts_with($amount, '-') ? -$cents : $cents;
    }

    private function centsToDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
