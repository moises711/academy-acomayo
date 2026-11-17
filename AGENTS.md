# AGENTS.md — Sistema Web de Academia Preuniversitaria

Laravel + Inertia + React + TypeScript. Idioma de la interfaz y de los mensajes: **español**.
Antes de trabajar, lee `composer.json` y `package.json` para conocer versiones y librerías
instaladas. Usa lo que ya existe; no agregues dependencias sin preguntar.

**FASE ACTUAL: DESARROLLO.** No hay datos reales; la base se destruye y recrea libremente.
(Al salir a producción, cambiar a "PRODUCCIÓN": ahí sí se permiten migraciones incrementales
y se prohíbe `migrate:fresh`.)

---

## 0. LAS 10 REGLAS QUE NUNCA SE ROMPEN

1. **No crear migraciones `add_*`/`alter_*`/`update_*`/`modify_*`/`change_*`.** Se edita la migración `create_*` original.
2. Tras tocar migraciones: `php artisan migrate:fresh --seed` debe terminar sin errores.
3. Un cambio de BD se completa en **todas** las capas (sección 3), nunca a medias.
4. Se autoriza por **permiso**, validado en el backend. Ocultar un botón no es seguridad.
5. En rutas `/mi/...` el estudiante sale del usuario autenticado, jamás de la URL ni del formulario.
6. Dinero siempre `decimal(10,2)`. Nunca `float`.
7. El QR contiene un UUID aleatorio, **nunca el DNI**.
8. Los pagos se **anulan**, no se borran.
9. No inventes tablas, columnas ni endpoints fuera del esquema; si falta algo, **pregunta**.
10. No declares terminado nada que no hayas ejecutado y verificado. Si no puedes correr un comando, dilo.

---

## 1. Comandos del proyecto

Verifica en `composer.json` / `package.json` cuáles existen; usa los reales y no inventes scripts.

| Acción | Comando esperado |
|---|---|
| Recrear base con datos demo | `php artisan migrate:fresh --seed` |
| Tests | `php artisan test` |
| Formato PHP | `./vendor/bin/pint` |
| Tipos TypeScript | `npx tsc --noEmit` |
| Build frontend | `npm run build` |
| Desarrollo | `composer run dev` o `npm run dev` + `php artisan serve` |

---

## 2. Migraciones

- Para cambiar una tabla existente: **edita su `create_<tabla>_table`**.
- Tabla nueva: una sola migración `create_<tabla>_table` completa.
- Tabla en desuso: elimina migración, modelo, controlador, requests, policies, páginas y tipos.
- El orden de archivos respeta las llaves foráneas (tabla referenciada primero). Si hace falta,
  renombra el timestamp.
- Nada de transformación de datos en migraciones; los datos de prueba van en seeders.
- Si crees que hace falta una migración incremental, **pregunta antes**.
- Si encuentras migraciones `add_*` existentes, **fusiónalas** en su `create_*` y elimínalas.

## 3. Todo cambio de BD incluye el conjunto completo

1. Migración original · 2. Modelo (`$table`, `$primaryKey`, `$fillable`, `$casts`, relaciones) ·
3. Factory y Seeder · 4. Form Requests y Policies · 5. Controladores y rutas ·
6. Tipos en `resources/js/types` · 7. Páginas React afectadas · 8. Tests.

## 4. Convenciones de base de datos

- PK con nombre propio (`id_estudiante`, `id_ciclo`, `id_matricula`...). Todo modelo declara
  `$table` y `$primaryKey`.
- FK con `foreignId(...)->constrained(tabla, columna_pk)` y `onDelete` **explícito**.
- Enums: capitalización idéntica a `resources/js/types` (ej. `'Presencial'`, `'Pendiente'`).
  **La fuente de verdad de los valores es el archivo de tipos**; cambiar uno implica cambiar el otro.
- Únicos obligatorios: `(id_estudiante, id_ciclo)` en matrículas · `(id_simulacro, id_matricula)`
  en resultados · `(id_matricula, id_horario, fecha)` en asistencias ·
  `(id_evaluacion, id_matricula)` en notas · `(id_hoja, numero_pregunta)` en respuestas_hoja.
- Índices en toda FK y en campos de búsqueda (dni, apellido, fecha_vencimiento).
- Auditoría y fechas: `created_at`/`updated_at` en tablas principales.
- Los puestos de ranking se calculan con consulta o vista; no duplicar la lógica en el frontend.
- **Fuente única de verdad del esquema: `docs/ESQUEMA_FINAL.sql`** (sintaxis PostgreSQL como referencia;
  traducir al motor real del proyecto). Es guía de diseño: se implementa como migraciones `create_*`
  de Laravel, nunca copiando el SQL. Si el SQL contradice este archivo o los tipos de `resources/js/types`,
  **pregunta** antes de decidir.

---

## 5. Roles, permisos y seguridad

- Tablas `roles`, `permisos`, `rol_permiso`. Sin enum de rol.
- Roles: admin, secretaria, caja, profesor, estudiante, apoderado.
- Se usa `can('modulo.accion')`, no el nombre del rol. Los permisos del usuario se comparten
  por Inertia (`HandleInertiaRequests`) para armar menús y botones.
- Cada ruta del panel protegida con middleware o policy; cada recurso nuevo trae su policy y
  un test de autorización (con permiso → 200, sin permiso → 403).
- Portal `/mi/...`: el apoderado solo accede a estudiantes vinculados a él.
- Mass assignment: `$fillable` explícito. Nunca `$guarded = []`.
- Inertia: no enviar al frontend campos sensibles (`password`, tokens, `codigo_qr` ajeno).
- Subida de archivos: validar tipo y tamaño, guardar en disco **privado**, nombres generados
  por el servidor, nunca confiar en el nombre original.
- Limitar intentos (rate limiting) en login y en el endpoint de escaneo QR.
- Secretos solo en `.env`. No leer, mostrar ni modificar `.env` y no subir credenciales al código.

---

## 6. Módulos con reglas propias

**Asistencia por QR**
- `estudiantes.codigo_qr`: UUID aleatorio único, regenerable.
- Cada escaneo crea un `registros_ingreso` (quién, cuándo, método).
- Del escaneo se marcan las clases de HOY del grupo (`presente`/`tarde` según tolerancia configurable).
- Comando programado diario que marca `falta` a quien no registró ingreso.
- Responder "ya registrado" ante doble escaneo. Corrección manual con `origen = manual` + auditoría.
- La pantalla de escaneo soporta cámara y lector USB (campo siempre enfocado, termina en Enter).

**Lectura de hojas de respuestas (OMR)**
- La lectura de hojas se realiza mediante carga de imágenes y revisión manual en React.
- No incorporar procesamiento Python/OpenCV ni invocación de procesos externos.
- **Ante la duda no adivinar:** marcar `dudosa` y enviar a revisión manual.
- Calificar con `claves_respuesta` y escribir en `resultados_simulacros` y `resultados_curso`;
  luego recalcular puestos. Toda corrección manual queda auditada.

**Finanzas**
- `cuotas` = lo que se debe; `pagos` = lo cobrado; se unen por `pagos_cuotas` (pagos parciales y múltiples).
- Anular un pago exige motivo y usuario; los anulados no cuentan en el estado de cuenta.
- Comprobante: número único.

**Auditoría**
- Crear, editar, eliminar o anular en pagos, cuotas, notas, asistencias, matrículas y usuarios
  se registra en `auditoria` (usuario, acción, datos antes/después).

---

## 7. Diseño de interfaz

- Sigue `docs/DESIGN.md` (tokens, componentes, reglas). Si no existe, créalo antes de crear pantallas.
- Un solo sistema de diseño: nada de colores, tamaños o espaciados sueltos fuera de los tokens.
- Modo claro y oscuro. Responsivo real a 360 px, 768 px y 1280 px; el menú lateral pasa a drawer
  en móvil y las tablas se vuelven tarjetas o scroll contenido.
- Toda pantalla de datos implementa los **4 estados**: cargando (skeleton), vacío (con acción),
  error (con reintento) y con datos.
- Formularios: error por campo, botón con estado "guardando", toast de éxito, confirmación antes
  de acciones destructivas.
- Tablas: búsqueda, filtros, orden y paginación.
- Accesibilidad básica: contraste, foco visible, etiquetas en inputs, navegación con teclado.
- Componentes reutilizables antes que código duplicado (DataTable, StatCard, EmptyState,
  StatusBadge, ConfirmDialog, FormField, PageHeader).
- Sin `lorem ipsum` ni pantallas a medias; datos de ejemplo realistas vía seeders.
- Landing pública: **todo el contenido en un único archivo de datos** (`resources/js/data/landing.ts`)
  con comentarios `// TODO: reemplazar con información real`.

## 8. Regional

- Moneda: soles (`S/`), 2 decimales. Zona horaria: `America/Lima`. Locale: `es`.
- Fechas en pantalla: `dd/mm/aaaa`; en base de datos y API: ISO 8601.
- Nombres y datos de prueba: realistas para Perú (DNI de 8 dígitos, teléfonos de 9 dígitos).

---

## 9. Pide permiso antes de

- Instalar o actualizar dependencias de Composer o npm.
- Cambiar la estructura de carpetas o renombrar módulos completos.
- Borrar archivos que no sean parte de la tarea.
- Cambios de más de 3 tablas o de relaciones centrales: **muestra primero un plan corto**.
- Tocar configuración de despliegue, `.env`, colas o programador de tareas.
- Comandos destructivos de Git (`reset --hard`, `push --force`) o de base de datos fuera de `migrate:fresh`.

## 10. Definición de "terminado"

Una tarea está terminada solo si se cumple TODO lo siguiente:
1. `php artisan migrate:fresh --seed` sin errores (si tocaste BD).
2. `php artisan test` en verde, con tests nuevos para lo que agregaste (incluida autorización).
3. `./vendor/bin/pint` aplicado y `npx tsc --noEmit` sin errores.
4. `npm run build` compila.
5. Los tipos TypeScript coinciden con lo que devuelve el backend.
6. La pantalla nueva cumple la sección 7 (4 estados, responsivo, modo oscuro).
7. Entregaste un resumen: archivos **creados / modificados / eliminados**, comandos ejecutados con
   su resultado, y lo que quedó pendiente o no pudiste verificar.

## 11. Forma de trabajar

- Haz el cambio mínimo necesario y completo; no refactorices código ajeno a la tarea sin avisar.
- Si algo del pedido es ambiguo o contradice este archivo, **pregunta** antes de implementar.
- Si descubres un error previo en el proyecto, repórtalo; no lo escondas ni lo "arregles" en silencio
  si está fuera del alcance.
- Mantén las respuestas concretas: qué hiciste, qué verificaste, qué falta.
