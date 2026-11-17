<?php

namespace App\Support;

use App\Models\Auditoria;
use App\Models\Ciclo;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class CicloArchive
{
    // Orden inverso de dependencias; las fichas y la auditoría se conservan.
    public const KEYS = [
        'respuestas_hoja' => ['id_hoja', 'numero_pregunta'],
        'resultados_curso' => ['id_resultado_curso'],
        'notas' => ['id_nota'],
        'asistencias' => ['id_asistencia'],
        'registros_ingreso' => ['id_registro'],
        'congelamientos' => ['id_congelamiento'],
        'pagos_cuotas' => ['id_pago', 'id_cuota'],
        'pagos' => ['id_pago'],
        'cuotas' => ['id_cuota'],
        'pagos_docentes' => ['id_pago_docente'],
        'hojas_respuesta' => ['id_hoja'],
        'plantillas_hoja' => ['id_plantilla'],
        'resultados_simulacros' => ['id_resultado'],
        'claves_respuesta' => ['id_clave'],
        'evaluaciones' => ['id_evaluacion'],
        'simulacros' => ['id_simulacro'],
        'anuncios' => ['id_anuncio'],
        'materiales' => ['id_material'],
        'horarios' => ['id_horario'],
        'matriculas' => ['id_matricula'],
        'grupos' => ['id_grupo'],
        'ciclos' => ['id_ciclo'],
    ];

    /** @return array<string, Builder> */
    public function queries(Ciclo $ciclo): array
    {
        $groups = DB::table('grupos')->where('id_ciclo', $ciclo->id_ciclo)->select('id_grupo');
        $enrollments = DB::table('matriculas')->where('id_ciclo', $ciclo->id_ciclo)->select('id_matricula');
        $exams = DB::table('simulacros')->where('id_ciclo', $ciclo->id_ciclo)->select('id_simulacro');
        $evaluations = DB::table('evaluaciones')->whereIn('id_grupo', clone $groups)->select('id_evaluacion');
        $schedules = DB::table('horarios')->whereIn('id_grupo', clone $groups)->select('id_horario');
        $payments = DB::table('pagos')->whereIn('id_matricula', clone $enrollments)->select('id_pago');
        $fees = DB::table('cuotas')->whereIn('id_matricula', clone $enrollments)->select('id_cuota');
        $sheets = DB::table('hojas_respuesta')->whereIn('id_simulacro', clone $exams)->orWhereIn('id_matricula', clone $enrollments)->select('id_hoja');
        $results = DB::table('resultados_simulacros')->whereIn('id_simulacro', clone $exams)->orWhereIn('id_matricula', clone $enrollments)->select('id_resultado');

        return [
            'respuestas_hoja' => DB::table('respuestas_hoja')->whereIn('id_hoja', clone $sheets),
            'resultados_curso' => DB::table('resultados_curso')->whereIn('id_resultado', clone $results),
            'notas' => DB::table('notas')->whereIn('id_evaluacion', clone $evaluations)->orWhereIn('id_matricula', clone $enrollments),
            'asistencias' => DB::table('asistencias')->whereIn('id_horario', clone $schedules)->orWhereIn('id_matricula', clone $enrollments),
            'registros_ingreso' => DB::table('registros_ingreso')->whereIn('id_matricula', clone $enrollments),
            'congelamientos' => DB::table('congelamientos')->whereIn('id_matricula', clone $enrollments),
            'pagos_cuotas' => DB::table('pagos_cuotas')->whereIn('id_pago', clone $payments)->orWhereIn('id_cuota', clone $fees),
            'pagos' => DB::table('pagos')->whereIn('id_matricula', clone $enrollments),
            'cuotas' => DB::table('cuotas')->whereIn('id_matricula', clone $enrollments),
            'pagos_docentes' => DB::table('pagos_docentes')->where('id_ciclo', $ciclo->id_ciclo),
            'hojas_respuesta' => DB::table('hojas_respuesta')->whereIn('id_hoja', clone $sheets),
            'plantillas_hoja' => DB::table('plantillas_hoja')->whereIn('config->simulacro_id', clone $exams),
            'resultados_simulacros' => DB::table('resultados_simulacros')->whereIn('id_resultado', clone $results),
            'claves_respuesta' => DB::table('claves_respuesta')->whereIn('id_simulacro', clone $exams),
            'evaluaciones' => DB::table('evaluaciones')->whereIn('id_grupo', clone $groups),
            'simulacros' => DB::table('simulacros')->where('id_ciclo', $ciclo->id_ciclo),
            'anuncios' => DB::table('anuncios')->where('id_ciclo', $ciclo->id_ciclo)->orWhereIn('id_grupo', clone $groups),
            'materiales' => DB::table('materiales')->whereIn('id_grupo', clone $groups),
            'horarios' => DB::table('horarios')->whereIn('id_grupo', clone $groups),
            'matriculas' => DB::table('matriculas')->where('id_ciclo', $ciclo->id_ciclo)->orWhereIn('id_grupo', clone $groups),
            'grupos' => DB::table('grupos')->where('id_ciclo', $ciclo->id_ciclo),
            'ciclos' => DB::table('ciclos')->where('id_ciclo', $ciclo->id_ciclo),
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function snapshot(array $queries, bool $lock = false): array
    {
        $rows = [];
        // Bloquear padres antes que hijos impide nuevos registros dependientes durante el borrado.
        foreach (array_reverse($queries, true) as $table => $query) {
            $query = clone $query;
            foreach (self::KEYS[$table] as $key) {
                $query->orderBy($key);
            }
            if ($lock) {
                $query->lockForUpdate();
            }
            $rows[$table] = $query->get()->map(fn ($row) => (array) $row)->all();
        }
        $this->assertIsolated($rows);

        return array_replace(array_fill_keys(array_keys(self::KEYS), []), $rows);
    }

    private function assertIsolated(array $rows): void
    {
        $parents = [
            'id_ciclo' => 'ciclos', 'id_grupo' => 'grupos', 'id_matricula' => 'matriculas',
            'id_simulacro' => 'simulacros', 'id_evaluacion' => 'evaluaciones', 'id_horario' => 'horarios',
            'id_pago' => 'pagos', 'id_cuota' => 'cuotas', 'id_hoja' => 'hojas_respuesta',
            'id_resultado' => 'resultados_simulacros', 'id_registro_ingreso' => 'registros_ingreso',
        ];
        $ids = [];
        foreach ($parents as $column => $parent) {
            $key = $column === 'id_registro_ingreso' ? 'id_registro' : $column;
            $ids[$column] = array_fill_keys(array_column($rows[$parent], $key), true);
        }
        foreach ($rows as $table => $records) {
            foreach ($records as $record) {
                foreach ($parents as $column => $parent) {
                    if ($parent === $table || ! isset($record[$column])) {
                        continue;
                    }
                    if (! isset($ids[$column][$record[$column]])) {
                        throw ValidationException::withMessages(['ciclo' => 'El registro de '.$table.' ('.implode(':', array_map(fn ($key) => $record[$key], self::KEYS[$table])).') tiene '.$column.' relacionado con otro ciclo. Corrige esa relación antes de descargar o eliminar.']);
                    }
                }
            }
        }
    }

    public function files(array $rows): array
    {
        $files = [];
        foreach ($rows['hojas_respuesta'] as $sheet) {
            if ($sheet['archivo_path']) {
                $path = $sheet['archivo_path'];
                if (! str_starts_with($path, 'omr/uploads/') || str_contains($path, '..')) {
                    throw ValidationException::withMessages(['respaldo' => 'La ruta de una hoja de respuestas no es válida.']);
                }
                if (! Storage::disk('local')->exists($path)) {
                    throw ValidationException::withMessages(['respaldo' => 'Falta un archivo de hoja de respuestas. Recupera el archivo antes de eliminar el ciclo.']);
                }
                $files[$path] = hash_file('sha256', Storage::disk('local')->path($path));
            }
        }
        ksort($files);

        return $files;
    }

    public function fingerprint(array $rows, array $files): string
    {
        return hash('sha256', json_encode([$rows, $files], JSON_THROW_ON_ERROR));
    }

    /** Fichas y catálogos asociados: se exportan, pero no se eliminan. */
    public function referenceRows(array $rows): array
    {
        $students = DB::table('estudiantes')->whereIn('id_estudiante', array_column($rows['matriculas'], 'id_estudiante'))->orderBy('id_estudiante')->get()->map(function ($student) {
            $row = (array) $student;
            unset($row['codigo_qr']);

            return $row;
        })->all();
        $professorIds = array_unique([...array_column($rows['horarios'], 'id_profesor'), ...array_column($rows['pagos_docentes'], 'id_profesor')]);
        $references = [
            'estudiantes' => $students,
            'apoderados' => DB::table('apoderados')->whereIn('id_estudiante', array_column($students, 'id_estudiante'))->get()->map(fn ($row) => (array) $row)->all(),
            'profesores' => DB::table('profesores')->whereIn('id_profesor', $professorIds)->get()->map(fn ($row) => (array) $row)->all(),
        ];
        $examIds = array_column($rows['simulacros'], 'id_simulacro');
        $references['convocatorias_becas'] = DB::table('convocatorias_becas')->whereIn('id_simulacro_ab', $examIds)->orWhereIn('id_simulacro_cd', $examIds)->get()->map(fn ($row) => (array) $row)->all();
        $references['postulantes_becas'] = DB::table('postulantes_becas')->whereIn('id_convocatoria', array_column($references['convocatorias_becas'], 'id_convocatoria'))->get()->map(fn ($row) => (array) $row)->all();
        $courseIds = [];
        foreach (['horarios', 'evaluaciones', 'claves_respuesta', 'resultados_curso', 'materiales'] as $table) {
            $courseIds = [...$courseIds, ...array_column($rows[$table], 'id_curso')];
        }
        foreach ([
            'cursos' => ['id_curso', $courseIds],
            'aulas' => ['id_aula', array_column($rows['horarios'], 'id_aula')],
            'carreras' => ['id_carrera', array_column($rows['matriculas'], 'id_carrera_objetivo')],
            'becas' => ['id_beca', array_column($rows['matriculas'], 'id_beca')],
        ] as $table => [$key, $ids]) {
            $references[$table] = DB::table($table)->whereIn($key, array_filter(array_unique($ids)))->orderBy($key)->get()->map(fn ($row) => (array) $row)->all();
        }
        $references['universidades'] = DB::table('universidades')->whereIn('id_universidad', array_column($references['carreras'], 'id_universidad'))->get()->map(fn ($row) => (array) $row)->all();
        $references['areas_academicas'] = DB::table('areas_academicas')->whereIn('id_area', array_filter([...array_column($references['carreras'], 'id_area'), ...array_column($rows['grupos'], 'id_area')]))->get()->map(fn ($row) => (array) $row)->all();
        $references['plantillas_compartidas'] = DB::table('plantillas_hoja')->whereIn('id_plantilla', array_column($rows['hojas_respuesta'], 'id_plantilla'))->whereNotIn('id_plantilla', array_column($rows['plantillas_hoja'], 'id_plantilla'))->get()->map(fn ($row) => (array) $row)->all();
        $references['profesor_cursos'] = DB::table('profesor_cursos')->whereIn('id_profesor', $professorIds)->get()->map(fn ($row) => (array) $row)->all();
        $references['auditoria'] = DB::table('auditoria')->where(function (Builder $query) use ($rows): void {
            $query->whereRaw('1 = 0');
            foreach (self::KEYS as $table => $keys) {
                $ids = array_map(fn ($row) => implode(':', array_map(fn ($key) => $row[$key], $keys)), $rows[$table]);
                $query->orWhere(fn (Builder $scope) => $scope->where('tabla', $table)->whereIn('id_registro', $ids));
            }
        })->orderBy('id_auditoria')->get()->map(function ($row) {
            $record = (array) $row;
            foreach (['datos_antes', 'datos_despues'] as $column) {
                $record[$column] = $record[$column] ? json_encode($this->withoutSecrets(json_decode($record[$column], true, flags: JSON_THROW_ON_ERROR)), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null;
            }

            return $record;
        })->all();
        $userIds = [];
        foreach ([...$rows, 'auditoria' => $references['auditoria']] as $records) {
            foreach ($records as $row) {
                foreach (['id_usuario', 'id_usuario_registro', 'anulado_por'] as $column) {
                    if (isset($row[$column])) {
                        $userIds[] = $row[$column];
                    }
                }
            }
        }
        $references['usuarios_referencia'] = DB::table('users')->whereIn('id_usuario', array_unique($userIds))->get(['id_usuario', 'name', 'email', 'id_rol'])->map(fn ($row) => (array) $row)->all();

        return $references;
    }

    private function withoutSecrets(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }
        foreach ($data as $key => $value) {
            if (in_array($key, ['password', 'remember_token', 'codigo_qr', 'token'], true)) {
                unset($data[$key]);
            } else {
                $data[$key] = $this->withoutSecrets($value);
            }
        }

        return $data;
    }

    public function zip(Ciclo $ciclo, array $rows, array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'academia-ciclo-');
        $zip = new ZipArchive;
        try {
            if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo generar el respaldo.');
            }
            $data = [...$rows, ...$this->referenceRows($rows)];
            foreach ($data as $table => $records) {
                $zip->addFromString("registros/{$table}.json", json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                $csv = fopen('php://temp', 'w+');
                fwrite($csv, "\xEF\xBB\xBF");
                if ($records !== []) {
                    fputcsv($csv, array_keys($records[0]), ',', '"', '');
                    foreach ($records as $row) {
                        $values = array_map(function ($value) {
                            $value = (string) ($value ?? '');

                            return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
                        }, array_values($row));
                        fputcsv($csv, $values, ',', '"', '');
                    }
                }
                rewind($csv);
                $zip->addFromString("csv/{$table}.csv", stream_get_contents($csv));
                fclose($csv);
            }
            foreach ($files as $file => $hash) {
                $zip->addFile(Storage::disk('local')->path($file), 'archivos/'.$file);
            }
            foreach ($data['estudiantes'] as $student) {
                $photo = $student['foto_path'] ?? null;
                if ($photo && ! str_contains($photo, '..') && Storage::disk('local')->exists($photo)) {
                    $zip->addFile(Storage::disk('local')->path($photo), 'fotos_estudiantes/'.basename($photo));
                }
            }
            $zip->addFromString('LEEME.txt', 'Respaldo del ciclo '.$ciclo->nombre_ciclo.".\nCSV para consulta; JSON conserva los valores originales.\nSe conservan las fichas de estudiantes y docentes y los catálogos. No incluye contraseñas ni códigos QR.\nIncluye únicamente los pagos docentes asignados explícitamente a este ciclo.\nEste archivo no es una restauración automática del sistema.\n");
            $zip->addFromString('manifiesto.json', json_encode([
                'version' => 1, 'ciclo' => $ciclo->nombre_ciclo, 'id_ciclo' => $ciclo->id_ciclo,
                'generado_en' => now()->toIso8601String(), 'registros_a_eliminar' => array_map('count', $rows),
                'archivos_sha256' => $files, 'huella' => $this->fingerprint($rows, $files),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            if (! $zip->close()) {
                throw new RuntimeException('No se pudo finalizar el respaldo.');
            }

            return $path;
        } catch (\Throwable $exception) {
            if (is_string($path) && is_file($path)) {
                unlink($path);
            }
            throw $exception;
        }
    }

    public function deleteRows(array $rows, User $user, ?string $ip): void
    {
        foreach (self::KEYS as $table => $keys) {
            foreach ($rows[$table] as $row) {
                $query = DB::table($table);
                foreach ($keys as $key) {
                    $query->where($key, $row[$key]);
                }
                Auditoria::query()->create([
                    'id_usuario' => $user->id_usuario, 'accion' => 'eliminar', 'tabla' => $table,
                    'id_registro' => implode(':', array_map(fn ($key) => $row[$key], $keys)),
                    'datos_antes' => $row, 'ip' => $ip,
                ]);
                $query->delete();
            }
        }
    }
}
