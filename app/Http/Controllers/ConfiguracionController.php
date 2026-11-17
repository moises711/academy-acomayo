<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConfiguracionRequest;
use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends Controller
{
    public function index(): Response
    {
        $values = Configuracion::query()->whereIn('clave', [
            'asistencia.tolerancia_minutos',
            'asistencia.bloquear_si_deuda',
        ])->pluck('valor', 'clave');

        return Inertia::render('Configuracion/Index', [
            'configuracion' => [
                'tolerancia_minutos' => (int) ($values['asistencia.tolerancia_minutos'] ?? 10),
                'bloquear_si_deuda' => ($values['asistencia.bloquear_si_deuda'] ?? '0') === '1',
            ],
        ]);
    }

    public function update(UpdateConfiguracionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Configuracion::query()->updateOrCreate(
            ['clave' => 'asistencia.tolerancia_minutos'],
            ['valor' => (string) $data['tolerancia_minutos'], 'descripcion' => 'Minutos de tolerancia antes de marcar tardanza'],
        );
        Configuracion::query()->updateOrCreate(
            ['clave' => 'asistencia.bloquear_si_deuda'],
            ['valor' => $data['bloquear_si_deuda'] ? '1' : '0', 'descripcion' => 'Bloquea el ingreso si el estudiante tiene deuda vencida'],
        );

        return back()->with('success', 'Configuración de asistencia guardada.');
    }
}
