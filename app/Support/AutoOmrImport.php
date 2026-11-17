<?php

namespace App\Support;

use App\Models\Auditoria;
use App\Models\ClaveRespuesta;
use App\Models\ConvocatoriaBeca;
use App\Models\HojaRespuesta;
use App\Models\Matricula;
use App\Models\PostulanteBeca;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AutoOmrImport
{
    public function apply(HojaRespuesta $sheet, array $reading): bool
    {
        $template = $sheet->plantillahoja;
        $answers = collect($reading['respuestas'] ?? []);
        $numbers = collect($template->config['questions'])->pluck('numero_pregunta')->sort()->values()->all();
        $allowed = array_slice(['A', 'B', 'C', 'D', 'E'], 0, $template->opciones_por_pregunta);
        if (($template->config['diseno'] ?? '') !== 'compacto'
            || $answers->pluck('numero_pregunta')->map(fn ($n) => (int) $n)->sort()->values()->all() !== $numbers
            || $answers->contains(fn ($a) => ($a['estado'] === 'valida' && ! in_array($a['marcada'] ?? null, $allowed, true)) || ($a['estado'] !== 'valida' && ! empty($a['marcada'])))) {
            throw ValidationException::withMessages(['lecturas' => 'La lectura no coincide con las preguntas y alternativas de la plantilla.']);
        }
        foreach ($answers as $answer) {
            DB::table('respuestas_hoja')->insert(['id_hoja' => $sheet->id_hoja, 'numero_pregunta' => $answer['numero_pregunta'], 'marcada' => $answer['marcada'] ?? null, 'estado' => $answer['estado']]);
        }
        $campaign = ConvocatoriaBeca::query()->where(fn ($q) => $q->where('id_simulacro_ab', $sheet->id_simulacro)->orWhere('id_simulacro_cd', $sheet->id_simulacro))->lockForUpdate()->first();
        $applicant = null;
        $matricula = null;
        if (! empty($reading['dni'])) {
            if ($campaign) {
                $applicant = PostulanteBeca::query()->with('convocatoria')->where('id_convocatoria', $campaign->id_convocatoria)->where('dni', $reading['dni'])->lockForUpdate()->first();
                if ($applicant && ($applicant->examenId() !== (int) $sheet->id_simulacro || $applicant->grupo !== ($reading['grupo'] ?? null) || $applicant->hoja()->exists())) {
                    $applicant = null;
                }
            } else {
                $matches = Matricula::query()->where('id_ciclo', $sheet->simulacro->id_ciclo)->where('estado_matricula', 'inscrito')->whereHas('estudiante', fn ($q) => $q->where('dni', $reading['dni']))->lockForUpdate()->get();
                if ($matches->count() === 1 && ! HojaRespuesta::query()->where('id_simulacro', $sheet->id_simulacro)->where('id_matricula', $matches->first()->id_matricula)->where('estado', 'procesada')->exists()) {
                    $matricula = $matches->first();
                }
            }
        }
        $keys = ClaveRespuesta::query()->where('id_simulacro', $sheet->id_simulacro)->orderBy('numero_pregunta')->pluck('numero_pregunta')->all();
        $ready = ($applicant || $matricula) && $keys === $numbers && ! $answers->contains(fn ($a) => in_array($a['estado'], ['dudosa', 'multiple'], true));
        $sheet->update(['id_postulante' => $applicant?->id_postulante, 'id_matricula' => $matricula?->id_matricula, 'estado' => $ready ? 'procesada' : 'revision', 'procesada_en' => $ready ? now() : null]);
        if ($applicant) {
            if ($ready) {
                app(BecaGrading::class)->grade($sheet);
            } else {
                $applicant->update(['nota' => null, 'estado' => 'Revision']);
            }
        } elseif ($ready) {
            $sheet->simulacro->calificarHoja($sheet);
        }
        Auditoria::query()->create(['id_usuario' => $sheet->id_usuario, 'accion' => 'crear', 'tabla' => 'respuestas_hoja', 'id_registro' => (string) $sheet->id_hoja, 'datos_despues' => ['origen' => 'camara', 'id_postulante' => $applicant?->id_postulante, 'id_matricula' => $matricula?->id_matricula, 'respuestas' => $answers->all(), 'estado' => $sheet->estado], 'ip' => request()->ip()]);

        return (bool) $ready;
    }
}
