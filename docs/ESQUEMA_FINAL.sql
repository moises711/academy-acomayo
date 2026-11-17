-- =====================================================================
-- ESQUEMA FINAL — ACADEMIA PREUNIVERSITARIA  (39 tablas)
-- FUENTE ÚNICA DE VERDAD DEL DISEÑO.  Reemplaza a academia_completo.sql
-- y academia_v3_migracion.sql (no usarlos).
--
-- NOTAS PARA QUIEN LO IMPLEMENTE (Codex):
--  * Está escrito en sintaxis PostgreSQL SOLO como referencia de diseño.
--    Se implementa como migraciones `create_*` de Laravel (nunca copiando este SQL),
--    traduciendo al motor real del proyecto (ver DB_CONNECTION en .env).
--  * Los "enums" son VARCHAR + CHECK. En Laravel: string + validación (o enum()).
--    Los valores y su capitalización coinciden con resources/js/types.
--  * Si el proyecto ya usa la tabla `users` de Laravel para autenticación,
--    mantener una sola tabla `users`, conforme a la decisión del proyecto.
--  * Los nombres de columnas respetan los que ya existen en el frontend
--    (estado_matricula, estado_pago, puntaje_obtenido, etc.).
--  * Laravel puede añadir timestamps() a cualquier tabla.
-- =====================================================================

-- ============ 1. CATÁLOGOS ============
CREATE TABLE universidades (
    id_universidad SERIAL PRIMARY KEY,
    nombre         VARCHAR(150) NOT NULL UNIQUE,
    siglas         VARCHAR(20),
    tipo           VARCHAR(10) CHECK (tipo IN ('Pública','Privada'))
);

CREATE TABLE areas_academicas (
    id_area SERIAL PRIMARY KEY,
    nombre  VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE carreras (
    id_carrera     SERIAL PRIMARY KEY,
    id_universidad INT NOT NULL REFERENCES universidades(id_universidad),
    id_area        INT NOT NULL REFERENCES areas_academicas(id_area),
    nombre         VARCHAR(150) NOT NULL,
    puntaje_minimo DECIMAL(6,2),
    UNIQUE (id_universidad, nombre)
);

CREATE TABLE becas (
    id_beca              SERIAL PRIMARY KEY,
    nombre               VARCHAR(80) NOT NULL,
    tipo                 VARCHAR(15) NOT NULL CHECK (tipo IN ('Beca','Media Beca','Convenio','Otro')),
    porcentaje_descuento DECIMAL(5,2) NOT NULL CHECK (porcentaje_descuento BETWEEN 0 AND 100),
    activa               BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE cursos (
    id_curso     SERIAL PRIMARY KEY,
    nombre_curso VARCHAR(100) NOT NULL UNIQUE,
    categoria    VARCHAR(10) NOT NULL CHECK (categoria IN ('Aptitud','Ciencias','Letras'))
);

CREATE TABLE aulas (
    id_aula        SERIAL PRIMARY KEY,
    nombre         VARCHAR(50) NOT NULL UNIQUE,
    tipo           VARCHAR(12) NOT NULL CHECK (tipo IN ('Presencial','Virtual')),
    capacidad      INT CHECK (capacidad > 0),
    enlace_virtual VARCHAR(255)
);

-- ============ 2. PERSONAS, ROLES Y USUARIOS ============
CREATE TABLE estudiantes (
    id_estudiante       SERIAL PRIMARY KEY,
    dni                 VARCHAR(15) NOT NULL UNIQUE,
    nombre              VARCHAR(80) NOT NULL,
    apellido            VARCHAR(80) NOT NULL,
    email               VARCHAR(120) UNIQUE,
    telefono            VARCHAR(20),
    fecha_nacimiento    DATE,
    direccion           VARCHAR(200),
    colegio_procedencia VARCHAR(150),
    anio_egreso         INT,
    foto_path           VARCHAR(255),                  -- foto para el carnet (disco PRIVADO)
    estado              VARCHAR(10) NOT NULL DEFAULT 'activo' CHECK (estado IN ('activo','inactivo','egresado')),
    codigo_qr           UUID NOT NULL UNIQUE DEFAULT gen_random_uuid(),   -- NUNCA el DNI en el QR
    qr_regenerado_at    TIMESTAMP,
    created_at          TIMESTAMP NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE apoderados (
    id_apoderado  SERIAL PRIMARY KEY,
    id_estudiante INT NOT NULL REFERENCES estudiantes(id_estudiante) ON DELETE CASCADE,
    nombre        VARCHAR(80) NOT NULL,
    apellido      VARCHAR(80) NOT NULL,
    parentesco    VARCHAR(40),
    telefono      VARCHAR(20),
    email         VARCHAR(120)
);

CREATE TABLE profesores (
    id_profesor SERIAL PRIMARY KEY,
    dni         VARCHAR(15) NOT NULL UNIQUE,
    nombre      VARCHAR(80) NOT NULL,
    apellido    VARCHAR(80) NOT NULL,
    email       VARCHAR(120),
    telefono    VARCHAR(20),
    estado      VARCHAR(10) NOT NULL DEFAULT 'activo' CHECK (estado IN ('activo','inactivo'))
);

CREATE TABLE profesor_cursos (
    id_profesor INT NOT NULL REFERENCES profesores(id_profesor) ON DELETE CASCADE,
    id_curso    INT NOT NULL REFERENCES cursos(id_curso) ON DELETE CASCADE,
    PRIMARY KEY (id_profesor, id_curso)
);

CREATE TABLE roles (
    id_rol      SERIAL PRIMARY KEY,
    nombre      VARCHAR(40) NOT NULL UNIQUE,   -- admin, secretaria, caja, profesor, estudiante, apoderado
    descripcion VARCHAR(200)
);

CREATE TABLE permisos (
    id_permiso  SERIAL PRIMARY KEY,
    codigo      VARCHAR(60) NOT NULL UNIQUE,   -- ej: asistencia.registrar_qr
    modulo      VARCHAR(40) NOT NULL,
    descripcion VARCHAR(200)
);

CREATE TABLE rol_permiso (
    id_rol     INT NOT NULL REFERENCES roles(id_rol) ON DELETE CASCADE,
    id_permiso INT NOT NULL REFERENCES permisos(id_permiso) ON DELETE CASCADE,
    PRIMARY KEY (id_rol, id_permiso)
);

CREATE TABLE users (
    id_usuario    SERIAL PRIMARY KEY,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    id_rol        INT NOT NULL REFERENCES roles(id_rol),
    id_profesor   INT UNIQUE REFERENCES profesores(id_profesor),
    id_estudiante INT UNIQUE REFERENCES estudiantes(id_estudiante),
    id_apoderado  INT UNIQUE REFERENCES apoderados(id_apoderado),
    ultimo_login  TIMESTAMP,
    activo        BOOLEAN NOT NULL DEFAULT TRUE,
    CHECK (num_nonnulls(id_profesor, id_estudiante, id_apoderado) <= 1)
);
-- Nota: un apoderado puede tener varios hijos; el vínculo hijo↔apoderado está en
-- apoderados.id_estudiante. Para un apoderado con varios hijos, crear una fila
-- en `apoderados` por cada hijo y resolver "mis hijos" por DNI/email del apoderado.

-- ============ 3. ESTRUCTURA ACADÉMICA ============
CREATE TABLE ciclos (
    id_ciclo     SERIAL PRIMARY KEY,
    nombre_ciclo VARCHAR(100) NOT NULL,
    tipo_ciclo   VARCHAR(10) NOT NULL CHECK (tipo_ciclo IN ('Anual','Semestral','Repaso')),
    fecha_inicio DATE NOT NULL,
    fecha_fin    DATE NOT NULL,
    modalidad    VARCHAR(12) NOT NULL CHECK (modalidad IN ('Presencial','Virtual','Híbrido')),
    estado       VARCHAR(12) NOT NULL DEFAULT 'Programado' CHECK (estado IN ('Programado','En curso','Cerrado')),
    CHECK (fecha_fin > fecha_inicio)
);

CREATE TABLE grupos (
    id_grupo  SERIAL PRIMARY KEY,
    id_ciclo  INT NOT NULL REFERENCES ciclos(id_ciclo),
    id_area   INT REFERENCES areas_academicas(id_area),
    nombre    VARCHAR(50) NOT NULL,
    turno     VARCHAR(8) NOT NULL CHECK (turno IN ('Mañana','Tarde','Noche')),
    capacidad INT CHECK (capacidad > 0),
    UNIQUE (id_ciclo, nombre)
);

CREATE TABLE horarios (
    id_horario  SERIAL PRIMARY KEY,
    id_grupo    INT NOT NULL REFERENCES grupos(id_grupo),
    id_curso    INT NOT NULL REFERENCES cursos(id_curso),
    id_profesor INT NOT NULL REFERENCES profesores(id_profesor),
    id_aula     INT NOT NULL REFERENCES aulas(id_aula),
    dia_semana  SMALLINT NOT NULL CHECK (dia_semana BETWEEN 1 AND 7),   -- 1 = lunes
    hora_inicio TIME NOT NULL,
    hora_fin    TIME NOT NULL,
    CHECK (hora_fin > hora_inicio)
);
-- Regla de negocio (validar en la aplicación): un profesor o un aula no pueden
-- tener dos clases que se traslapen el mismo día.

CREATE TABLE matriculas (
    id_matricula        SERIAL PRIMARY KEY,
    id_estudiante       INT NOT NULL REFERENCES estudiantes(id_estudiante),
    id_ciclo            INT NOT NULL REFERENCES ciclos(id_ciclo),
    id_grupo            INT REFERENCES grupos(id_grupo),
    id_beca             INT REFERENCES becas(id_beca),
    id_carrera_objetivo INT REFERENCES carreras(id_carrera),
    fecha_matricula     DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_retiro        DATE,
    estado_matricula    VARCHAR(10) NOT NULL DEFAULT 'inscrito'
                        CHECK (estado_matricula IN ('inscrito','retirado','congelado','finalizado')),
    UNIQUE (id_estudiante, id_ciclo)
);

CREATE TABLE congelamientos (
    id_congelamiento SERIAL PRIMARY KEY,
    id_matricula     INT NOT NULL REFERENCES matriculas(id_matricula),
    fecha_inicio     DATE NOT NULL,
    fecha_fin        DATE,
    motivo           VARCHAR(255),
    monto_a_favor    DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (monto_a_favor >= 0)
);

-- ============ 4. ASISTENCIA (QR + manual) ============
CREATE TABLE registros_ingreso (
    id_registro  BIGSERIAL PRIMARY KEY,
    id_matricula INT NOT NULL REFERENCES matriculas(id_matricula),
    id_usuario   INT NOT NULL REFERENCES users(id_usuario),    -- quién escaneó
    tipo         VARCHAR(8) NOT NULL DEFAULT 'ingreso' CHECK (tipo IN ('ingreso','salida')),
    metodo       VARCHAR(8) NOT NULL DEFAULT 'qr' CHECK (metodo IN ('qr','manual')),
    fecha_hora   TIMESTAMP NOT NULL DEFAULT now(),
    observacion  VARCHAR(200)
);

CREATE TABLE asistencias (
    id_asistencia       SERIAL PRIMARY KEY,
    id_matricula        INT NOT NULL REFERENCES matriculas(id_matricula),
    id_horario          INT NOT NULL REFERENCES horarios(id_horario),
    fecha               DATE NOT NULL,
    estado              VARCHAR(12) NOT NULL CHECK (estado IN ('Presente','Tarde','Falta','Justificada')),
    origen              VARCHAR(8) NOT NULL DEFAULT 'manual' CHECK (origen IN ('qr','manual')),
    id_usuario_registro INT REFERENCES users(id_usuario),
    id_registro_ingreso BIGINT REFERENCES registros_ingreso(id_registro),
    hora_registro       TIME,
    UNIQUE (id_matricula, id_horario, fecha)
);

-- ============ 5. FINANZAS ============
CREATE TABLE cuotas (                    -- lo que se DEBE (antes llamado "Pago" en el frontend)
    id_cuota          SERIAL PRIMARY KEY,
    id_matricula      INT NOT NULL REFERENCES matriculas(id_matricula),
    concepto          VARCHAR(100) NOT NULL,
    numero_cuota      INT,
    monto_original    DECIMAL(10,2) NOT NULL CHECK (monto_original >= 0),
    monto_descuento   DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (monto_descuento >= 0),
    monto_final       DECIMAL(10,2) NOT NULL CHECK (monto_final >= 0),
    fecha_vencimiento DATE NOT NULL,
    estado_pago       VARCHAR(10) NOT NULL DEFAULT 'Pendiente'
                      CHECK (estado_pago IN ('Pendiente','Parcial','Pagado','Vencido','Anulado'))
);

CREATE TABLE pagos (                     -- lo realmente COBRADO
    id_pago           SERIAL PRIMARY KEY,
    id_matricula      INT NOT NULL REFERENCES matriculas(id_matricula),
    id_usuario        INT NOT NULL REFERENCES users(id_usuario),    -- quién cobró
    fecha_pago        TIMESTAMP NOT NULL DEFAULT now(),
    monto             DECIMAL(10,2) NOT NULL CHECK (monto > 0),
    metodo_pago       VARCHAR(15) NOT NULL CHECK (metodo_pago IN ('Efectivo','Transferencia','Tarjeta','Yape/Plin')),
    tipo_comprobante  VARCHAR(10) CHECK (tipo_comprobante IN ('Boleta','Factura','Recibo')),
    nro_comprobante   VARCHAR(30) UNIQUE,
    estado            VARCHAR(8) NOT NULL DEFAULT 'valido' CHECK (estado IN ('valido','anulado')),
    motivo_anulacion  VARCHAR(200),
    anulado_por       INT REFERENCES users(id_usuario),
    anulado_en        TIMESTAMP
);

CREATE TABLE pagos_docentes (
    id_pago_docente SERIAL PRIMARY KEY,
    id_ciclo        INT REFERENCES ciclos(id_ciclo) ON DELETE RESTRICT, -- NULL: pago histórico pendiente de clasificar
    id_profesor     INT NOT NULL REFERENCES profesores(id_profesor) ON DELETE RESTRICT,
    id_usuario      INT NOT NULL REFERENCES users(id_usuario) ON DELETE RESTRICT,
    monto           DECIMAL(10,2) NOT NULL CHECK (monto > 0),
    fecha_pago      DATE NOT NULL,
    periodo         VARCHAR(7) NOT NULL, -- YYYY-MM
    metodo_pago     VARCHAR(15) NOT NULL CHECK (metodo_pago IN ('Efectivo','Transferencia','Tarjeta','Yape/Plin')),
    nro_comprobante VARCHAR(30) UNIQUE,
    estado          VARCHAR(8) NOT NULL DEFAULT 'valido' CHECK (estado IN ('valido','anulado')),
    motivo_anulacion VARCHAR(200),
    anulado_por     INT REFERENCES users(id_usuario) ON DELETE RESTRICT,
    anulado_en      TIMESTAMP,
    created_at      TIMESTAMP NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX pagos_docentes_ciclo_idx ON pagos_docentes(id_ciclo);
CREATE INDEX pagos_docentes_profesor_fecha_idx ON pagos_docentes(id_profesor, fecha_pago);
CREATE INDEX pagos_docentes_periodo_estado_idx ON pagos_docentes(periodo, estado);

CREATE TABLE pagos_cuotas (
    id_pago        INT NOT NULL REFERENCES pagos(id_pago),
    id_cuota       INT NOT NULL REFERENCES cuotas(id_cuota),
    monto_aplicado DECIMAL(10,2) NOT NULL CHECK (monto_aplicado > 0),
    PRIMARY KEY (id_pago, id_cuota)
);

-- ============ 6. SIMULACROS ============
CREATE TABLE simulacros (
    id_simulacro     SERIAL PRIMARY KEY,
    id_ciclo         INT NOT NULL REFERENCES ciclos(id_ciclo),
    titulo           VARCHAR(150) NOT NULL,
    fecha_aplicacion DATE NOT NULL,
    puntaje_maximo   DECIMAL(6,2) NOT NULL
);

CREATE TABLE resultados_simulacros (
    id_resultado     SERIAL PRIMARY KEY,
    id_simulacro     INT NOT NULL REFERENCES simulacros(id_simulacro),
    id_matricula     INT NOT NULL REFERENCES matriculas(id_matricula),
    puntaje_obtenido DECIMAL(6,2) NOT NULL,
    puesto_general   INT,
    puesto_carrera   INT,
    es_rezagado      BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_rendicion  DATE,
    estado_ranking   VARCHAR(22) NOT NULL DEFAULT 'pendiente_actualizar'
                     CHECK (estado_ranking IN ('procesado','pendiente_actualizar')),
    UNIQUE (id_simulacro, id_matricula)
);

CREATE TABLE resultados_curso (
    id_resultado_curso SERIAL PRIMARY KEY,
    id_resultado       INT NOT NULL REFERENCES resultados_simulacros(id_resultado) ON DELETE CASCADE,
    id_curso           INT NOT NULL REFERENCES cursos(id_curso),
    correctas          INT NOT NULL DEFAULT 0,
    incorrectas        INT NOT NULL DEFAULT 0,
    en_blanco          INT NOT NULL DEFAULT 0,
    puntaje            DECIMAL(6,2) NOT NULL,
    UNIQUE (id_resultado, id_curso)
);

-- ============ 7. NOTAS (evaluaciones internas) ============
CREATE TABLE evaluaciones (
    id_evaluacion SERIAL PRIMARY KEY,
    id_grupo      INT NOT NULL REFERENCES grupos(id_grupo),
    id_curso      INT NOT NULL REFERENCES cursos(id_curso),
    titulo        VARCHAR(150) NOT NULL,
    tipo          VARCHAR(10) NOT NULL DEFAULT 'Práctica' CHECK (tipo IN ('Práctica','Examen','Tarea','Otro')),
    fecha         DATE NOT NULL,
    nota_maxima   DECIMAL(5,2) NOT NULL DEFAULT 20 CHECK (nota_maxima > 0),
    peso          DECIMAL(5,2) NOT NULL DEFAULT 1  CHECK (peso > 0)
);

CREATE TABLE notas (
    id_nota       SERIAL PRIMARY KEY,
    id_evaluacion INT NOT NULL REFERENCES evaluaciones(id_evaluacion) ON DELETE CASCADE,
    id_matricula  INT NOT NULL REFERENCES matriculas(id_matricula),
    nota          DECIMAL(5,2) NOT NULL CHECK (nota >= 0),
    observacion   VARCHAR(200),
    id_usuario    INT REFERENCES users(id_usuario),
    UNIQUE (id_evaluacion, id_matricula)
);

-- ============ 8. LECTURA DE HOJAS DE RESPUESTAS (OMR) ============
CREATE TABLE plantillas_hoja (
    id_plantilla           SERIAL PRIMARY KEY,
    nombre                 VARCHAR(100) NOT NULL,
    num_preguntas          INT NOT NULL CHECK (num_preguntas > 0),
    opciones_por_pregunta  INT NOT NULL DEFAULT 5 CHECK (opciones_por_pregunta BETWEEN 2 AND 8),
    config                 JSON NOT NULL,      -- coordenadas de burbujas y marcas de alineación
    activa                 BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE claves_respuesta (
    id_clave           SERIAL PRIMARY KEY,
    id_simulacro       INT NOT NULL REFERENCES simulacros(id_simulacro) ON DELETE CASCADE,
    id_curso           INT NOT NULL REFERENCES cursos(id_curso),
    numero_pregunta    INT NOT NULL CHECK (numero_pregunta > 0),
    respuesta_correcta CHAR(1) NOT NULL,
    puntos_correcta    DECIMAL(5,2) NOT NULL DEFAULT 1,
    puntos_incorrecta  DECIMAL(5,2) NOT NULL DEFAULT 0,     -- puede ser negativo
    UNIQUE (id_simulacro, numero_pregunta)
);

-- Convocatorias: dos simulacros independientes para claves A–B y C–D.
CREATE TABLE convocatorias_becas (
    id_convocatoria BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    examen_becas_activo BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_examen DATE,
    hora_inicio_examen TIME,
    hora_fin_examen TIME,
    puntaje_minimo DECIMAL(10,2) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'Borrador' CHECK (estado IN ('Borrador','Abierta','Cerrada')),
    id_simulacro_ab INT UNIQUE REFERENCES simulacros(id_simulacro) ON DELETE SET NULL,
    id_simulacro_cd INT UNIQUE REFERENCES simulacros(id_simulacro) ON DELETE SET NULL,
    depurada_en TIMESTAMP,
    created_at TIMESTAMP, updated_at TIMESTAMP
);
CREATE INDEX idx_convocatorias_fechas ON convocatorias_becas(estado,fecha_inicio,fecha_fin);
CREATE TABLE postulantes_becas (
    id_postulante BIGSERIAL PRIMARY KEY,
    id_convocatoria BIGINT NOT NULL REFERENCES convocatorias_becas(id_convocatoria) ON DELETE RESTRICT,
    dni VARCHAR(8) NOT NULL,
    telefono VARCHAR(9) NOT NULL,
    grupo CHAR(1) NOT NULL CHECK (grupo IN ('A','B','C','D')),
    nota DECIMAL(10,2),
    estado VARCHAR(12) NOT NULL DEFAULT 'Inscrito' CHECK (estado IN ('Inscrito','Revision','Calificado','Becario')),
    created_at TIMESTAMP, updated_at TIMESTAMP,
    UNIQUE(id_convocatoria,dni)
);
CREATE INDEX idx_postulantes_dni ON postulantes_becas(dni);

CREATE TABLE hojas_respuesta (
    id_postulante BIGINT UNIQUE REFERENCES postulantes_becas(id_postulante) ON DELETE RESTRICT,
    id_hoja       SERIAL PRIMARY KEY,
    id_simulacro  INT NOT NULL REFERENCES simulacros(id_simulacro),
    id_matricula  INT REFERENCES matriculas(id_matricula),   -- NULL hasta identificar al estudiante
    id_plantilla  INT NOT NULL REFERENCES plantillas_hoja(id_plantilla),
    codigo_hoja   VARCHAR(40) NOT NULL UNIQUE,
    archivo_path  VARCHAR(255),                              -- disco PRIVADO
    estado        VARCHAR(10) NOT NULL DEFAULT 'pendiente'
                  CHECK (estado IN ('pendiente','procesada','revision','error')),
    confianza     DECIMAL(5,2),
    procesada_en  TIMESTAMP,
    id_usuario    INT REFERENCES users(id_usuario)        -- quién la subió
);

CREATE TABLE respuestas_hoja (
    id_hoja         INT NOT NULL REFERENCES hojas_respuesta(id_hoja) ON DELETE CASCADE,
    numero_pregunta INT NOT NULL CHECK (numero_pregunta > 0),
    marcada         CHAR(1),                                 -- NULL = en blanco
    estado          VARCHAR(10) NOT NULL DEFAULT 'valida'
                    CHECK (estado IN ('valida','blanco','multiple','dudosa')),
    PRIMARY KEY (id_hoja, numero_pregunta)
);

-- ============ 9. COMUNICACIÓN Y SITIO WEB ============
CREATE TABLE anuncios (
    id_anuncio   SERIAL PRIMARY KEY,
    id_ciclo     INT REFERENCES ciclos(id_ciclo),    -- NULL = para todos
    id_grupo     INT REFERENCES grupos(id_grupo),    -- NULL = todo el ciclo
    id_usuario   INT NOT NULL REFERENCES users(id_usuario),
    titulo       VARCHAR(150) NOT NULL,
    contenido    TEXT NOT NULL,
    publicado_en TIMESTAMP NOT NULL DEFAULT now(),
    vence_en     TIMESTAMP
);

CREATE TABLE materiales (
    id_material SERIAL PRIMARY KEY,
    id_curso    INT NOT NULL REFERENCES cursos(id_curso),
    id_grupo    INT REFERENCES grupos(id_grupo),     -- NULL = todos los grupos
    id_usuario  INT NOT NULL REFERENCES users(id_usuario),
    titulo      VARCHAR(150) NOT NULL,
    tipo        VARCHAR(8) NOT NULL CHECK (tipo IN ('PDF','Video','Enlace','Otro')),
    url         VARCHAR(500) NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE consultas (                -- formulario de contacto de la landing
    id_consulta SERIAL PRIMARY KEY,
    nombre      VARCHAR(120) NOT NULL,
    telefono    VARCHAR(20) NOT NULL,
    email       VARCHAR(120),
    interes     VARCHAR(150),
    mensaje     TEXT,
    estado      VARCHAR(12) NOT NULL DEFAULT 'Nueva' CHECK (estado IN ('Nueva','Contactada','Descartada')),
    created_at  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE plantillas_carnet (       -- diseños de carnet configurables
    id_plantilla_carnet SERIAL PRIMARY KEY,
    nombre              VARCHAR(80) NOT NULL UNIQUE,
    ancho_mm            DECIMAL(5,1) NOT NULL CHECK (ancho_mm BETWEEN 30 AND 220),
    alto_mm             DECIMAL(5,1) NOT NULL CHECK (alto_mm  BETWEEN 30 AND 220),
    orientacion         VARCHAR(10) NOT NULL DEFAULT 'horizontal' CHECK (orientacion IN ('horizontal','vertical')),
    tamano_qr_mm        DECIMAL(5,1) NOT NULL DEFAULT 25 CHECK (tamano_qr_mm >= 10),
    config              JSON NOT NULL,     -- colores, logo, campos visibles, posición del QR, fuentes, reverso
    es_predeterminada   BOOLEAN NOT NULL DEFAULT FALSE,
    activa              BOOLEAN NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP NOT NULL DEFAULT now()
);

-- ============ 10. SISTEMA ============
CREATE TABLE auditoria (
    id_auditoria  BIGSERIAL PRIMARY KEY,
    id_usuario    INT REFERENCES users(id_usuario),
    accion        VARCHAR(10) NOT NULL CHECK (accion IN ('crear','editar','eliminar','anular','exportar')),
    tabla         VARCHAR(60) NOT NULL,
    id_registro   VARCHAR(40) NOT NULL,
    datos_antes   JSON,
    datos_despues JSON,
    ip            VARCHAR(45),
    created_at    TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE configuraciones (          -- parámetros ajustables (ej. tolerancia de tardanza)
    clave       VARCHAR(60) PRIMARY KEY,
    valor       VARCHAR(255) NOT NULL,
    descripcion VARCHAR(200)
);
-- Claves iniciales sugeridas (seeder):
--   asistencia.tolerancia_minutos = 10
--   asistencia.bloquear_si_deuda  = 0   (0 = solo avisar, 1 = bloquear ingreso)

-- ============ ÍNDICES ============
CREATE INDEX idx_matriculas_ciclo      ON matriculas(id_ciclo);
CREATE INDEX idx_matriculas_grupo      ON matriculas(id_grupo);
CREATE INDEX idx_cuotas_matricula      ON cuotas(id_matricula);
CREATE INDEX idx_cuotas_vencimiento    ON cuotas(fecha_vencimiento, estado_pago);
CREATE INDEX idx_pagos_matricula       ON pagos(id_matricula);
CREATE INDEX idx_asistencias_fecha     ON asistencias(fecha);
CREATE INDEX idx_horarios_grupo        ON horarios(id_grupo);
CREATE INDEX idx_ingreso_matr_fecha    ON registros_ingreso(id_matricula, fecha_hora);
CREATE INDEX idx_resultados_simulacro  ON resultados_simulacros(id_simulacro);
CREATE INDEX idx_estudiantes_apellido  ON estudiantes(apellido, nombre);
CREATE INDEX idx_auditoria_tabla       ON auditoria(tabla, id_registro);
CREATE INDEX idx_hojas_simulacro       ON hojas_respuesta(id_simulacro, estado);

-- ============ VISTAS (opcionales: pueden implementarse como consultas en Laravel) ============
CREATE VIEW v_ranking_simulacro AS
SELECT r.id_simulacro, r.id_matricula, m.id_carrera_objetivo, r.puntaje_obtenido,
       RANK() OVER (PARTITION BY r.id_simulacro ORDER BY r.puntaje_obtenido DESC) AS puesto_general,
       RANK() OVER (PARTITION BY r.id_simulacro, m.id_carrera_objetivo ORDER BY r.puntaje_obtenido DESC) AS puesto_carrera
FROM resultados_simulacros r
JOIN matriculas m ON m.id_matricula = r.id_matricula;

CREATE VIEW v_asistencia_resumen AS
SELECT a.id_matricula, h.id_curso,
       COUNT(*) AS total_clases,
       SUM(CASE WHEN a.estado IN ('Presente','Tarde') THEN 1 ELSE 0 END) AS asistidas,
       SUM(CASE WHEN a.estado = 'Falta'  THEN 1 ELSE 0 END) AS faltas,
       SUM(CASE WHEN a.estado = 'Tarde'  THEN 1 ELSE 0 END) AS tardanzas,
       ROUND(100.0 * SUM(CASE WHEN a.estado IN ('Presente','Tarde') THEN 1 ELSE 0 END)
             / NULLIF(COUNT(*), 0), 1) AS porcentaje
FROM asistencias a
JOIN horarios h ON h.id_horario = a.id_horario
GROUP BY a.id_matricula, h.id_curso;
-- IMPORTANTE: las faltas deben existir como filas 'Falta' (comando de cierre diario).

CREATE VIEW v_estado_cuenta AS
SELECT t.id_matricula, t.total_cuotas, t.total_pagado, (t.total_cuotas - t.total_pagado) AS deuda
FROM (
    SELECT m.id_matricula,
           COALESCE((SELECT SUM(c.monto_final) FROM cuotas c
                      WHERE c.id_matricula = m.id_matricula AND c.estado_pago <> 'Anulado'), 0) AS total_cuotas,
           COALESCE((SELECT SUM(pc.monto_aplicado) FROM pagos_cuotas pc
                       JOIN pagos p ON p.id_pago = pc.id_pago
                      WHERE p.id_matricula = m.id_matricula AND p.estado = 'valido'), 0) AS total_pagado
    FROM matriculas m
) t;

CREATE VIEW v_promedio_notas AS
SELECT n.id_matricula, e.id_curso,
       ROUND(SUM((n.nota / e.nota_maxima * 20) * e.peso) / SUM(e.peso), 2) AS promedio_sobre_20,
       COUNT(*) AS evaluaciones
FROM notas n
JOIN evaluaciones e ON e.id_evaluacion = n.id_evaluacion
GROUP BY n.id_matricula, e.id_curso;
