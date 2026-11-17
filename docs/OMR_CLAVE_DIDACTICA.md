# Clave didáctica en Lectura OMR

Modificado: `resources/js/Pages/Omr/Index.tsx`.
Creado: este documento. Eliminados: ninguno.

La clave se muestra como una cartilla horizontal dividida en bloques de 20 preguntas. Los botones A–E funcionan con ratón, pantalla táctil y teclado. La alternativa seleccionada se distingue mediante relleno y estado accesible aria-pressed. Elegir otra alternativa reemplaza la anterior. El contador informa cuántas preguntas están marcadas. Curso y puntajes por pregunta quedan en un panel desplegable.

Las preguntas nuevas comienzan sin respuesta. Se impide guardar hasta que todas tengan una alternativa. Se mantienen las claves existentes al generar nuevas preguntas. El guardado utiliza el endpoint ya existente, con sus permisos y validaciones. No se modificaron el esquema ni dependencias.

Verificación: `npx tsc --noEmit` y `npm run build` aprobados. Navegador: selección C para pregunta 1 y generación hasta 80 verificadas; con 20 respuestas existentes y 60 vacías el guardado se deshabilita. A 360 px no hay desbordamiento de página; solo se desliza la cartilla. No se guardaron los cambios de prueba en la base principal. Las pruebas PHP no se repitieron en este cambio exclusivamente visual; la última suite tenía 109 aprobadas y dos fallos previos (Catálogos y Carnets).

Pendiente: el usuario confirmó que A–B y C–D son agrupaciones de carreras con claves diferentes. El esquema existente solo tiene una clave por simulacro y pregunta. Antes de ampliar el esquema, se solicita la autorización requerida por AGENTS.md. La impresión A4 sigue usando el diseño previamente implementado; la nueva disposición horizontal corresponde al editor de claves.
