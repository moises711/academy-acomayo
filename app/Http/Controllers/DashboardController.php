<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Ciclo;
use App\Models\Consulta;
use App\Models\Cuota;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Simulacro;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->role === 'secretaria') {
            return Inertia::render('Dashboard', [
                'role' => 'secretaria',
                'stats' => [
                    'estudiantes_activos' => Estudiante::query()->activo()->count(),
                    'matriculas' => Matricula::query()->where('estado_matricula', 'inscrito')->count(),
                    'consultas_nuevas' => Consulta::query()->where('estado', 'Nueva')->count(),
                ],
                'matriculasRecientes' => Matricula::query()->with(['estudiante', 'ciclo', 'grupo'])
                    ->latest()->limit(6)->get(),
                'consultasNuevas' => Consulta::query()->where('estado', 'Nueva')->latest()->limit(5)->get(),
            ]);
        }

        if ($user->role === 'caja') {
            $pendingFees = Cuota::query()->whereIn('estado_pago', ['Pendiente', 'Parcial', 'Vencido'])
                ->with(['pagos' => fn ($query) => $query->where('estado', 'valido')])->get();
            $pendingAmountCents = $pendingFees->sum(function (Cuota $fee): int {
                $paidCents = $fee->pagos->sum(fn (Pago $pago): int => $this->decimalToCents((string) $pago->pivot->monto_aplicado));

                return max(0, $this->decimalToCents((string) $fee->monto_final) - $paidCents);
            });

            return Inertia::render('Dashboard', [
                'role' => 'caja',
                'stats' => [
                    'ingresos_mes' => Pago::query()->where('estado', 'valido')->whereBetween('fecha_pago', [now()->startOfMonth(), now()->endOfMonth()])->sum('monto'),
                    'pagos_pendientes' => $pendingFees->count(),
                    'cuotas_vencidas' => $pendingFees->where('estado_pago', 'Vencido')->count(),
                    'monto_pendiente' => $this->centsToDecimal($pendingAmountCents),
                ],
                'pagosRecientes' => Pago::query()->with(['matricula.estudiante', 'cuotas'])
                    ->where('estado', 'valido')->latest('fecha_pago')->limit(8)->get(),
            ]);
        }

        if ($user->role === 'profesor') {
            $clasesHoy = $user->id_profesor
                ? Horario::query()->with(['curso', 'grupo.ciclo', 'aula'])
                    ->where('id_profesor', $user->id_profesor)
                    ->where('dia_semana', now()->dayOfWeekIso)
                    ->orderBy('hora_inicio')->get()
                : collect();

            return Inertia::render('Dashboard', [
                'role' => 'profesor',
                'clasesHoy' => $clasesHoy,
                'stats' => [
                    'clases_hoy' => $clasesHoy->count(),
                    'grupos' => $clasesHoy->pluck('id_grupo')->unique()->count(),
                ],
            ]);
        }

        $pendingFees = Cuota::query()
            ->whereIn('estado_pago', ['Pendiente', 'Parcial', 'Vencido'])
            ->with(['pagos' => fn ($query) => $query->where('estado', 'valido')])
            ->get();

        $pendingAmountCents = $pendingFees->sum(function (Cuota $fee): int {
            $paidCents = $fee->pagos->sum(fn (Pago $pago): int => $this->decimalToCents((string) $pago->pivot->monto_aplicado));

            return max(0, $this->decimalToCents((string) $fee->monto_final) - $paidCents);
        });

        $incomeByMonth = collect(range(5, 0))->map(function (int $monthsAgo): array {
            $month = now()->subMonths($monthsAgo);

            return [
                'mes' => $month->locale('es')->translatedFormat('M'),
                'monto' => Pago::query()->where('estado', 'valido')
                    ->whereBetween('fecha_pago', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->sum('monto'),
            ];
        });
        $simulacroAverages = Simulacro::query()->withAvg('resultados', 'puntaje_obtenido')
            ->with('ciclo:id_ciclo,nombre_ciclo')->latest('fecha_aplicacion')->limit(6)->get()
            ->map(fn (Simulacro $simulacro): array => [
                'titulo' => $simulacro->titulo,
                'promedio' => round((float) ($simulacro->resultados_avg_puntaje_obtenido ?? 0), 2),
                'maximo' => (float) $simulacro->puntaje_maximo,
            ]);

        return Inertia::render('Dashboard', [
            'role' => 'admin',
            'stats' => [
                'estudiantes' => Estudiante::query()->count(),
                'estudiantes_activos' => Estudiante::query()->activo()->count(),
                'matriculas' => Matricula::query()->count(),
                'ciclos' => Ciclo::query()->count(),
                'cursos' => Curso::query()->count(),
                'pagos_pendientes' => $pendingFees->count(),
                'monto_pendiente' => $this->centsToDecimal($pendingAmountCents),
                'ingresos_mes' => Pago::query()->where('estado', 'valido')
                    ->whereBetween('fecha_pago', [now()->startOfMonth(), now()->endOfMonth()])->sum('monto'),
                'cuotas_vencidas' => Cuota::query()->whereDate('fecha_vencimiento', '<', today()->toDateString())
                    ->whereNotIn('estado_pago', ['Pagado', 'Anulado'])->count(),
                'asistencia_hoy' => Asistencia::query()->whereDate('fecha', today()->toDateString())->count(),
                'simulacros' => Simulacro::query()->count(),
                'horarios' => Horario::query()->count(),
            ],
            'ingresosMensuales' => $incomeByMonth,
            'promediosSimulacros' => $simulacroAverages,
            'matriculasRecientes' => Matricula::query()
                ->with(['estudiante', 'ciclo', 'carrera'])
                ->latest()
                ->limit(5)
                ->get(),
            'pagosRecientes' => Pago::query()
                ->with(['matricula.estudiante', 'cuotas'])
                ->latest('fecha_pago')
                ->limit(5)
                ->get(),
            'proximosSimulacros' => Simulacro::query()
                ->with('ciclo')
                ->withCount('resultados')
                ->whereDate('fecha_aplicacion', '>=', now()->toDateString())
                ->orderBy('fecha_aplicacion')
                ->limit(5)
                ->get(),
        ]);
    }

    private function decimalToCents(string $amount): int
    {
        [$units, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $units * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function centsToDecimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
