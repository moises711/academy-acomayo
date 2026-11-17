# Inscripción y evaluación de postulantes a becas

## Uso

1. Entrar en **Contenido del sitio web → Administrar convocatorias y postulantes**, o `/becas`.
2. Crear una convocatoria con fechas, puntaje mínimo y dos simulacros distintos del mismo ciclo: uno para A–B y otro para C–D. Cada simulacro conserva su propia clave en Lectura OMR.
3. Revisar las claves y publicar como **Abierta**. El formulario público está en `/becas/inscripcion`, también enlazado en el sitio público. Solo recibe convocatorias abiertas dentro de sus fechas, calculadas en America/Lima.
4. El postulante registra DNI de 8 dígitos, celular de 9 dígitos que empieza con 9 y grupo A, B, C o D. El DNI es único por convocatoria. Reenviar una solicitud no cambia el registro existente ni revela sus datos.
5. En OMR, seleccionar el examen correspondiente, crear la plantilla, cargar la imagen y revisar las respuestas. Escribir el DNI del postulante en la hoja. Se busca únicamente dentro de esa convocatoria y agrupación; no se requiere matrícula. Una hoja solo puede tener un destinatario y cada postulante una hoja.
6. Guardar la revisión calcula la nota con puntos por acierto/error; en blanco suma cero, marca múltiple recibe penalización por error. Las dudas o respuestas faltantes dejan la nota pendiente. Los postulantes con nota mayor o igual al mínimo quedan como Becario; los demás, Calificado.
7. Tras revisar **todos** los postulantes, cerrar desde el panel. Exige escribir el nombre de la convocatoria y confirmar el borrado. Se conservan DNI, celular, grupo y nota de los becarios, y se eliminan los demás registros con sus respuestas e imágenes. Las auditorías de sus revisiones se desidentifican; el cierre conserva una auditoría de cantidades. No se ejecutó este borrado sobre la base principal durante la verificación.

Las convocatorias demo quedan en Borrador. Sus puntajes y claves son ficticios: deben revisarse antes de publicar. Con inscritos, se permite cambiar publicación, descripción y fechas, pero se bloquean los cambios de exámenes y del mínimo; cerradas, ya no se pueden editar ni recalificar.

## Seguridad y compatibilidad

- Administración y cierre: permiso existente `usuarios.gestionar`, coherente con Contenido del sitio web. Lectura y revisión OMR: `omr.gestionar`. Políticas adicionales verifican acceso a los recursos.
- Registro público: validación, CSRF y límite de 6 solicitudes por minuto. No publica listas, DNI, teléfonos ni notas de otros postulantes.
- Notas en decimal; cálculo en centésimos enteros, sin float.
- Dos tablas nuevas y una relación nullable en la migración original de hojas OMR. No se agregaron dependencias ni migraciones incrementales.
- El historial de ciclos bloquea eliminación si hay convocatorias sin cerrar. Incluye convocatorias y becarios en el respaldo del ciclo; los becarios permanecen después de eliminar un ciclo, con referencias de examen vacías.
- Se corrigió un defecto previo dentro del flujo afectado: el botón Guardar revisión y calificar carecía de formulario conectado y no enviaba la revisión.

## Archivos creados

- `database/migrations/2026_10_07_012657_01_create_convocatorias_becas_table.php`
- `database/migrations/2026_10_07_012657_02_create_postulantes_becas_table.php`
- `app/Models/ConvocatoriaBeca.php`, `app/Models/PostulanteBeca.php`
- `app/Policies/ConvocatoriaBecaPolicy.php`, `app/Policies/PostulanteBecaPolicy.php`
- `app/Http/Controllers/ConvocatoriaBecaController.php`, `app/Http/Controllers/PostulanteBecaController.php`
- `app/Http/Requests/SaveConvocatoriaBecaRequest.php`, `app/Http/Requests/StorePostulanteBecaRequest.php`, `app/Http/Requests/CloseConvocatoriaBecaRequest.php`
- `app/Support/BecaGrading.php`
- `database/factories/ConvocatoriaBecaFactory.php`, `database/factories/PostulanteBecaFactory.php`
- `database/seeders/ConvocatoriaBecaSeeder.php`
- `resources/js/types/becas.ts`
- `resources/js/Pages/Becas/Index.tsx`, `resources/js/Pages/Becas/Inscripcion.tsx`
- `tests/Feature/BecaRegistrationTest.php`
- Este documento.

## Archivos modificados

- `database/migrations/2026_10_07_012658_create_hojas_respuesta_table.php`
- `app/Models/HojaRespuesta.php`
- `app/Http/Requests/UpdateHojaRespuestaRequest.php`
- `app/Http/Controllers/OmrController.php`, `app/Http/Controllers/CicloHistoryController.php`
- `app/Support/CicloArchive.php`
- `database/seeders/DatabaseSeeder.php`
- `routes/web.php`, `docs/ESQUEMA_FINAL.sql`
- `resources/js/Pages/Omr/Index.tsx`, `resources/js/Pages/SitioWeb/Index.tsx`, `resources/js/Pages/Welcome.tsx`
- `resources/js/data/landing.ts`

Eliminados: ninguno. Los dos archivos nuevos generados por Artisan se renombraron para respetar el orden de llaves foráneas.

## Verificación

- `php artisan migrate:fresh --seed --force`, contenedor con `DB_DATABASE=testing`: aprobado.
- Misma operación en la base local de desarrollo: aprobada. Antes se guardó respaldo JSON completo en el disco privado `backups/antes-becas-20261009-023526.json` (dentro de storage/app/private, sin exposición pública). La base local fue recreada; los datos anteriores solo permanecen en ese respaldo.
- Pruebas dirigidas de becas, OMR e historial: **19 aprobadas, 219 aserciones**.
- Suite completa en testing: **115 aprobadas y 2 fallos previos, 787 aserciones**.
- Pruebas de becas repetidas después del ajuste de fechas y cobertura de creación: **6 aprobadas, 84 aserciones**, registro en `/tmp/academia-becas-final-tests.log`.
- `./vendor/bin/pint` aplicado y `./vendor/bin/pint --test`: aprobado.
- `npx tsc --noEmit` y `npm run build`: aprobados.
- Navegador: publicación temporal de demo, inscripción idempotente con datos ficticios existentes, vuelta a Borrador y controles de acceso con administrador. Formulario y panel sin desbordamientos a 360, 768 y 1280 px.

## Pendientes y límites

- **La lectura de imágenes continúa siendo manual.** No se implementó detección automática de burbujas del DNI ni de respuestas, ni una nueva cartilla imprimible horizontal con DNI en burbujas. Esas funciones solicitadas anteriormente requieren otra etapa y confirmar la excepción a la regla OMR manual del proyecto.
- La suite general mantiene `CatalogoManagementTest` (secretaría recibe 403 en lugar de 200) y `StudentCarnetTest` (la aserción Inertia rechaza los campos id/nombre). No se cambiaron por estar fuera del alcance.
- El cierre exige una nota revisada para cada registro; los ausentes no se eliminan automáticamente sin resolución manual.
- No hay importación automática del respaldo JSON anterior ni sincronización automática entre postulante y ficha de estudiante/matrícula.
- Los postulantes no tienen matrícula: sus notas permanecen en `postulantes_becas`, sin inventar matrículas para escribir en resultados de estudiantes.

La revisión automática rechazó aplicar directamente el vínculo con Schema::table. Se usó la alternativa autorizada en AGENTS.md: migraciones originales y migrate:fresh con respaldo previo.
