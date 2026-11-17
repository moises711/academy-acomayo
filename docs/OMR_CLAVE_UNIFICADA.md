# Clave unificada para A, B, C y D

Desde OMR → Configurar modalidad de clave, o en Contenido del sitio web → Exámenes de becas → Nueva convocatoria (o configuración de una convocatoria sin inscritos), el selector «Modalidad de la clave» permite:

- Separada: un examen con clave para A–B y otro examen con clave para C–D, dentro del mismo ciclo.
- Unificada: un solo examen, una clave y una cartilla compartidos por los cuatro grupos.

El modo unificado se representa utilizando el mismo examen en los dos vínculos existentes de la convocatoria. No requiere otra tabla ni columna. Los tipos, la inscripción por grupo, el reconocimiento por DNI, la validación de grupo y la calificación reutilizan los vínculos existentes. En OMR se muestra un único acceso «Clave y cartilla unificada A–B–C–D», y los registros de postulantes indican la clave unificada.

Las convocatorias con inscritos conservan sus exámenes y umbral de calificación. El selector y los exámenes quedan bloqueados en la interfaz, y el backend sigue protegiendo esa regla. Una modalidad unificada no convierte las convocatorias existentes automáticamente. El modo separado rechaza identificadores iguales aunque uno llegue como texto y el otro como número.

## Archivos

Modificados:

- `app/Http/Requests/SaveConvocatoriaBecaRequest.php`
- `app/Http/Controllers/ConvocatoriaBecaController.php`
- `resources/js/Pages/Becas/Index.tsx`
- `resources/js/Pages/Omr/Index.tsx`
- `tests/Feature/BecaRegistrationTest.php`

Creado: `docs/OMR_CLAVE_UNIFICADA.md`.

Sin archivos fuente eliminados. `public/build` regenerado. Sin cambios del esquema, dependencias o despliegue; no corresponde recrear la base de datos.

## Verificación

- `php artisan test --filter='Omr|BecaRegistrationTest'` dentro del contenedor del proyecto: 17 pruebas aprobadas y 197 aserciones. La prueba nueva configura la modalidad unificada, verifica autorización, identifica y califica los cuatro grupos contra la misma clave y comprueba que el modo separado rechaza un mismo examen.
- Pint aplicado a los tres archivos PHP afectados, sin errores.
- `npx tsc --noEmit`: aprobado.
- `npm run build`: aprobado.
- Navegador: el selector unificado muestra un solo examen para A–B–C–D. Captura: `/tmp/academia-clave-unificada.png`. No se guardó ni se alteró ninguna convocatoria de demostración en esta verificación de interfaz.
- `php artisan test`: 121 aprobadas y 2 fallos previos, con 854 aserciones. Los fallos siguen siendo `CatalogoManagementTest` (permiso de secretaria) y `StudentCarnetTest` (propiedades extra en respuesta Inertia), ajenos a esta tarea.

Permanece pendiente la prueba física del escáner con teléfono y cartilla impresa, indicada en `OMR_ESCANER_CAMBIOS.md`; esta tarea modifica la selección de claves, no el lector de cámara.
