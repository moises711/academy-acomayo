# Cartilla horizontal y escáner OMR

Se implementó la cartilla de referencia con ocho columnas de DNI, grupo A/B/C/D y cuatro bloques de veinte preguntas. La misma cartilla sirve para marcar la clave mediante burbujas negras. Admite A–D o A–E; el formato compacto admite hasta 80 preguntas y se conserva el formato clásico para los exámenes anteriores.

Cada convocatoria relaciona un examen y su clave para A–B y otro para C–D. En OMR hay accesos directos para trabajar cada agrupación. Un DNI de postulante se identifica exclusivamente dentro de la convocatoria y se verifica su grupo registrado. Un estudiante se identifica por DNI y matrícula inscrita en el ciclo del examen.

El escáner usa la cámara en vivo del navegador. Detecta los cuatro cuadrados de referencia, corrige la perspectiva y mide las burbujas, evitando las letras impresas. Exige dos lecturas consecutivas iguales antes de capturar. La cámara se detiene al completar la lectura, al cerrar el visor y al salir del componente. También permite guardar una lectura dudosa para revisión o cargar un escaneo existente.

El servidor valida las preguntas y alternativas y calcula la nota usando la clave guardada: no acepta una nota enviada desde el navegador. Una marca múltiple o dudosa, DNI desconocido, grupo incorrecto, clave incompleta o hoja repetida dejan la lectura pendiente. Las imágenes se almacenan en el disco privado y las lecturas quedan auditadas. Si falla una carga, se revierte la transacción y se eliminan sus archivos recién creados.

La lectura automática en JavaScript se incorpora por indicación expresa del usuario, como excepción al flujo exclusivamente manual anterior. No se añadieron dependencias, procesamiento Python/OpenCV, procesos externos, tablas, columnas ni endpoints. Se reutilizan la configuración JSON de plantillas y la ruta protegida de subida OMR.

## Archivos creados

- `app/Support/CompactOmrLayout.php`
- `app/Support/AutoOmrImport.php`
- `resources/js/Components/OmrCartilla.tsx`
- `resources/js/Components/OmrUpload.tsx`
- `resources/js/Components/OmrCamera.tsx`
- `resources/js/lib/omr-reader.ts`
- `tests/omr-reader.test.mjs`
- `docs/OMR_ESCANER_CAMBIOS.md`

## Archivos modificados

- `app/Http/Controllers/OmrController.php`
- `app/Http/Requests/StorePlantillaHojaRequest.php`
- `app/Http/Requests/UploadHojasRespuestaRequest.php`
- `app/Support/BecaGrading.php` (comparación de identificadores numéricos recibidos como texto en cargas multipart)
- `resources/js/Pages/Omr/Index.tsx`
- `tests/Feature/BecaRegistrationTest.php`

No se eliminaron archivos fuente; `public/build` fue regenerado por el build. No se modificó el esquema de la base de datos; no corresponde ejecutar `migrate:fresh` para este cambio.

## Verificación

- `node --experimental-strip-types tests/omr-reader.test.mjs`: 5 pruebas aprobadas: DNI, ceros iniciales, 80 respuestas, imagen reducida, dobles marcas, referencias ausentes y letras impresas.
- `php artisan test --filter='Omr|BecaRegistrationTest'` en el contenedor del proyecto: 16 pruebas aprobadas, 180 aserciones. Incluye autorizaciones, identificación de postulantes y estudiantes, cálculo en servidor, límites del formato, grupo incorrecto, duplicados y reversión de archivos tras entradas inválidas.
- `php artisan test` en el mismo contenedor: 118 aprobadas y 2 fallos anteriores al cambio, en `CatalogoManagementTest` (secretaria recibe 403) y `StudentCarnetTest` (propiedades adicionales en la respuesta Inertia). La suite completa se ejecutó antes de las dos últimas pruebas nuevas; estas sí están incluidas en la ejecución final focalizada.
- Pint aplicado sobre todos los archivos PHP modificados y creados.
- `npx tsc --noEmit`: aprobado.
- `npm run build`: aprobado.
- Navegador: se creó la cartilla de demostración de 80 preguntas y se leyó un PNG sintético con letras y burbujas mediante el mismo lector Canvas utilizado por el escáner. Reconoció DNI de demostración 80000001, grupo A y las 80 respuestas sin dudas. Al guardar identificó al postulante y mostró nota 80.00, 80 correctas, 0 incorrectas y 0 en blanco. La base demo conserva esa hoja procesada y una primera hoja de prueba sin asignar, pendiente de revisión. Se crearon dos plantillas demo independientes para A–B y C–D.

- Responsivo: sin desbordamiento global a 360, 768 y 1280 px. Modo oscuro comprobado en el navegador.

## Límites y prueba pendiente

La cámara en vivo necesita HTTPS al acceder desde un teléfono; localhost es válido en el mismo equipo. No se modificaron despliegue, certificados, red ni `.env`.

Se debe validar con una cartilla impresa y un teléfono físico: iluminación, enfoque, tinta y papel pueden afectar la lectura. No se afirma que esa prueba física haya sido realizada. Se necesita imprimir los cuatro cuadrados completos, mantener la hoja horizontal y llenar burbujas con tinta negra. La imagen de referencia por sí sola y las plantillas clásicas antiguas no sustituyen una nueva plantilla compacta calibrada.
