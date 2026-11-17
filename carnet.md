# PROMPT PARA CODEX — Exportación de estudiantes, QR masivo y diseñador de carnets

> Pega TODO lo que está debajo de la línea en Codex.
> Antes: confirma que `docs/ESQUEMA_FINAL.sql` (versión de 39 tablas), `AGENTS.md` y
> `docs/DESIGN.md` (con el addendum) están en el repositorio.

---

Quiero agregar tres funciones al sistema. Respeta `AGENTS.md` (migraciones `create_*` editadas,
sin `add_*`; permisos validados en el backend; tipos TS sincronizados) y `docs/DESIGN.md`
(4 estados de UI, responsivo, modo claro/oscuro, español). El esquema de referencia es
`docs/ESQUEMA_FINAL.sql`; ya incluye la tabla `plantillas_carnet`, la columna
`estudiantes.foto_path` y la acción `exportar` en `auditoria`.

## ETAPA A — Plan (NO escribas código todavía)

Entrégame un plan corto con:
1. Qué librerías ya están en `composer.json` / `package.json` y cuáles faltarían para:
   Excel (.xlsx), PDF, Word (.docx), generación de QR, ZIP y render del carnet.
   **No instales nada sin mi confirmación.** Propón una opción por necesidad y por qué.
2. Cambios de migraciones, modelos, permisos y seeders necesarios.
3. Rutas, controladores, jobs de cola y pantallas nuevas.
4. Riesgos (memoria con muchos registros, fuentes en PDF, tamaño real de impresión).

Espera mi confirmación antes de continuar con la Etapa B.

## ETAPA B — Base de datos y permisos

- Implementa en las migraciones `create_*`: `estudiantes.foto_path`, tabla `plantillas_carnet` y
  `exportar` como acción válida de `auditoria`. Modelos, factories, seeders, tipos TS.
- Permisos nuevos (seeder y asignación):
  - `estudiantes.exportar` → admin, secretaria.
  - `carnets.generar` (generar/imprimir carnets y descargar QR) → admin, secretaria.
  - `carnets.disenar` (crear y editar plantillas) → solo admin.
- Carga de **foto del estudiante** en el formulario de estudiante: validar tipo (jpg/png/webp) y
  tamaño máximo, guardar en disco **privado** con nombre generado, recorte cuadrado/vertical
  con vista previa. Sin foto, el carnet usa un marcador con las iniciales.

## ETAPA C — Exportar registros de estudiantes (PDF, Excel, Word)

En la pantalla de Estudiantes, botón **«Exportar»** que abre un panel con:
- **Formato:** Excel (.xlsx), PDF o Word (.docx).
- **Qué exportar:** (a) solo las filas seleccionadas, (b) todo lo que muestra el filtro actual,
  (c) todos los estudiantes. Mostrar cuántos registros se exportarán antes de confirmar.
- **Filtros** (los mismos de la tabla): ciclo, grupo, estado, estado de matrícula, con deuda / sin deuda.
- **Columnas a incluir** (casillas): DNI, nombres y apellidos, email, teléfono, fecha de nacimiento,
  dirección, colegio de procedencia, año de egreso, ciclo, grupo, estado de matrícula, beca,
  carrera objetivo, apoderado y su teléfono, deuda pendiente. Recordar la última selección del usuario.
- **PDF:** opción de orientación (vertical/horizontal), encabezado con nombre y logo de la academia,
  fecha y usuario que exportó, numeración de páginas, fuentes legibles (mínimo 8 pt).
- **Excel:** fila de encabezado con estilo, columnas con ancho automático, filtros activados,
  primera fila fija, formato correcto de fechas y de montos en soles.
- **Word:** documento con título, tabla con estilo y pie con fecha.
- Hasta N filas (configurable, por defecto 500) se descarga directo; por encima se genera en
  **cola** con barra de progreso y aviso con botón de descarga al terminar. Procesar por bloques
  (chunk) para no agotar memoria.
- Archivos generados en disco **privado**, descarga por enlace firmado y temporal, y borrado
  automático a las 24 h (comando programado).
- **Seguridad:** exige `estudiantes.exportar` en el backend; protege contra **inyección de
  fórmulas** en Excel (celdas que empiecen por `=`, `+`, `-`, `@` se escapan); cada exportación
  se registra en `auditoria` con acción `exportar` (quién, formato, filtros, nº de registros).

## ETAPA D — Descarga masiva de QR

Pantalla/panel **«Descargar QR»** (permiso `carnets.generar`):
- **Selección:** por ciclo, por grupo, por estudiantes marcados o por búsqueda.
- **Contenido del QR:** solo el `codigo_qr` (UUID). **Nunca el DNI ni datos personales.**
- **Formatos de salida:**
  - **ZIP de imágenes PNG** (un archivo por estudiante, nombrado `apellido_nombre_dni.png`).
  - **ZIP de SVG** (vectorial, ideal para imprenta).
  - **PDF de planilla:** cuadrícula de QR en A4 con el nombre debajo.
- **Tamaño ajustable:**
  - PNG: de 200 a 2000 px de lado (con atajos 300 / 600 / 1000).
  - PDF: tamaño del QR en **milímetros** (de 15 a 60 mm) y número de columnas/filas por página.
  - Si el QR queda menor a 20 mm, mostrar una advertencia de que podría leerse mal al imprimir.
- **Opciones:** margen (zona blanca) mínimo de 4 módulos, nivel de corrección de errores
  (M por defecto, Q/H opcionales), mostrar u ocultar el nombre y el grupo bajo el QR.
- **Vista previa** con 1–3 ejemplos antes de generar.
- Si son más de N estudiantes, generar en cola con progreso (igual que la Etapa C).
- Registrar la descarga en `auditoria` (acción `exportar`).

## ETAPA E — Diseñador de carnets (plantillas configurables)

Módulo **«Carnets → Diseño»** (permiso `carnets.disenar`) con editor de dos zonas: controles a la
izquierda y **vista previa en vivo** a la derecha (en móvil: vista previa arriba, controles abajo).

**Tamaño y forma:**
- Presets: tarjeta estándar 85,6 × 54 mm, vertical 54 × 85,6 mm, A7, y **medidas personalizadas**
  en mm (validadas contra los límites de la tabla: 30 a 220 mm).
- Orientación horizontal o vertical.

**Contenido ajustable (activar/desactivar cada campo):**
nombre completo, DNI, código del estudiante, grupo, ciclo, **vigencia** (fecha fin del ciclo),
foto, logo de la academia, nombre de la academia y un texto libre corto.

**QR:**
- Tamaño en mm (mínimo 10; advertencia por debajo de 18 mm).
- Posición: izquierda, derecha, centro o esquina; con borde blanco obligatorio para lectura.
- El QR siempre negro sobre fondo blanco, aunque el carnet tenga color de fondo.

**Estilo:** color de fondo o imagen de fondo, color de acento, colores de texto, tipografía y
tamaño de letra por campo, esquinas redondeadas, y franja superior con el color de marca.
Todo con límites razonables y valores por defecto tomados de los tokens de marca.

**Frente y reverso:** el reverso es opcional (reglas de uso, teléfono, dirección, texto legal).

**Plantillas:** guardar con nombre, duplicar, marcar una como **predeterminada**, activar o
desactivar. Los archivos de logo y fondo se suben al disco privado y se validan (tipo y tamaño).
La configuración visual se guarda en `plantillas_carnet.config` (JSON validado con un esquema; no
aceptar claves arbitrarias).

## ETAPA F — Generar e imprimir carnets

Pantalla **«Carnets → Generar»** (permiso `carnets.generar`):
1. Elegir **plantilla** (por defecto la predeterminada).
2. Elegir **a quién**: un estudiante, selección múltiple, un grupo o todo un ciclo. Opción
   «solo matrículas activas».
3. Elegir **hoja de impresión:** A4 u otras, con **N carnets por página** calculados según el
   tamaño del carnet, márgenes ajustables, **marcas de corte** opcionales y modo
   «frente y reverso» (con orden de reverso correcto para imprimir a doble cara).
4. Vista previa de la primera hoja y descarga en **PDF**.
5. Descarga individual de un carnet desde la ficha del estudiante.

Requisitos de impresión:
- El PDF debe tener el **tamaño físico exacto en mm** (sin escalado del navegador) y resolución
  suficiente para impresión (≥ 300 dpi en imágenes y QR vectorial cuando sea posible).
- Fuentes incrustadas; el QR nunca se rasteriza con pérdida.
- Si falta la foto, usar el marcador con iniciales; si falta algún dato obligatorio, avisar antes
  de generar y listar los estudiantes afectados.
- Generación en cola con progreso cuando sean muchos carnets.
- **Regenerar QR** de un estudiante invalida los carnets anteriores: pedir confirmación, avisar
  que habrá que reimprimir y dejarlo en `auditoria`.

## ETAPA G — Pruebas y cierre

Tests obligatorios:
- **Autorización:** sin permiso → 403 en cada ruta de exportación, QR y carnets; con permiso → 200.
- **Exportación:** que cada formato genere un archivo válido que se pueda abrir, con las columnas
  elegidas, los filtros aplicados y el conteo correcto; prueba de inyección de fórmulas.
- **Cola:** que más de N filas pase por cola y deje el archivo listo para descarga firmada.
- **QR:** que el contenido codificado sea exactamente el `codigo_qr`, que el PNG respete el tamaño
  pedido en px y que el PDF respete el tamaño en mm.
- **Carnet:** que el PDF tenga el tamaño de página esperado, que el número de carnets por hoja
  sea correcto y que las plantillas rechacen medidas fuera de rango.
- **Auditoría:** que cada exportación y descarga deje su registro.

Al terminar:
1. `php artisan migrate:fresh --seed`, `php artisan test`, `./vendor/bin/pint`,
   verificación de tipos TS y `npm run build`, todos sin errores.
2. Entrega el informe habitual: archivos **creados / modificados / eliminados**, comandos
   ejecutados con resultado, y pendientes o limitaciones (por ejemplo, fuentes del PDF o
   rendimiento con muchos registros).
3. Describe cómo probar manualmente: un ejemplo de exportación de cada formato, el ZIP de QR a dos
   tamaños, y un carnet impreso a escala real (verificar con regla que mide lo configurado).