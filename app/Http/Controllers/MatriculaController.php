<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatriculaRequest;
use App\Http\Requests\UpdateMatriculaRequest;
use App\Models\Beca;
use App\Models\Carrera;
use App\Models\Ciclo;
use App\Models\Cuota;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Matricula;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MatriculaController extends Controller
{
    public function index(Request $request): Response
    {
        $matriculas = Matricula::query()
            ->with(['estudiante', 'ciclo', 'grupo', 'beca', 'carrera'])
            ->when($request->filled('estado'), fn ($query) => $query->where('estado_matricula', $request->string('estado')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Matriculas/Index', [
            'matriculas' => [
                'data' => $matriculas->items(),
                'links' => [
                    'first' => $matriculas->url(1),
                    'last' => $matriculas->url($matriculas->lastPage()),
                    'prev' => $matriculas->previousPageUrl(),
                    'next' => $matriculas->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $matriculas->currentPage(),
                    'from' => $matriculas->firstItem(),
                    'last_page' => $matriculas->lastPage(),
                    'path' => $matriculas->path(),
                    'per_page' => $matriculas->perPage(),
                    'to' => $matriculas->lastItem(),
                    'total' => $matriculas->total(),
                    'links' => $matriculas->linkCollection()->toArray(),
                ],
            ],
            'filters' => $request->only(['estado']),
            'stats' => [
                'total' => Matricula::query()->count(),
                'inscritos' => Matricula::query()->where('estado_matricula', 'inscrito')->count(),
                'retirados' => Matricula::query()->where('estado_matricula', 'retirado')->count(),
                'congelados' => Matricula::query()->where('estado_matricula', 'congelado')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Matriculas/Create', [
            'estudiantes' => Estudiante::query()->activo()->orderBy('apellido')->get(['id_estudiante', 'dni', 'nombre', 'apellido', 'email']),
            'ciclos' => Ciclo::query()->orderByDesc('fecha_inicio')->get(['id_ciclo', 'nombre_ciclo', 'modalidad']),
            'grupos' => Grupo::query()->with('ciclo')->orderBy('nombre')->get(),
            'becas' => Beca::query()->where('activa', true)->orderBy('nombre')->get(['id_beca', 'nombre', 'porcentaje_descuento']),
            'carreras' => Carrera::query()->with('universidad', 'area')->orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreMatriculaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $plan = $validated['plan'];
        unset($validated['plan']);

        DB::transaction(function () use ($validated, $plan): void {
            $matricula = Matricula::query()->create($validated);
            $numberOfFees = (int) $plan['numero_cuotas'];
            $originalCents = (int) round((float) $plan['monto_total'] * 100);
            $discountRate = $matricula->beca?->porcentaje_descuento ?? 0;
            $discountCents = (int) round($originalCents * (float) $discountRate / 100);
            $originalAmounts = $this->splitCents($originalCents, $numberOfFees);
            $discountAmounts = $this->splitCents($discountCents, $numberOfFees);
            $firstDueDate = CarbonImmutable::parse($plan['fecha_primera_cuota'])->startOfDay();

            foreach ($originalAmounts as $index => $feeOriginalCents) {
                $feeDiscountCents = $discountAmounts[$index];
                $feeFinalCents = $feeOriginalCents - $feeDiscountCents;

                Cuota::query()->create([
                    'id_matricula' => $matricula->id_matricula,
                    'concepto' => 'Cuota '.($index + 1).' de '.$numberOfFees,
                    'numero_cuota' => $index + 1,
                    'monto_original' => $this->formatCents($feeOriginalCents),
                    'monto_descuento' => $this->formatCents($feeDiscountCents),
                    'monto_final' => $this->formatCents($feeFinalCents),
                    'fecha_vencimiento' => $firstDueDate->addMonthsNoOverflow($index)->toDateString(),
                    'estado_pago' => $feeFinalCents === 0 ? 'Pagado' : 'Pendiente',
                ]);
            }
        });

        return redirect()->route('matriculas.index')->with('success', 'Matrícula registrada exitosamente.');
    }

    /** @return array<int, int> */
    private function splitCents(int $totalCents, int $parts): array
    {
        $baseAmount = intdiv($totalCents, $parts);
        $remainder = $totalCents % $parts;

        return array_map(
            fn (int $index): int => $baseAmount + ($index < $remainder ? 1 : 0),
            range(0, $parts - 1),
        );
    }

    private function formatCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    public function show(Matricula $matricula): Response
    {
        $matricula->load(['estudiante', 'ciclo']);

        return Inertia::render('Matriculas/Show', [
            'matricula' => $matricula,
        ]);
    }

    public function update(UpdateMatriculaRequest $request, Matricula $matricula): RedirectResponse
    {
        $matricula->update($request->validated());

        return back()->with('success', 'Matrícula actualizada.');
    }

    public function destroy(Matricula $matricula): RedirectResponse
    {
        $matricula->delete();

        return redirect()->route('matriculas.index')->with('success', 'Matrícula eliminada.');
    }
}
