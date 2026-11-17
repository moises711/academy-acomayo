# PROMPT PARA CODEX — Sistema Web de Academia Preuniversitaria

> CÓMO USAR: pega primero el **PROMPT MAESTRO** (una sola vez al inicio de la sesión).
> Después pega **una fase a la vez**, y no pases a la siguiente hasta revisar la anterior.
> El archivo `AGENTS.md` de la raíz del proyecto tiene prioridad en reglas de migraciones y convenciones.

---

## PROMPT MAESTRO (pegar primero)

Eres el desarrollador principal de un sistema web para una **academia preuniversitaria**.
Stack del proyecto: Laravel + Inertia + React + TypeScript. **Antes de escribir código, revisa
`composer.json`, `package.json` y la estructura actual** y usa las librerías que ya están
instaladas (UI, iconos, gráficos). No agregues librerías nuevas si ya existe una equivalente;
si necesitas una nueva, dilo y justifícala.

### Qué se está construyendo
1. **Página web pública (landing)**, responsiva y moderna, con contenido de ejemplo claramente
   reemplazable, porque la academia aún no entrega su información final.
2. **Panel de administración** con diseño moderno, usable en computadora, tablet y celular.
3. **Roles y permisos**: admin, secretaria, caja, profesor, estudiante, apoderado.
4. **Asistencia por QR**: la secretaria escanea el carnet del estudiante.
5. **Lectura automática de hojas de respuestas (OMR)**: hojas con burbujas/puntitos que se
   escanean o fotografían para calificar los simulacros.
6. **Portal del estudiante** con sus notas, pagos, asistencia y más datos.

### Reglas que nunca se rompen
- Obedece `AGENTS.md`. En especial: **NO crear migraciones `add_*` / `alter_*`**; edita las
  migraciones `create_*` originales y verifica con `php artisan migrate:fresh --seed`.
- Esquema de referencia: **`docs/ESQUEMA_FINAL.sql`** (única fuente de verdad, 38 tablas).
  Es referencia de diseño; se implementa como migraciones `create_*`, no se copia el SQL.
- Llaves primarias con nombre propio (`id_estudiante`, etc.) y `$primaryKey` en cada modelo.
- Dinero siempre `decimal(10,2)`.
- Autorización por **permiso**, validada en el backend (no solo ocultar botones).
- Portal del estudiante: el estudiante sale SIEMPRE del usuario autenticado, nunca de la URL.
- No inventes tablas, columnas ni endpoints fuera del esquema. Si falta algo, pregunta.
- Todo texto visible al usuario va en **español**.

### Estándar de calidad de diseño (aplica a TODA pantalla)
- Estilo moderno y limpio: espacios generosos, bordes redondeados, sombras suaves,
  tipografía clara con jerarquía (títulos, subtítulos, texto de apoyo).
- **Un solo sistema de diseño**: define tokens (colores, radios, sombras, espaciados, tipografía)
  una vez y reutilízalos. Nada de colores o tamaños sueltos "a ojo".
- Modo claro y modo oscuro.
- Responsivo real: probado en 360px, 768px y 1280px. Las tablas en celular se convierten en
  tarjetas o hacen scroll horizontal contenido; el menú lateral pasa a menú tipo drawer.
- Todo componente de datos debe tener sus **4 estados**: cargando (skeleton), vacío (con
  mensaje y botón de acción), error (con opción de reintentar) y con datos.
- Formularios: validación visible por campo, botón con estado "guardando", mensajes de éxito
  (toast), confirmación antes de acciones destructivas.
- Tablas: búsqueda, filtros, ordenamiento, paginación, y acciones por fila.
- Accesibilidad: contraste suficiente, foco visible, etiquetas en inputs, navegación con teclado.
- Consistencia: botones, inputs, badges de estado y modales se ven y se comportan igual en
  todo el sistema. Estados con colores semánticos (activo=verde, pendiente=ámbar, vencido/falta=rojo).
- No dejes pantallas "a medias" ni texto "lorem ipsum" en el panel. Los datos de ejemplo salen
  de seeders realistas (nombres peruanos, cursos reales de preuniversitario, montos en soles).

### Forma de trabajar
- Para cambios grandes, **primero un plan corto** y espera confirmación.
- Al terminar cada fase entrega: lista de archivos creados/modificados/eliminados, comandos
  ejecutados y su resultado, y qué quedó pendiente.
- Antes de cerrar: `php artisan migrate:fresh --seed`, `php artisan test`, `./vendor/bin/pint`
  y verificación de tipos TypeScript sin errores.

Confirma que entendiste con un resumen de 5 líneas y espera la Fase 0.

---

## FASE 0 — Reconocimiento (no modifiques nada)

Analiza el repositorio y responde:
1. Versiones de Laravel, React, Inertia, Tailwind y librería de UI instalada.
2. Lista de migraciones, modelos, controladores y páginas existentes.
3. Migraciones `add_*` / `alter_*` presentes (hay que fusionarlas en la Fase 1).
4. Qué módulos ya existen y funcionan: estudiantes, ciclos, cursos, matrículas, pagos,
   simulacros, resultados, horarios.
5. Qué existe del layout/menú actual y qué problemas de diseño ves.
6. Riesgos que detectas para pasar al esquema nuevo.

Entrega un informe. **No cambies archivos.**

**Aceptación:** informe completo y honesto, sin inventar archivos que no existen.

---

## FASE 1 — Base de datos consolidada

Implementa el esquema de `docs/ESQUEMA_FINAL.sql` (38 tablas, incluye OMR, consultas y configuraciones)
dentro de las migraciones `create_*` originales, respetando `AGENTS.md`:
- Fusiona y elimina todas las migraciones `add_*`.
- Renombra lo necesario: tu antiguo `Pago` pasa a **`Cuota`**; el nuevo `Pago` es el dinero cobrado,
  unido a cuotas por `pagos_cuotas`.
- `Estudiante` pierde `tipo_beca`, `porcentaje_descuento`, `saldo_favor` (la beca va en matrícula,
  el saldo sale de congelamientos).
- `Matricula` usa `id_carrera_objetivo` (catálogo) en vez de textos.
- `Horario` usa `id_grupo`, `id_profesor`, `id_aula`.
- `ResultadoSimulacro` usa `id_matricula`.
- Roles y permisos en tablas (`roles`, `permisos`, `rol_permiso`), no enum.

**Tablas OMR (ya incluidas en `ESQUEMA_FINAL.sql`; este resumen es solo orientativo):**
- `plantillas_hoja`: id_plantilla, nombre, num_preguntas, opciones_por_pregunta (ej. 5: A–E),
  config (JSON con coordenadas de burbujas y marcas de alineación), activa.
- `claves_respuesta`: id_clave, id_simulacro, id_curso, numero_pregunta, respuesta_correcta (char),
  puntos_correcta, puntos_incorrecta (puede ser negativo). Único (id_simulacro, numero_pregunta).
- `hojas_respuesta`: id_hoja, id_simulacro, id_matricula (nullable hasta identificarla),
  id_plantilla, codigo_hoja (único), archivo_path, estado (pendiente / procesada / revision / error),
  confianza (decimal), procesada_en, id_usuario (quién la subió).
- `respuestas_hoja`: id_hoja, numero_pregunta, marcada (char nullable),
  estado (valida / blanco / multiple / dudosa). Único (id_hoja, numero_pregunta).

**Seeders:**
- Roles y permisos con la matriz acordada (admin todo; secretaria matrículas + asistencia QR +
  asistencia manual + anuncios; caja cobros; profesor asistencia manual + notas + materiales;
  estudiante y apoderado solo `portal.mis_datos`).
- Un usuario de cada rol para pruebas (credenciales documentadas en `docs/USUARIOS_DEMO.md`).
- Datos demo realistas: 2 ciclos, 6 grupos, ~60 estudiantes, horarios, cuotas y pagos variados
  (pagados, pendientes, vencidos), asistencias, evaluaciones, notas, simulacros y resultados.

**Aceptación:** `migrate:fresh --seed` sin errores; cero migraciones `add_*`; modelos con relaciones
completas; tests de relaciones básicas pasan.

---

## FASE 2 — Sistema de diseño y layout del panel

1. Crea `docs/DESIGN.md` con los tokens (colores, tipografía, radios, sombras, espaciados), los
   componentes base y las reglas de uso. Todas las fases siguientes deben respetarlo.
2. Construye el layout del panel:
   - Menú lateral colapsable (drawer en móvil) con iconos, agrupado por módulos:
     Inicio, Estudiantes, Académico, Docentes, Asistencia, Finanzas, Evaluaciones,
     Comunicación, Configuración.
   - Barra superior: buscador global, toggle claro/oscuro, notificaciones, menú de usuario.
   - Migas de pan y encabezado de página con título y botón de acción principal.
3. **El menú se genera según los permisos del usuario** (compartidos por Inertia). Cada ítem
   declara el permiso que requiere; sin permiso, no aparece Y la ruta devuelve 403.
4. Componentes reutilizables: DataTable (búsqueda, filtros, orden, paginación, vista tarjetas en móvil),
   StatCard, EmptyState, Skeletons, PageHeader, ConfirmDialog, FormField, StatusBadge, FileUpload.
5. Login moderno, recuperación de contraseña y redirección por rol:
   admin/secretaria/caja/profesor → panel; estudiante/apoderado → portal `/mi`.

**Aceptación:** capturas o descripción del layout en 360/768/1280px; modo oscuro funcional;
un usuario sin permiso no ve ni accede a la sección; `docs/DESIGN.md` creado.

---

## FASE 3 — Página web pública (landing)

Crea la página pública en `/` con diseño moderno y 100% responsiva. **Todo el contenido debe
vivir en UN solo archivo de datos** (`resources/js/data/landing.ts`) con comentarios `// TODO:
reemplazar con información real`, para que la academia solo edite ese archivo. Nada de textos
sueltos dentro de los componentes.

Secciones:
1. **Barra de navegación** fija con enlaces ancla, botón "Matricúlate" y botón "Ingresar" (login).
2. **Hero**: título fuerte, subtítulo, 2 llamados a la acción, imagen/ilustración placeholder
   y 3–4 cifras destacadas (ingresantes, años de experiencia, etc., de ejemplo).
3. **Por qué elegirnos**: 4–6 tarjetas con icono.
4. **Programas / ciclos**: se alimentan de la tabla `ciclos` (los activos/programados) con
   modalidad, fechas y botón de consulta. Si no hay datos, mostrar el contenido de ejemplo.
5. **Plana docente**: tarjetas de profesores (de ejemplo, reemplazables).
6. **Resultados e ingresantes**: galería/contadores de ejemplo.
7. **Horarios y modalidades**.
8. **Testimonios**.
9. **Preguntas frecuentes** (acordeón).
10. **Contacto**: formulario (nombre, teléfono, interés) que guarda en una tabla `consultas`
    (agrégala a la migración correspondiente) + botón de WhatsApp + dirección + mapa placeholder.
11. **Footer** con enlaces y redes sociales.

Requisitos:
- Imágenes placeholder locales (no depender de enlaces externos que se rompan). Usa gradientes
  o ilustraciones simples; deja claro dónde va cada foto real.
- Rendimiento y SEO básico: títulos y meta descripción, `alt` en imágenes, carga diferida de imágenes,
  etiquetas semánticas.
- Animaciones sutiles al hacer scroll (sin exagerar, respetando `prefers-reduced-motion`).
- La sección de consultas aparece en el panel para secretaría (listado + estado: nueva / contactada).

**Aceptación:** se ve profesional en móvil y escritorio; cambiar `landing.ts` actualiza la página;
el formulario de contacto guarda y aparece en el panel; Lighthouse accesibilidad razonable.

---

## FASE 4 — Módulos del panel (haz un módulo por vez)

Orden: **Configuración (catálogos y usuarios/roles) → Estudiantes y matrículas → Académico →
Docentes → Finanzas (cuotas y caja) → Evaluaciones y simulacros → Comunicación**.

Para cada módulo: CRUD completo, DataTable con filtros útiles, formularios validados con Form
Requests, Policies por permiso, tests de autorización, y tipos TypeScript sincronizados.

Puntos específicos:
- **Dashboard de inicio** por rol:
  - Admin: matriculados, ingresos del mes, cuotas vencidas, asistencia de hoy, gráfico de ingresos
    y de simulacros.
  - Secretaría: acceso rápido al escáner, matrículas recientes, consultas nuevas del landing.
  - Caja: cobros del día por método, morosos, botón "Registrar pago".
  - Profesor: sus clases de hoy y acceso a tomar asistencia / cargar notas.
- **Ficha del estudiante**: pestañas (Datos, Apoderados, Matrículas, Asistencia, Notas, Pagos, Simulacros).
- **Nueva matrícula**: asistente por pasos (estudiante → ciclo y grupo → beca y carrera objetivo →
  plan de cuotas generado automáticamente → confirmación).
- **Caja**: buscar alumno, ver cuotas pendientes, registrar pago (puede cubrir varias cuotas o ser
  parcial), emitir comprobante imprimible, **anular** pago con motivo, cierre de caja diario.
- **Horarios**: vista semanal tipo calendario por grupo/profesor/aula con **validación de cruces**.
- **Auditoría**: registra cambios en pagos, notas, asistencias, matrículas y usuarios; pantalla
  de consulta solo para admin.

**Aceptación (por módulo):** CRUD funcional, permisos probados con tests, 4 estados de UI,
responsivo, sin romper módulos previos.

---

## FASE 5 — Asistencia por QR

**Carnet:**
- Cada estudiante tiene `codigo_qr` (UUID aleatorio). **Nunca el DNI dentro del QR.**
- Genera el carnet imprimible (PDF o vista de impresión) con foto placeholder, nombre, grupo,
  ciclo y QR. Impresión individual y por lote de un grupo. Botón "regenerar QR" (por pérdida).

**Pantalla de escaneo (rol secretaria, permiso `asistencia.registrar_qr`):**
- Modo cámara (navegador, funciona en celular y laptop) **y** modo lector USB (los lectores
  de código actúan como teclado y terminan con Enter: el campo de captura debe estar siempre
  enfocado).
- Al escanear: validar el QR, buscar la matrícula activa y mostrar una tarjeta GRANDE:
  verde "Ingreso registrado" con nombre y grupo, o rojo con el motivo (QR inválido, sin
  matrícula activa, matrícula retirada/congelada, ya registrado hoy).
- Alerta amarilla si el estudiante tiene cuotas vencidas (según política configurable: solo
  avisar o bloquear).
- Evitar doble registro (ignorar el mismo QR por unos segundos y avisar "ya registrado").
- Sonido y vibración de confirmación, lista de los últimos escaneos, contador del día.
- Registra en `registros_ingreso` (quién escaneó, hora, método).

**Lógica de asistencia por clase:**
- Del escaneo se marcan las asistencias de las clases de HOY del grupo según `horarios`:
  `presente` si llegó dentro de la tolerancia, `tarde` si pasó. La tolerancia es configurable.
- Comando programado diario (`php artisan schedule`) que cierra el día: marca `falta` a quien
  no registró ingreso.
- Corrección manual por profesor o secretaria (permiso `asistencia.registrar_manual`), guardando
  `origen = manual` y quién la cambió; queda en auditoría.
- Reportes: asistencia por grupo, por estudiante (porcentaje y faltas seguidas), exportable.

**Aceptación:** flujo completo probado (escaneo válido, inválido, duplicado, sin matrícula);
tests del servicio de asistencia; un usuario sin el permiso no puede acceder a la pantalla ni al endpoint.

---

## FASE 6 — Lectura de hojas de respuestas (OMR)

Objetivo actual: subir imágenes escaneadas de las hojas de burbujas y registrar sus respuestas
mediante revisión manual. No hay generación de hojas ni lectura automática del escaneo.

1. **Integración Laravel:** subir imágenes JPG o PNG (varias a la vez) → guardar en disco privado
   y crear filas en `hojas_respuesta`; las respuestas se registran desde la interfaz.
2. **Pantalla de revisión:** lista de hojas y muestra la imagen original; permite asignar estudiante
   y corregir respuestas a mano. Hojas sin estudiante identificado se asignan manualmente.
3. **Calificación:** con `claves_respuesta` (pantalla para cargar la clave por simulacro, manual
   o por Excel/CSV) calcula puntaje por curso y total aplicando puntos por correcta/incorrecta/blanco.
   Escribe en `resultados_simulacros` y `resultados_curso`, y recalcula los puestos general y por
   carrera objetivo (usar la vista o consulta de ranking; no duplicar lógica).
4. Auditoría de correcciones manuales y posibilidad de recalificar si cambia la clave.

**Aceptación:** carga de imagen → revisión manual → calificar → ranking; las dudas permanecen en
revisión y toda corrección manual queda auditada.

---

## FASE 7 — Portal del estudiante y apoderado (`/mi/...`)

Todas las rutas toman el estudiante del usuario autenticado. El apoderado elige entre sus hijos.
Diseño pensado primero para **celular**.

Pantallas:
- **Inicio**: saludo, **carnet con QR para mostrar desde el celular**, clases de hoy, anuncios,
  alerta de próxima cuota, resumen (promedio, asistencia, deuda).
- **Mis notas**: promedio por curso, listado de evaluaciones y nota, evolución de simulacros con
  puesto general y por carrera, y gráfico de progreso.
- **Mi asistencia**: porcentaje general y por curso, calendario con faltas/tardanzas.
- **Mis pagos**: estado de cuenta (total, pagado, deuda), cuotas pendientes con vencimiento,
  historial con comprobantes descargables.
- **Material de estudio**: filtrable por curso.
- **Mi horario** semanal.
- **Mis datos** y cambio de contraseña.

Usa las vistas SQL del esquema (`v_asistencia_resumen`, `v_estado_cuenta`, `v_promedio_notas`).
Entrega el dashboard en una sola petición (tipo `DashboardEstudiante`).

**Aceptación:** tests que demuestren que un estudiante NO puede ver datos de otro cambiando IDs;
apoderado solo ve a sus hijos; responsivo en 360px.

---

## FASE 8 — Revisión final y control de calidad

1. Recorre cada rol con su usuario demo y verifica menús, permisos y pantallas.
2. Revisa consistencia visual contra `docs/DESIGN.md` y corrige discrepancias.
3. Verifica los 4 estados de UI en todas las pantallas de datos.
4. Revisa seguridad: rutas sin protección, IDs manipulables, validaciones faltantes, exposición de
   datos en respuestas de Inertia.
5. Rendimiento: consultas N+1 (usar eager loading), índices faltantes.
6. Ejecuta toda la batería: `migrate:fresh --seed`, tests, pint, tipos TS, build de producción.
7. Entrega un informe con: lo completado, lo pendiente, limitaciones conocidas (en especial
   del OMR) y pasos para desplegar.

**Aceptación:** build de producción sin errores, tests en verde, informe honesto de pendientes.
