<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AulasHorarioController;
use App\Http\Controllers\CarnetTemplateController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CicloController;
use App\Http\Controllers\CicloHistoryController;
use App\Http\Controllers\ComunicacionController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\ConvocatoriaBecaController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\NotasController;
use App\Http\Controllers\OmrController;
use App\Http\Controllers\PagoDocenteController;
use App\Http\Controllers\PagosCuotaController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PostulanteBecaController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ResultadosSimulacroController;
use App\Http\Controllers\SimulacroController;
use App\Http\Controllers\SitioWebController;
use App\Http\Controllers\UserManagementController;
use App\Models\Ciclo;
use App\Models\Configuracion;
use App\Models\ConvocatoriaBeca;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $content = Configuracion::query()->whereKey('sitio.contenido')->value('valor');
    $decodedContent = $content ? json_decode($content, true) : [];

    return Inertia::render('Welcome', [
        'contenido' => is_array($decodedContent) ? $decodedContent : [],
        'fotoPortadaUrl' => Configuracion::query()->whereKey('sitio.foto_portada')->exists()
            ? route('sitio-web.portada.foto')
            : null,
        'docentesWeb' => app(SitioWebController::class)->docentesPublicos(),
        'examenBecas' => ($exam = ConvocatoriaBeca::examenPublicadoEnWeb()) ? [
            'nombre' => $exam->nombre,
            'fecha_examen' => $exam->fecha_examen->toDateString(),
            'hora_inicio_examen' => substr($exam->hora_inicio_examen, 0, 5),
            'hora_fin_examen' => substr($exam->hora_fin_examen, 0, 5),
        ] : null,
        'ciclos' => Ciclo::query()
            ->whereIn('estado', ['Programado', 'En curso'])
            ->orderBy('fecha_inicio')
            ->get(['id_ciclo', 'nombre_ciclo', 'tipo_ciclo', 'fecha_inicio', 'fecha_fin', 'modalidad']),
    ]);
});
Route::get('becas/inscripcion', [PostulanteBecaController::class, 'index'])->name('becas.inscripcion');
Route::post('becas/inscripcion', [PostulanteBecaController::class, 'store'])->middleware('throttle:6,1')->name('becas.inscripcion.store');

Route::get('sitio-web/portada/foto', [SitioWebController::class, 'portadaFoto'])->name('sitio-web.portada.foto');
Route::get('sitio-web/docentes/{docente}/foto', [SitioWebController::class, 'docenteFoto'])->name('sitio-web.docentes.foto');
Route::post('consultas', [ConsultaController::class, 'store'])
    ->middleware('throttle:5,1')->name('consultas.store');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/mi', [PortalController::class, 'index'])
        ->middleware('permission:portal.mis_datos')->name('mi.index');
    Route::get('/mi/pagos/{pago}/comprobante', [PortalController::class, 'comprobante'])
        ->middleware('permission:portal.mis_datos')->name('mi.pagos.comprobante');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.ver')->name('dashboard');

    Route::get('asistencias', [AsistenciaController::class, 'index'])
        ->middleware('permission:asistencia.ver')->name('asistencias.index');
    Route::get('asistencias/exportar', [AsistenciaController::class, 'exportar'])
        ->middleware('permission:asistencia.ver')->name('asistencias.exportar');
    Route::post('asistencias', [AsistenciaController::class, 'store'])
        ->middleware(['permission:asistencia.registrar_qr', 'throttle:30,1'])->name('asistencias.store');
    Route::patch('asistencias/{asistencia}', [AsistenciaController::class, 'update'])
        ->middleware('permission:asistencia.registrar_manual')->name('asistencias.update');

    Route::middleware('permission:usuarios.gestionar')->group(function (): void {
        Route::get('becas', [ConvocatoriaBecaController::class, 'index'])->name('becas.index');
        Route::post('becas', [ConvocatoriaBecaController::class, 'store'])->name('becas.store');
        Route::put('becas/{convocatoria}', [ConvocatoriaBecaController::class, 'update'])->name('becas.update');
        Route::post('becas/{convocatoria}/cerrar', [ConvocatoriaBecaController::class, 'close'])->name('becas.close');
        Route::get('sitio-web', [SitioWebController::class, 'index'])->name('sitio-web.index');
        Route::put('sitio-web', [SitioWebController::class, 'update'])->name('sitio-web.update');
        Route::get('usuarios', [UserManagementController::class, 'index'])->name('usuarios.index');
        Route::post('usuarios', [UserManagementController::class, 'store'])->name('usuarios.store');
        Route::put('usuarios/{usuario}', [UserManagementController::class, 'update'])->name('usuarios.update');
        Route::delete('usuarios/{usuario}', [UserManagementController::class, 'destroy'])->name('usuarios.destroy');
    });

    Route::get('estudiantes/carnets', [EstudianteController::class, 'carnets'])
        ->middleware('permission:carnets.generar')->name('estudiantes.carnets');
    Route::get('estudiantes/carnets/qr', [EstudianteController::class, 'codigosQrGrupo'])
        ->middleware('permission:carnets.generar')->name('estudiantes.carnets.qr');
    Route::resource('estudiantes', EstudianteController::class)->only(['index', 'show'])
        ->middleware('permission:estudiantes.ver');
    Route::resource('estudiantes', EstudianteController::class)->except(['index', 'show'])
        ->middleware('permission:estudiantes.gestionar');
    Route::get('estudiantes/{estudiante}/carnet', [EstudianteController::class, 'carnet'])
        ->middleware('permission:carnets.generar')->name('estudiantes.carnet');
    Route::get('estudiantes/{estudiante}/foto', [EstudianteController::class, 'foto'])
        ->middleware('permission:estudiantes.ver')->name('estudiantes.foto');
    Route::post('estudiantes/{estudiante}/carnet/regenerar', [EstudianteController::class, 'regenerarQr'])
        ->middleware('permission:carnets.generar')->name('estudiantes.carnet.regenerar');
    Route::middleware('permission:carnets.disenar')->prefix('carnets/plantillas')->name('carnets.plantillas.')->group(function (): void {
        Route::get('/', [CarnetTemplateController::class, 'index'])->name('index');
        Route::post('/', [CarnetTemplateController::class, 'store'])->name('store');
        Route::put('/{id}', [CarnetTemplateController::class, 'update'])->whereUuid('id')->name('update');
        Route::delete('/{id}', [CarnetTemplateController::class, 'destroy'])->whereUuid('id')->name('destroy');
        Route::post('/{id}/predeterminada', [CarnetTemplateController::class, 'setDefault'])->whereUuid('id')->name('default');
    });

    Route::resource('cursos', CursoController::class)->except(['create', 'show', 'edit'])
        ->middleware('permission:academico.gestionar');
    Route::middleware('permission:ciclos.historial')->prefix('historial-ciclos')->name('historial-ciclos.')->group(function (): void {
        Route::get('/', [CicloHistoryController::class, 'index'])->name('index');
        Route::post('/{ciclo}/descargar', [CicloHistoryController::class, 'export'])->name('export');
        Route::delete('/{ciclo}', [CicloHistoryController::class, 'destroy'])->name('destroy');
        Route::patch('/pagos-docentes/{pagoDocente}/ciclo', [CicloHistoryController::class, 'assign'])->name('assign');
    });

    Route::resource('ciclos', CicloController::class)->except(['create', 'show', 'edit'])
        ->middleware('permission:academico.gestionar');
    Route::get('catalogos', [CatalogoController::class, 'index'])
        ->middleware('permission:academico.gestionar')->name('catalogos.index');
    Route::post('catalogos/{tipo}', [CatalogoController::class, 'store'])
        ->middleware('permission:academico.gestionar')->name('catalogos.store');
    Route::put('catalogos/{tipo}/{id}', [CatalogoController::class, 'update'])
        ->middleware('permission:academico.gestionar')->name('catalogos.update');
    Route::delete('catalogos/{tipo}/{id}', [CatalogoController::class, 'destroy'])
        ->middleware('permission:academico.gestionar')->name('catalogos.destroy');
    Route::resource('docentes', ProfesorController::class)->except(['create', 'show', 'edit'])
        ->middleware('permission:docentes.gestionar');
    Route::get('pagos-docentes', [PagoDocenteController::class, 'index'])
        ->middleware('permission:pagos_docentes.gestionar')->name('pagos-docentes.index');
    Route::get('docentes/{docente}/pagos', [PagoDocenteController::class, 'index'])
        ->middleware('permission:pagos_docentes.gestionar')->name('docentes.pagos.index');
    Route::post('pagos-docentes', [PagoDocenteController::class, 'store'])
        ->middleware('permission:pagos_docentes.gestionar')->name('pagos-docentes.store');
    Route::post('pagos-docentes/{pagoDocente}/anular', [PagoDocenteController::class, 'anular'])
        ->middleware('permission:pagos_docentes.gestionar')->name('pagos-docentes.anular');
    Route::resource('matriculas', MatriculaController::class)->only(['index', 'show'])
        ->middleware('permission:matriculas.ver');
    Route::resource('matriculas', MatriculaController::class)->except(['index', 'show', 'edit'])
        ->middleware('permission:matriculas.gestionar');

    Route::get('pagos', [PagosCuotaController::class, 'index'])
        ->middleware('permission:finanzas.ver')->name('pagos.index');
    Route::post('pagos', [PagosCuotaController::class, 'store'])
        ->middleware('permission:pagos.gestionar')->name('pagos.store');
    Route::post('cuotas', [PagosCuotaController::class, 'storeCuota'])
        ->middleware('permission:pagos.gestionar')->name('cuotas.store');
    Route::post('pagos/{pago}/anular', [PagosCuotaController::class, 'anular'])
        ->middleware('permission:pagos.gestionar')->name('pagos.anular');
    Route::get('pagos/{pago}/comprobante', [PagosCuotaController::class, 'comprobante'])
        ->middleware('permission:finanzas.ver')->name('pagos.comprobante');

    Route::resource('simulacros', SimulacroController::class)
        ->only(['index', 'create', 'show', 'edit', 'store', 'update', 'destroy'])
        ->middleware('permission:evaluaciones.gestionar');
    Route::get('simulacros/{simulacro}/resultados', [ResultadosSimulacroController::class, 'index'])
        ->middleware('permission:evaluaciones.gestionar')->name('simulacros.resultados');
    Route::post('resultados-simulacros', [ResultadosSimulacroController::class, 'store'])
        ->middleware('permission:evaluaciones.gestionar')->name('resultados.store');
    Route::middleware('permission:omr.gestionar')->group(function (): void {
        Route::get('omr', [OmrController::class, 'index'])->name('omr.index');
        Route::post('omr/simulacros/{simulacro}/claves', [OmrController::class, 'storeClaves'])->name('omr.claves.store');
        Route::post('omr/simulacros/{simulacro}/plantillas', [OmrController::class, 'storePlantilla'])->name('omr.plantillas.store');
        Route::post('omr/hojas/subir', [OmrController::class, 'subir'])->name('omr.hojas.subir');
        Route::patch('omr/hojas/{hoja}', [OmrController::class, 'actualizar'])->name('omr.hojas.update');
        Route::get('omr/hojas/{hoja}/preview', [OmrController::class, 'previsualizar'])->name('omr.hojas.preview');
        Route::get('omr/hojas/{hoja}/archivo', [OmrController::class, 'descargar'])->name('omr.hojas.archivo');
    });
    Route::resource('horarios', AulasHorarioController::class)->only(['index', 'store', 'destroy'])
        ->middleware('permission:academico.gestionar');
    Route::get('reportes', [ReporteController::class, 'index'])
        ->middleware('permission:reportes.ver')->name('reportes.index');
    Route::get('auditoria', [AuditoriaController::class, 'index'])
        ->middleware('permission:auditoria.ver')->name('auditoria.index');
    Route::get('configuracion', [ConfiguracionController::class, 'index'])
        ->middleware('permission:usuarios.gestionar')->name('configuracion.index');
    Route::put('configuracion', [ConfiguracionController::class, 'update'])
        ->middleware('permission:usuarios.gestionar')->name('configuracion.update');
    Route::get('notas', [NotasController::class, 'index'])
        ->middleware('permission:notas.gestionar')->name('notas.index');
    Route::post('evaluaciones', [NotasController::class, 'storeEvaluation'])
        ->middleware('permission:notas.gestionar')->name('evaluaciones.store');
    Route::put('evaluaciones/{evaluacion}/notas', [NotasController::class, 'storeGrades'])
        ->middleware('permission:notas.gestionar')->name('evaluaciones.notas.store');
    Route::get('consultas', [ConsultaController::class, 'index'])
        ->middleware('permission:consultas.gestionar')->name('consultas.index');
    Route::put('consultas/{consulta}', [ConsultaController::class, 'update'])
        ->middleware('permission:consultas.gestionar')->name('consultas.update');
    Route::get('comunicacion', [ComunicacionController::class, 'index'])
        ->middleware('permission:comunicacion.gestionar')->name('comunicacion.index');
    Route::post('comunicacion', [ComunicacionController::class, 'store'])
        ->middleware('permission:comunicacion.gestionar')->name('comunicacion.store');
    Route::put('comunicacion/{tipo}/{id}', [ComunicacionController::class, 'update'])
        ->middleware('permission:comunicacion.gestionar')->name('comunicacion.update');
    Route::delete('comunicacion/{tipo}/{id}', [ComunicacionController::class, 'destroy'])
        ->middleware('permission:comunicacion.gestionar')->name('comunicacion.destroy');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
