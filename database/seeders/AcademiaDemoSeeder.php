<?php

namespace Database\Seeders;

use App\Models\Apoderado;
use App\Models\AreaAcademica;
use App\Models\Asistencia;
use App\Models\Aula;
use App\Models\Beca;
use App\Models\Carrera;
use App\Models\Ciclo;
use App\Models\ClaveRespuesta;
use App\Models\Cuota;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Evaluacion;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\RegistroIngreso;
use App\Models\ResultadoCurso;
use App\Models\ResultadosSimulacro;
use App\Models\Simulacro;
use App\Models\Universidad;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademiaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $universities = collect([
            ['nombre' => 'Universidad Nacional Mayor de San Marcos', 'siglas' => 'UNMSM', 'tipo' => 'Pública'],
            ['nombre' => 'Universidad Nacional de Ingeniería', 'siglas' => 'UNI', 'tipo' => 'Pública'],
            ['nombre' => 'Pontificia Universidad Católica del Perú', 'siglas' => 'PUCP', 'tipo' => 'Privada'],
        ])->map(fn (array $data): Universidad => Universidad::query()->updateOrCreate(['nombre' => $data['nombre']], $data));

        $areas = collect(['Ciencias de la Salud', 'Ingeniería', 'Ciencias Sociales'])
            ->map(fn (string $nombre): AreaAcademica => AreaAcademica::query()->firstOrCreate(['nombre' => $nombre]));
        $careers = collect([
            [$universities[0], $areas[0], 'Medicina Humana', 1600],
            [$universities[0], $areas[2], 'Derecho', 1350],
            [$universities[1], $areas[1], 'Ingeniería Civil', 1450],
            [$universities[1], $areas[1], 'Ingeniería de Sistemas', 1400],
            [$universities[2], $areas[1], 'Arquitectura', 1300],
        ])->map(fn (array $item): Carrera => Carrera::query()->firstOrCreate([
            'id_universidad' => $item[0]->id_universidad,
            'nombre' => $item[2],
        ], ['id_area' => $item[1]->id_area, 'puntaje_minimo' => $item[3]]));

        foreach ([
            ['Beca excelencia', 'Beca', 100],
            ['Media beca', 'Media Beca', 50],
            ['Convenio escolar', 'Convenio', 25],
        ] as [$nombre, $tipo, $porcentaje]) {
            Beca::query()->updateOrCreate(['nombre' => $nombre], [
                'tipo' => $tipo,
                'porcentaje_descuento' => $porcentaje,
                'activa' => true,
            ]);
        }

        $courseNames = [
            'Aritmética' => 'Ciencias', 'Álgebra' => 'Ciencias', 'Geometría' => 'Ciencias',
            'Física' => 'Ciencias', 'Química' => 'Ciencias', 'Comprensión Lectora' => 'Letras',
        ];
        $courses = collect($courseNames)->map(fn (string $categoria, string $nombre): Curso => Curso::query()->firstOrCreate(
            ['nombre_curso' => $nombre], ['categoria' => $categoria],
        ))->values();

        $rooms = collect(range(1, 6))->map(fn (int $number): Aula => Aula::query()->firstOrCreate(
            ['nombre' => 'Aula '.str_pad((string) $number, 2, '0', STR_PAD_LEFT)],
            ['tipo' => 'Presencial', 'capacidad' => 35],
        ));

        $this->call(CicloSeeder::class);
        $cycles = Ciclo::query()->orderBy('id_ciclo')->get();
        $professors = collect(range(1, 6))->map(function (int $number): Profesor {
            return Profesor::query()->updateOrCreate(['dni' => '4000000'.str_pad((string) $number, 1, '0', STR_PAD_LEFT)], [
                'nombre' => ['María', 'José', 'Rosa', 'Luis', 'Carmen', 'Diego'][$number - 1],
                'apellido' => ['Quispe', 'Rojas', 'Huamán', 'Salazar', 'Paredes', 'Mendoza'][$number - 1],
                'email' => 'docente'.$number.'@academia.test',
                'telefono' => '9'.str_pad((string) (10000000 + $number), 8, '0', STR_PAD_LEFT),
                'estado' => 'activo',
            ]);
        });

        foreach ($professors as $professor) {
            $professor->cursos()->syncWithoutDetaching($courses->pluck('id_curso'));
        }

        $groups = collect(range(1, 6))->map(function (int $number) use ($cycles, $areas): Grupo {
            $cycle = $cycles[$number <= 4 ? 0 : 1];

            return Grupo::query()->updateOrCreate([
                'id_ciclo' => $cycle->id_ciclo,
                'nombre' => 'Grupo '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            ], [
                'id_area' => $areas[($number - 1) % $areas->count()]->id_area,
                'turno' => ['Mañana', 'Tarde', 'Noche'][($number - 1) % 3],
                'capacidad' => 35,
            ]);
        });

        $day = now()->isoWeekday();
        $schedules = collect();
        foreach ($groups as $index => $group) {
            foreach ($courses as $courseIndex => $course) {
                $schedule = Horario::query()->firstOrCreate([
                    'id_grupo' => $group->id_grupo,
                    'id_curso' => $course->id_curso,
                    'dia_semana' => $day,
                    'hora_inicio' => sprintf('%02d:00:00', 8 + $courseIndex * 2),
                ], [
                    'id_profesor' => $professors[($index + $courseIndex) % $professors->count()]->id_profesor,
                    'id_aula' => $rooms[$index]->id_aula,
                    'hora_fin' => sprintf('%02d:30:00', 9 + $courseIndex * 2),
                ]);
                $schedules->push($schedule);
            }
        }

        $students = collect(range(1, 60))->map(function (int $number): Estudiante {
            $firstNames = ['Valeria', 'Mateo', 'Camila', 'Thiago', 'Lucía', 'Sebastián', 'Daniela', 'Joaquín', 'Mariana', 'Gabriel'];
            $lastNames = ['Quispe', 'Flores', 'Huamán', 'Rojas', 'Mendoza', 'Vargas', 'Salazar', 'Castillo', 'Paredes', 'García'];

            return Estudiante::query()->updateOrCreate(['dni' => str_pad((string) (70000000 + $number), 8, '0', STR_PAD_LEFT)], [
                'nombre' => $firstNames[($number - 1) % count($firstNames)],
                'apellido' => $lastNames[(intdiv($number - 1, 3)) % count($lastNames)],
                'email' => 'estudiante'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'@academia.test',
                'telefono' => '9'.str_pad((string) (20000000 + $number), 8, '0', STR_PAD_LEFT),
                'fecha_nacimiento' => now()->subYears(16 + ($number % 5))->subDays($number)->toDateString(),
                'direccion' => 'Av. Los Álamos '.(100 + $number).', Lima',
                'colegio_procedencia' => ['I.E. José María Arguedas', 'I.E. República del Perú', 'I.E. Miguel Grau'][$number % 3],
                'anio_egreso' => now()->year - ($number % 2),
                'estado' => 'activo',
            ]);
        });

        foreach ($students as $index => $student) {
            $apoderado = Apoderado::query()->updateOrCreate([
                'id_estudiante' => $student->id_estudiante,
                'nombre' => 'Familia '.($index + 1),
            ], [
                'apellido' => 'Responsable',
                'parentesco' => 'Madre',
                'telefono' => '9'.str_pad((string) (30000000 + $index + 1), 8, '0', STR_PAD_LEFT),
                'email' => 'familia'.($index + 1).'@academia.test',
            ]);

            $group = $groups[$index % $groups->count()];
            $matricula = Matricula::query()->updateOrCreate([
                'id_estudiante' => $student->id_estudiante,
                'id_ciclo' => $group->id_ciclo,
            ], [
                'id_grupo' => $group->id_grupo,
                'id_beca' => $index % 12 === 0 ? Beca::query()->where('nombre', 'Beca excelencia')->value('id_beca') : null,
                'id_carrera_objetivo' => $careers[$index % $careers->count()]->id_carrera,
                'fecha_matricula' => now()->subDays($index % 40)->toDateString(),
                'estado_matricula' => 'inscrito',
            ]);

            $student->loadMissing('matriculas');
            if ($index === 0) {
                User::query()->where('email', 'estudiante@academia.test')->update(['id_estudiante' => $student->id_estudiante]);
                User::query()->where('email', 'apoderado@academia.test')->update(['id_apoderado' => $apoderado->id_apoderado]);
            }

            $fees = collect(range(1, 3))->map(function (int $number) use ($matricula, $index): Cuota {
                $state = $number === 1 ? 'Pagado' : (($index % 4 === 0 && $number === 2) ? 'Vencido' : 'Pendiente');

                return Cuota::query()->updateOrCreate([
                    'id_matricula' => $matricula->id_matricula,
                    'numero_cuota' => $number,
                ], [
                    'concepto' => $number === 1 ? 'Matrícula' : 'Cuota mensual '.($number - 1),
                    'monto_original' => 320,
                    'monto_descuento' => 0,
                    'monto_final' => 320,
                    'fecha_vencimiento' => now()->subDays($number === 2 && $state === 'Vencido' ? 10 : -($number * 20))->toDateString(),
                    'estado_pago' => $state,
                ]);
            });

            if ($fees[0]->estado_pago === 'Pagado') {
                $cashier = User::query()->where('email', 'caja@academia.test')->firstOrFail();
                $payment = Pago::query()->updateOrCreate(['nro_comprobante' => 'B001-'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT)], [
                    'id_matricula' => $matricula->id_matricula,
                    'id_usuario' => $cashier->id_usuario,
                    'fecha_pago' => now()->subDays($index % 15),
                    'monto' => 320,
                    'metodo_pago' => ['Efectivo', 'Transferencia', 'Yape/Plin'][$index % 3],
                    'tipo_comprobante' => 'Boleta',
                    'estado' => 'valido',
                ]);
                $payment->cuotas()->syncWithoutDetaching([$fees[0]->id_cuota => ['monto_aplicado' => 320]]);
            }

            $schedule = $schedules->firstWhere('id_grupo', $group->id_grupo);
            $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();
            $entry = RegistroIngreso::query()->firstOrCreate([
                'id_matricula' => $matricula->id_matricula,
                'fecha_hora' => now()->setTime(7, 45)->addMinutes($index),
            ], ['id_usuario' => $secretary->id_usuario, 'tipo' => 'ingreso', 'metodo' => 'qr']);
            Asistencia::query()->updateOrCreate([
                'id_matricula' => $matricula->id_matricula,
                'id_horario' => $schedule->id_horario,
                'fecha' => now()->toDateString(),
            ], [
                'estado' => $index % 7 === 0 ? 'Tarde' : 'Presente',
                'origen' => 'qr',
                'id_usuario_registro' => $secretary->id_usuario,
                'id_registro_ingreso' => $entry->id_registro,
                'hora_registro' => '07:45:00',
            ]);
        }

        foreach ($professors as $index => $professor) {
            User::query()->where('email', 'profesor@academia.test')->update(['id_profesor' => $professor->id_profesor]);
            if ($index === 0) {
                break;
            }
        }

        $simulacro = Simulacro::query()->firstOrCreate([
            'id_ciclo' => $cycles[0]->id_ciclo,
            'titulo' => 'Simulacro de admisión 01',
        ], ['fecha_aplicacion' => now()->addDays(7)->toDateString(), 'puntaje_maximo' => 100]);
        $secondSimulacro = Simulacro::query()->firstOrCreate([
            'id_ciclo' => $cycles[0]->id_ciclo,
            'titulo' => 'Simulacro de admisión 02',
        ], ['fecha_aplicacion' => now()->addDays(21)->toDateString(), 'puntaje_maximo' => 100]);

        foreach (range(1, 30) as $question) {
            ClaveRespuesta::query()->firstOrCreate([
                'id_simulacro' => $simulacro->id_simulacro,
                'numero_pregunta' => $question,
            ], [
                'id_curso' => $courses[($question - 1) % $courses->count()]->id_curso,
                'respuesta_correcta' => ['A', 'B', 'C', 'D', 'E'][$question % 5],
                'puntos_correcta' => 1,
                'puntos_incorrecta' => -0.25,
            ]);
        }

        $mocksByCycle = [];
        foreach ($cycles as $cycle) {
            $mocksByCycle[$cycle->id_ciclo] = collect([$simulacro, $secondSimulacro])->map(function (Simulacro $source) use ($cycle): Simulacro {
                $mock = Simulacro::query()->firstOrCreate([
                    'id_ciclo' => $cycle->id_ciclo,
                    'titulo' => $source->titulo,
                ], ['fecha_aplicacion' => $cycle->estado === 'Cerrado' ? $cycle->fecha_fin->toDateString() : $source->fecha_aplicacion, 'puntaje_maximo' => $source->puntaje_maximo]);
                if ($mock->id_simulacro !== $source->id_simulacro) {
                    ClaveRespuesta::query()->where('id_simulacro', $source->id_simulacro)->get()->each(function (ClaveRespuesta $key) use ($mock): void {
                        ClaveRespuesta::query()->firstOrCreate(['id_simulacro' => $mock->id_simulacro, 'numero_pregunta' => $key->numero_pregunta], $key->only(['id_curso', 'respuesta_correcta', 'puntos_correcta', 'puntos_incorrecta']));
                    });
                }

                return $mock;
            });
        }
        foreach ($students as $index => $student) {
            $matricula = $student->matriculas()->firstOrFail();
            foreach ($mocksByCycle[$matricula->id_ciclo] as $mockIndex => $mock) {
                $result = ResultadosSimulacro::query()->firstOrCreate([
                    'id_simulacro' => $mock->id_simulacro,
                    'id_matricula' => $matricula->id_matricula,
                ], [
                    'puntaje_obtenido' => 45 + (($index * 7 + $mockIndex * 11) % 56),
                    'puesto_general' => null,
                    'puesto_carrera' => null,
                    'es_rezagado' => $index % 9 === 0,
                    'fecha_rendicion' => now()->subDays(14 - $mockIndex * 7)->toDateString(),
                    'estado_ranking' => 'pendiente_actualizar',
                ]);
                foreach ($courses as $courseIndex => $course) {
                    ResultadoCurso::query()->firstOrCreate([
                        'id_resultado' => $result->id_resultado,
                        'id_curso' => $course->id_curso,
                    ], [
                        'correctas' => 3 + (($index + $courseIndex) % 5),
                        'incorrectas' => ($index + $courseIndex) % 3,
                        'en_blanco' => 2,
                        'puntaje' => 8 + (($index + $courseIndex) % 12),
                    ]);
                }
            }
        }

        foreach ($groups as $group) {
            foreach ($courses as $course) {
                $evaluation = Evaluacion::query()->firstOrCreate([
                    'id_grupo' => $group->id_grupo,
                    'id_curso' => $course->id_curso,
                    'titulo' => 'Práctica diagnóstica',
                ], [
                    'tipo' => 'Práctica',
                    'fecha' => now()->subDays(5)->toDateString(),
                    'nota_maxima' => 20,
                    'peso' => 1,
                ]);
                $group->matriculas()->each(function (Matricula $matricula) use ($evaluation): void {
                    Nota::query()->firstOrCreate([
                        'id_evaluacion' => $evaluation->id_evaluacion,
                        'id_matricula' => $matricula->id_matricula,
                    ], ['nota' => 11 + ($matricula->id_matricula % 10), 'observacion' => null]);
                });
            }
        }

        DB::table('configuraciones')->updateOrInsert(['clave' => 'asistencia.tolerancia_minutos'], [
            'valor' => '10', 'descripcion' => 'Minutos de tolerancia antes de marcar tardanza',
        ]);
        DB::table('configuraciones')->updateOrInsert(['clave' => 'asistencia.bloquear_si_deuda'], [
            'valor' => '0', 'descripcion' => 'Bloquea el ingreso si el estudiante tiene deuda vencida',
        ]);
    }
}
