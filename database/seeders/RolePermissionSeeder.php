<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin' => 'Administrador del sistema',
            'secretaria' => 'Gestión académica y atención',
            'caja' => 'Gestión de cuotas y cobros',
            'profesor' => 'Docencia y seguimiento académico',
            'estudiante' => 'Acceso al portal del estudiante',
            'apoderado' => 'Acceso al portal familiar',
        ];

        foreach ($roles as $nombre => $descripcion) {
            Role::query()->updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }

        $permissions = [
            'ciclos.historial' => 'Descargar y eliminar registros de ciclos cerrados',
            'dashboard.ver' => 'Ver panel principal',
            'usuarios.gestionar' => 'Gestionar usuarios y roles',
            'estudiantes.ver' => 'Consultar estudiantes',
            'estudiantes.gestionar' => 'Crear y actualizar estudiantes',
            'estudiantes.exportar' => 'Exportar registros de estudiantes',
            'carnets.generar' => 'Generar carnets y descargar códigos QR',
            'carnets.disenar' => 'Crear y editar plantillas de carnets',
            'matriculas.ver' => 'Consultar matrículas',
            'matriculas.gestionar' => 'Crear y actualizar matrículas',
            'academico.gestionar' => 'Gestionar ciclos, grupos, cursos y horarios',
            'docentes.gestionar' => 'Gestionar docentes',
            'pagos_docentes.gestionar' => 'Registrar y anular pagos a docentes',
            'asistencia.registrar_qr' => 'Registrar asistencia mediante QR',
            'asistencia.registrar_manual' => 'Registrar y corregir asistencia manual',
            'asistencia.ver' => 'Consultar asistencia y reportes',
            'finanzas.ver' => 'Consultar cuotas y estado de cuenta',
            'pagos.gestionar' => 'Registrar y anular cobros',
            'evaluaciones.gestionar' => 'Gestionar evaluaciones y simulacros',
            'omr.gestionar' => 'Generar, leer y revisar hojas de respuestas',
            'notas.gestionar' => 'Registrar notas',
            'comunicacion.gestionar' => 'Publicar anuncios y materiales',
            'consultas.gestionar' => 'Atender consultas del sitio web',
            'auditoria.ver' => 'Consultar auditoría del sistema',
            'reportes.ver' => 'Consultar reportes académicos y financieros',
            'portal.mis_datos' => 'Acceder a datos propios del portal',
        ];

        $permissionIds = [];
        foreach ($permissions as $codigo => $descripcion) {
            $permission = Permission::query()->updateOrCreate(
                ['codigo' => $codigo],
                ['modulo' => str($codigo)->before('.')->toString(), 'descripcion' => $descripcion],
            );
            $permissionIds[$codigo] = $permission->id_permiso;
        }

        $matrix = [
            'admin' => array_keys($permissions),
            'secretaria' => [
                'dashboard.ver', 'estudiantes.ver', 'estudiantes.gestionar', 'estudiantes.exportar', 'carnets.generar', 'matriculas.ver',
                'matriculas.gestionar', 'asistencia.registrar_qr', 'asistencia.registrar_manual', 'omr.gestionar',
                'asistencia.ver', 'comunicacion.gestionar', 'consultas.gestionar',
            ],
            'caja' => ['dashboard.ver', 'estudiantes.ver', 'matriculas.ver', 'finanzas.ver', 'pagos.gestionar', 'pagos_docentes.gestionar', 'reportes.ver'],
            'profesor' => [
                'dashboard.ver', 'asistencia.registrar_manual', 'asistencia.ver', 'notas.gestionar',
                'comunicacion.gestionar', 'omr.gestionar',
            ],
            'estudiante' => ['portal.mis_datos'],
            'apoderado' => ['portal.mis_datos'],
        ];

        foreach ($matrix as $roleName => $codes) {
            Role::query()->where('nombre', $roleName)->firstOrFail()
                ->permissions()->sync(array_map(fn (string $code): int => $permissionIds[$code], $codes));
        }
    }
}
