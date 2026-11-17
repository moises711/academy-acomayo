<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Cuota;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Simulacro;
use Inertia\Inertia;
use Inertia\Response;

class ReporteController extends Controller
{
    public function index(): Response
    {
        $startOfPeriod = now()->startOfMonth()->subMonths(5);
        $monthlyPayments = Pago::query()
            ->where('estado', 'valido')
            ->whereBetween('fecha_pago', [$startOfPeriod, now()->endOfMonth()])
            ->get(['fecha_pago', 'monto'])
            ->groupBy(fn (Pago $payment): string => $payment->fecha_pago->format('Y-m'));
        $monthlyIncome = collect(range(5, 0))->map(function (int $monthsAgo) use ($monthlyPayments): array {
            $month = now()->startOfMonth()->subMonths($monthsAgo);
            $paymentRows = $monthlyPayments->get($month->format('Y-m'), collect());
            $amount = $paymentRows->sum(fn (Pago $payment): int => $this->decimalToCents((string) $payment->monto));

            return [
                'periodo' => $month->format('Y-m'),
                'mes' => $month->locale('es')->translatedFormat('M Y'),
                'monto' => $this->centsToDecimal($amount),
            ];
        });
        $overdueFees = Cuota::query()
            ->whereDate('fecha_vencimiento', '<', today()->toDateString())
            ->whereNotIn('estado_pago', ['Pagado', 'Anulado'])
            ->with(['pagos' => fn ($query) => $query->where('estado', 'valido')])
            ->get();
        $overdueCents = $overdueFees->sum(function (Cuota $fee): int {
            $paid = $fee->pagos->sum(fn (Pago $payment): int => $this->decimalToCents((string) $payment->pivot->monto_aplicado));

            return max(0, $this->decimalToCents((string) $fee->monto_final) - $paid);
        });

        return Inertia::render('Reportes/Index', [
            'resumen' => [
                'estudiantes' => Estudiante::query()->count(),
                'estudiantes_activos' => Estudiante::query()->where('estado', 'activo')->count(),
                'matriculas_activas' => Matricula::query()->where('estado_matricula', 'inscrito')->count(),
                'cuotas_vencidas' => $overdueFees->count(),
                'deuda_vencida' => $this->centsToDecimal($overdueCents),
                'ingresos_mes' => $this->centsToDecimal(Pago::query()
                    ->where('estado', 'valido')
                    ->whereBetween('fecha_pago', [now()->startOfMonth(), now()->endOfMonth()])
                    ->get(['monto'])
                    ->sum(fn (Pago $payment): int => $this->decimalToCents((string) $payment->monto))),
                'total_simulacros' => Simulacro::query()->count(),
                'estudiantes_por_estado' => Estudiante::query()
                    ->selectRaw('estado, COUNT(*) as total')
                    ->groupBy('estado')
                    ->orderBy('estado')
                    ->get(['estado', 'total']),
                'matriculas_por_estado' => Matricula::query()
                    ->selectRaw('estado_matricula as estado, COUNT(*) as total')
                    ->groupBy('estado_matricula')
                    ->orderBy('estado_matricula')
                    ->get(),
                'cursos_por_categoria' => Curso::query()
                    ->selectRaw('categoria, COUNT(*) as total')
                    ->groupBy('categoria')
                    ->orderBy('categoria')
                    ->get(['categoria', 'total']),
            ],
            'ingresosPorMes' => $monthlyIncome,
            'ciclos' => Ciclo::query()
                ->withCount(['matriculas', 'simulacros', 'grupos'])
                ->orderByDesc('fecha_inicio')
                ->limit(10)
                ->get(),
            'topEstudiantes' => Estudiante::query()
                ->withCount(['matriculas' => fn ($query) => $query->where('estado_matricula', 'inscrito')])
                ->orderByDesc('matriculas_count')
                ->orderBy('apellido')
                ->limit(8)
                ->get(['id_estudiante', 'dni', 'nombre', 'apellido', 'estado']),
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
