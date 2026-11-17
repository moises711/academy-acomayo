<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnularPagoRequest;
use App\Http\Requests\StoreCuotaRequest;
use App\Http\Requests\StorePagoRequest;
use App\Models\Auditoria;
use App\Models\Cuota;
use App\Models\Matricula;
use App\Models\Pago;
use App\Support\InertiaPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PagosCuotaController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'estado' => ['nullable', 'in:Pendiente,Parcial,Pagado,Vencido,Anulado'],
            'buscar' => ['nullable', 'string', 'max:100'],
            'fecha' => ['nullable', 'date'],
        ]);
        $fechaCaja = $filters['fecha'] ?? today()->toDateString();
        $cuotas = Cuota::query()
            ->with(['matricula.estudiante', 'matricula.ciclo', 'pagos' => fn ($query) => $query->where('estado', 'valido')])
            ->when($request->filled('estado'), fn ($query) => $query->where('estado_pago', $filters['estado']))
            ->when($request->filled('buscar'), fn ($query) => $query->whereHas('matricula.estudiante', fn ($studentQuery) => $studentQuery
                ->where('dni', 'like', "%{$filters['buscar']}%")
                ->orWhere('nombre', 'like', "%{$filters['buscar']}%")
                ->orWhere('apellido', 'like', "%{$filters['buscar']}%")))
            ->orderBy('fecha_vencimiento')
            ->paginate(10)
            ->withQueryString();

        $cuotas->getCollection()->each(function (Cuota $cuota): void {
            $pagado = $cuota->pagos->sum(fn (Pago $pago): int => $this->decimalToCents((string) $pago->pivot->monto_aplicado));
            $cuota->setAttribute('monto_pagado', $this->centsToDecimal($pagado));
            $cuota->setAttribute('monto_pendiente', $this->centsToDecimal(max(0, $this->decimalToCents((string) $cuota->monto_final) - $pagado)));
            $cuota->setRelation('pagos', collect());
        });

        $openFees = Cuota::query()
            ->whereIn('estado_pago', ['Pendiente', 'Parcial', 'Vencido'])
            ->with(['pagos' => fn ($query) => $query->where('estado', 'valido')])
            ->get();
        $openFees->each(function (Cuota $fee): void {
            $paidCents = $fee->pagos->sum(fn (Pago $pago): int => $this->decimalToCents((string) $pago->pivot->monto_aplicado));
            $fee->setAttribute('monto_pagado', $this->centsToDecimal($paidCents));
            $fee->setAttribute('monto_pendiente', $this->centsToDecimal(max(0, $this->decimalToCents((string) $fee->monto_final) - $paidCents)));
            $fee->setRelation('pagos', collect());
        });
        $pendingCents = $openFees->sum(fn (Cuota $fee): int => $this->decimalToCents((string) $fee->monto_pendiente));

        $payments = Pago::query()
            ->with(['matricula.estudiante', 'usuario', 'cuotas'])
            ->latest('fecha_pago')
            ->paginate(10, ['*'], 'pagina_pagos')
            ->withQueryString();

        return Inertia::render('Pagos/Index', [
            'cuotas' => InertiaPaginator::from($cuotas),
            'cuotasDisponibles' => $openFees->filter(fn (Cuota $fee): bool => $this->decimalToCents((string) $fee->monto_pendiente) > 0)->values(),
            'pagos' => InertiaPaginator::from($payments),
            'matriculas' => Matricula::query()
                ->with(['estudiante', 'ciclo'])
                ->where('estado_matricula', 'inscrito')
                ->orderByDesc('fecha_matricula')
                ->get(),
            'filters' => $filters,
            'resumenCaja' => Pago::query()
                ->selectRaw('metodo_pago, COUNT(*) as operaciones, SUM(monto) as total')
                ->where('estado', 'valido')
                ->whereDate('fecha_pago', $fechaCaja)
                ->groupBy('metodo_pago')
                ->orderBy('metodo_pago')
                ->get(),
            'stats' => [
                'cuotas_pendientes' => $openFees->count(),
                'cuotas_vencidas' => $openFees->filter(fn (Cuota $fee): bool => in_array($fee->estado_pago, ['Pendiente', 'Parcial', 'Vencido'], true)
                    && $fee->fecha_vencimiento->lt(today()))->count(),
                'deuda_pendiente' => $this->centsToDecimal($pendingCents),
                'recaudado_hoy' => $this->centsToDecimal(Pago::query()
                    ->where('estado', 'valido')
                    ->whereDate('fecha_pago', $fechaCaja)
                    ->get(['monto'])
                    ->sum(fn (Pago $pago): int => $this->decimalToCents((string) $pago->monto))),
            ],
        ]);
    }

    public function storeCuota(StoreCuotaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $expectedFinal = $this->decimalToCents($data['monto_original']) - $this->decimalToCents($data['monto_descuento']);

        if ($expectedFinal !== $this->decimalToCents($data['monto_final'])) {
            throw ValidationException::withMessages(['monto_final' => 'El monto final debe ser el monto original menos el descuento.']);
        }

        $cuota = Cuota::query()->create([
            ...$data,
            'estado_pago' => $data['fecha_vencimiento'] < today()->toDateString() ? 'Vencido' : 'Pendiente',
        ]);
        Auditoria::query()->create([
            'id_usuario' => $request->user()->id_usuario,
            'accion' => 'crear',
            'tabla' => 'cuotas',
            'id_registro' => (string) $cuota->id_cuota,
            'datos_despues' => $cuota->only(['id_matricula', 'concepto', 'monto_original', 'monto_descuento', 'monto_final', 'fecha_vencimiento', 'estado_pago']),
            'ip' => $request->ip(),
        ]);

        return back()->with('success', 'Cuota creada correctamente.');
    }

    public function store(StorePagoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $paymentCents = $this->decimalToCents($validated['monto']);
        $applications = collect($validated['aplicaciones']);
        $applicationsCents = $applications->sum(fn (array $application): int => $this->decimalToCents($application['monto_aplicado']));

        if ($paymentCents !== $applicationsCents) {
            throw ValidationException::withMessages(['monto' => 'La suma aplicada a cuotas debe coincidir con el importe cobrado.']);
        }

        DB::transaction(function () use ($applications, $paymentCents, $request, $validated): void {
            $fees = Cuota::query()
                ->whereIn('id_cuota', $applications->pluck('id_cuota'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id_cuota');

            if ($fees->count() !== $applications->count()) {
                throw ValidationException::withMessages(['aplicaciones' => 'Una o más cuotas ya no están disponibles.']);
            }

            foreach ($applications as $application) {
                /** @var Cuota $fee */
                $fee = $fees->get($application['id_cuota']);
                $paidCents = $fee->pagos()
                    ->where('pagos.estado', 'valido')
                    ->sum('pagos_cuotas.monto_aplicado');
                $remainingCents = $this->decimalToCents((string) $fee->monto_final) - $this->decimalToCents((string) $paidCents);
                $applicationCents = $this->decimalToCents($application['monto_aplicado']);

                if ((int) $fee->id_matricula !== (int) $validated['id_matricula'] || $fee->estado_pago === 'Anulado' || $applicationCents > $remainingCents) {
                    throw ValidationException::withMessages([
                        'aplicaciones' => "La cuota {$fee->concepto} no pertenece a la matrícula o excede su saldo.",
                    ]);
                }
            }

            $receipt = $validated['nro_comprobante'] ?? $this->generateReceiptNumber();
            $payment = Pago::query()->create([
                'id_matricula' => $validated['id_matricula'],
                'id_usuario' => $request->user()->id_usuario,
                'fecha_pago' => now(),
                'monto' => $this->centsToDecimal($paymentCents),
                'metodo_pago' => $validated['metodo_pago'],
                'tipo_comprobante' => $validated['tipo_comprobante'] ?? 'Recibo',
                'nro_comprobante' => $receipt,
            ]);

            foreach ($applications as $application) {
                $payment->cuotas()->attach($application['id_cuota'], [
                    'monto_aplicado' => $application['monto_aplicado'],
                ]);
            }

            Auditoria::query()->create([
                'id_usuario' => $request->user()->id_usuario,
                'accion' => 'crear',
                'tabla' => 'pagos',
                'id_registro' => (string) $payment->id_pago,
                'datos_despues' => $payment->only(['id_matricula', 'id_usuario', 'fecha_pago', 'monto', 'metodo_pago', 'tipo_comprobante', 'nro_comprobante', 'estado']),
                'ip' => $request->ip(),
            ]);

            foreach ($fees as $fee) {
                $this->recalculateFeeStatus($fee, $request);
            }
        });

        return back()->with('success', 'Cobro registrado y comprobante generado.');
    }

    public function anular(AnularPagoRequest $request, Pago $pago): RedirectResponse
    {
        DB::transaction(function () use ($pago, $request): void {
            $payment = Pago::query()->lockForUpdate()->findOrFail($pago->id_pago);

            if ($payment->estado !== 'valido') {
                throw ValidationException::withMessages(['motivo_anulacion' => 'Este pago ya se encuentra anulado.']);
            }

            $fees = $payment->cuotas()->lockForUpdate()->get();
            $before = $payment->only(['estado', 'motivo_anulacion', 'anulado_por', 'anulado_en']);
            $payment->anular($request->user(), $request->validated('motivo_anulacion'));

            Auditoria::query()->create([
                'id_usuario' => $request->user()->id_usuario,
                'accion' => 'anular',
                'tabla' => 'pagos',
                'id_registro' => (string) $payment->id_pago,
                'datos_antes' => $before,
                'datos_despues' => $payment->only(['estado', 'motivo_anulacion', 'anulado_por', 'anulado_en']),
                'ip' => $request->ip(),
            ]);

            foreach ($fees as $fee) {
                $this->recalculateFeeStatus($fee, $request);
            }
        });

        return back()->with('success', 'Pago anulado. Las cuotas se actualizaron y el registro quedó en auditoría.');
    }

    public function comprobante(Pago $pago): Response
    {
        return Inertia::render('Pagos/Comprobante', [
            'pago' => $pago->load(['matricula.estudiante', 'matricula.ciclo', 'usuario', 'cuotas']),
        ]);
    }

    private function recalculateFeeStatus(Cuota $fee, StorePagoRequest|AnularPagoRequest $request): void
    {
        $before = $fee->estado_pago;
        $paidCents = $fee->pagos()
            ->where('pagos.estado', 'valido')
            ->sum('pagos_cuotas.monto_aplicado');
        $paidCents = $this->decimalToCents((string) $paidCents);
        $totalCents = $this->decimalToCents((string) $fee->monto_final);
        $status = $paidCents >= $totalCents
            ? 'Pagado'
            : ($paidCents > 0 ? 'Parcial' : ($fee->fecha_vencimiento->lt(today()) ? 'Vencido' : 'Pendiente'));

        if ($before === $status) {
            return;
        }

        $fee->update(['estado_pago' => $status]);
        Auditoria::query()->create([
            'id_usuario' => $request->user()->id_usuario,
            'accion' => 'editar',
            'tabla' => 'cuotas',
            'id_registro' => (string) $fee->id_cuota,
            'datos_antes' => ['estado_pago' => $before],
            'datos_despues' => ['estado_pago' => $status],
            'ip' => $request->ip(),
        ]);
    }

    private function generateReceiptNumber(): string
    {
        do {
            $number = 'REC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Pago::query()->where('nro_comprobante', $number)->exists());

        return $number;
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
