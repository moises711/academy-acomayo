# Configuración de cartillas dentro de Lectura OMR

## Funcionalidad

En `/omr`, selecciona un simulacro. Define entre 1 y 120 preguntas, curso inicial y puntajes por acierto/error. Generar conserva las preguntas existentes y agrega las faltantes; reducir la cantidad pide confirmación. Las preguntas nuevas usan A como valor inicial: revisa cada alternativa antes de guardar. Aplicar puntajes a todas también pide confirmación. Cada pregunta permite curso, clave A–E y puntajes individuales. Las respuestas en blanco conservan el comportamiento existente: cero puntos.

La vista previa permite descargar un borrador SVG en A4. Después de guardar la clave, se crea una cartilla con nombre, título, instrucciones y una o dos columnas. Una columna admite hasta 60 preguntas; dos, hasta 120. Las plantillas guardadas muestran vista previa y descarga. No se incluyen las respuestas correctas en la cartilla imprimible. Imprimir en A4 al 100 %. La revisión de imágenes sigue siendo manual.

## Archivos

Modificados:
- `resources/js/Pages/Omr/Index.tsx`: editor, generación, puntajes, errores de guardado, diseño, vista previa, descarga y adaptación móvil.
- `app/Http/Requests/StorePlantillaHojaRequest.php`: validación de título, instrucciones y columnas.
- `app/Http/Controllers/OmrController.php`: guarda el diseño en el JSON existente y rechaza una columna para más de 60 preguntas.
- `tests/Feature/OmrTest.php`: diseño persistido, límites de columnas, ausencia de claves en cartilla y autorización de escritura.

Creado: este documento. Eliminados: ninguno. Sin cambios de tablas, dependencias ni rutas.

## Verificación ejecutada

- `php artisan test --filter=Omr`, en contenedor con base testing: 5 pruebas aprobadas, 50 aserciones.
- `php artisan test`, en contenedor con base testing: 109 aprobadas y 2 fallos previos, 707 aserciones.
- `./vendor/bin/pint` aplicado a PHP modificado; `./vendor/bin/pint --test` aprobado en el proyecto.
- `npx tsc --noEmit`: aprobado.
- `npm run build`: aprobado después de los cambios finales del editor.
- Navegador: generación de preguntas y descarga SVG verificadas; sin desbordamiento de página a 360, 768 y 1280 px. No se guardaron claves ficticias en la base principal.
- No se ejecutó `migrate:fresh --seed`: no se modificaron migraciones ni esquema.

## Pendiente / limitaciones

La suite general mantiene los fallos previos de `CatalogoManagementTest` (secretaría recibe 403 en lugar del 200 esperado) y `StudentCarnetTest` (la aserción Inertia rechaza campos adicionales id/nombre). No se modificaron por estar fuera del alcance. No se verificó impresión física.

El registro temporal de postulantes a becas, solicitado anteriormente, todavía requiere confirmar la ampliación del esquema y el momento de eliminación. Este cambio solo mejora la configuración de cartillas en OMR.
