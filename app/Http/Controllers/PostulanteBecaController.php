<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostulanteBecaRequest;
use App\Models\ConvocatoriaBeca;
use App\Models\PostulanteBeca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PostulanteBecaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Becas/Inscripcion', ['convocatorias' => self::publicCampaigns(), 'examenBecas' => self::publicExam()]);
    }

    public static function publicCampaigns(): mixed
    {
        return ConvocatoriaBeca::query()->where('estado', 'Abierta')->whereDate('fecha_inicio', '<=', today('America/Lima'))->whereDate('fecha_fin', '>=', today('America/Lima'))
            ->whereNotNull('id_simulacro_ab')->whereNotNull('id_simulacro_cd')->orderByDesc('id_convocatoria')
            ->get(['id_convocatoria', 'nombre', 'descripcion', 'fecha_fin']);
    }

    private static function publicExam(): ?array
    {
        $campaign = ConvocatoriaBeca::examenPublicadoEnWeb();

        return $campaign ? [
            'nombre' => $campaign->nombre,
            'fecha_examen' => $campaign->fecha_examen->toDateString(),
            'hora_inicio_examen' => substr($campaign->hora_inicio_examen, 0, 5),
            'hora_fin_examen' => substr($campaign->hora_fin_examen, 0, 5),
        ] : null;
    }

    public function store(StorePostulanteBecaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data): void {
            $campaign = ConvocatoriaBeca::query()->lockForUpdate()->findOrFail($data['id_convocatoria']);
            if (! $campaign->admiteInscripciones()) {
                throw ValidationException::withMessages(['id_convocatoria' => 'Esta convocatoria ya no recibe inscripciones.']);
            }
            // Una segunda solicitud nunca modifica datos ni revela información del DNI inscrito.
            PostulanteBeca::query()->firstOrCreate(['id_convocatoria' => $campaign->id_convocatoria, 'dni' => $data['dni']], ['telefono' => $data['telefono'], 'grupo' => $data['grupo'], 'estado' => 'Inscrito']);
        });

        return back()->with('success', 'Solicitud recibida. Si ya estabas inscrito, tu registro anterior se conserva.');
    }
}
