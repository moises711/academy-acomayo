// =====================================================
// TIPOS ACTUALIZADOS - Modelo completo de la academia
// Convención: los decimales de Laravel llegan como string | number.
// Los valores de enum conservan tu capitalización actual.
// Los campos marcados "NUEVO" / "CAMBIO" indican qué cambió.
// =====================================================

export type Decimal = string | number;

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
};

// ---------- CATÁLOGOS (NUEVOS) ----------
export type Universidad = {
    id_universidad: number;
    nombre: string;
    siglas: string | null;
    tipo: 'Pública' | 'Privada' | null;
    carreras_count?: number;
    carreras?: Carrera[];
};

export type AreaAcademica = {
    id_area: number;
    nombre: string;
};

export type Carrera = {
    id_carrera: number;
    id_universidad: number;
    id_area: number;
    nombre: string;
    puntaje_minimo: Decimal | null;
    universidad?: Universidad;
    area?: AreaAcademica;
};

export type Beca = {
    id_beca: number;
    nombre: string;
    tipo: 'Beca' | 'Media Beca' | 'Convenio' | 'Otro';
    porcentaje_descuento: Decimal;
    activa: boolean;
    matriculas_count?: number;
};

export type Aula = {
    id_aula: number;
    nombre: string;
    tipo: 'Presencial' | 'Virtual';
    capacidad: number | null;
    enlace_virtual: string | null;
};

export type Curso = {
    id_curso: number;
    nombre_curso: string;
    categoria: 'Aptitud' | 'Ciencias' | 'Letras';
    horarios_count?: number;
    profesores?: Profesor[]; // NUEVO (profesor_cursos)
};

// ---------- PERSONAS ----------
export type Estudiante = {
    id_estudiante: number;
    dni: string;                          // NUEVO
    nombre: string;
    apellido: string;
    email: string | null;
    telefono: string | null;
    fecha_nacimiento: string | null;
    direccion: string | null;             // NUEVO
    colegio_procedencia: string | null;   // NUEVO
    anio_egreso: number | null;           // NUEVO
    estado: 'activo' | 'inactivo' | 'egresado';
    fotoUrl?: string | null;
    codigo_qr?: string;
    // La beca pertenece a la matrícula y el QR nunca se expone en listados.
    matriculas_count?: number;
    apoderados?: Apoderado[];             // NUEVO
    matriculas?: Matricula[];
    user?: Usuario | null;
    resultados_simulacros?: ResultadoSimulacro[];
};

export type Apoderado = {                 // NUEVO
    id_apoderado: number;
    id_estudiante: number;
    nombre: string;
    apellido: string;
    parentesco: string | null;
    telefono: string | null;
    email: string | null;
};

export type Profesor = {                  // NUEVO (antes no tenías tipo)
    id_profesor: number;
    dni: string;
    nombre: string;
    apellido: string;
    email: string | null;
    telefono: string | null;
    estado: 'activo' | 'inactivo';
    cursos?: Curso[];
    horarios_count?: number;
    pagos?: PagoDocente[];
};

export type Usuario = {                   // NUEVO
    id_usuario: number;
    email: string;
    rol: 'admin' | 'secretaria' | 'caja' | 'profesor' | 'estudiante' | 'apoderado';
    id_profesor: number | null;
    activo: boolean;
};

export type Consulta = {
    id_consulta: number;
    nombre: string;
    telefono: string;
    email: string | null;
    interes: string | null;
    mensaje: string | null;
    estado: 'Nueva' | 'Contactada' | 'Descartada';
    created_at: string;
};

// ---------- ESTRUCTURA ACADÉMICA ----------
export type Ciclo = {
    id_ciclo: number;
    nombre_ciclo: string;
    tipo_ciclo: 'Anual' | 'Semestral' | 'Repaso';   // NUEVO
    fecha_inicio: string;
    fecha_fin: string;
    // CAMBIO: se elimina `duracion` (se calcula con las fechas)
    modalidad: 'Presencial' | 'Virtual' | 'Híbrido';
    estado: 'Programado' | 'En curso' | 'Cerrado';  // NUEVO
    matriculas_count?: number;
    simulacros_count?: number;
    horarios_count?: number;
    grupos_count?: number;
};

export type Grupo = {                     // NUEVO
    id_grupo: number;
    id_ciclo: number;
    id_area: number | null;
    nombre: string;
    turno: 'Mañana' | 'Tarde' | 'Noche';
    capacidad: number | null;
    matriculas_count?: number;
    ciclo?: Ciclo;
    area?: AreaAcademica;   
};

export type Horario = {
    id_horario: number;
    id_grupo: number;                     // NUEVO (reemplaza a id_ciclo)
    id_curso: number;
    id_profesor: number;                  // NUEVO
    id_aula: number;                      // CAMBIO: antes `aula: string`
    dia_semana: number;                   // CAMBIO: 1 = lunes ... 7 = domingo
    hora_inicio: string;
    hora_fin: string;
    grupo?: Grupo;
    curso?: Curso;
    profesor?: Profesor;
    aula?: Aula;
};

// ---------- MATRÍCULA ----------
export type Matricula = {
    id_matricula: number;
    id_estudiante: number;
    id_ciclo: number;
    id_grupo: number | null;
    id_beca: number | null;
    id_carrera_objetivo: number | null;
    fecha_matricula: string;
    fecha_retiro: string | null;
    estado_matricula: 'inscrito' | 'retirado' | 'congelado' | 'finalizado';
    estudiante?: Estudiante;
    ciclo?: Ciclo;
    grupo?: Grupo;
    beca?: Beca;
    carrera?: Carrera;
    cuotas?: Cuota[];
    pagos?: Pago[];
    congelamientos?: Congelamiento[];
};

export type Congelamiento = {             // NUEVO
    id_congelamiento: number;
    id_matricula: number;
    fecha_inicio: string;
    fecha_fin: string | null;
    motivo: string | null;
    monto_a_favor: Decimal;
};

export type Asistencia = {                // NUEVO
    id_asistencia: number;
    id_matricula: number;
    id_horario: number;
    fecha: string;
    estado: 'Presente' | 'Tarde' | 'Falta' | 'Justificada';
    origen: 'qr' | 'manual';
    matricula?: Matricula;
    horario?: Horario;
};

export type Nota = {
    id_nota: number;
    id_evaluacion: number;
    id_matricula: number;
    nota: Decimal;
    observacion: string | null;
    evaluacion?: {
        titulo: string;
        fecha: string;
        nota_maxima: Decimal;
        curso?: Curso;
    };
};

// ---------- FINANZAS ----------
// CAMBIO IMPORTANTE: tu antiguo `Pago` pasa a llamarse `Cuota` (lo que se debe).
// El nuevo `Pago` es el dinero realmente cobrado.
export type Cuota = {
    id_cuota: number;
    id_matricula: number;                 // CAMBIO: antes id_estudiante + id_ciclo
    concepto: string;
    numero_cuota: number | null;
    monto_original: Decimal;
    monto_descuento: Decimal;
    monto_final: Decimal;
    fecha_vencimiento: string;
    estado_pago: 'Pendiente' | 'Parcial' | 'Pagado' | 'Vencido' | 'Anulado';
    monto_pagado?: Decimal;
    monto_pendiente?: Decimal;
    matricula?: Matricula;
    pagos?: Pago[];
};

export type Pago = {
    id_pago: number;
    id_matricula: number;
    id_usuario: number;                   // quién cobró
    fecha_pago: string;
    monto: Decimal;
    metodo_pago: 'Efectivo' | 'Transferencia' | 'Tarjeta' | 'Yape/Plin';
    tipo_comprobante: 'Boleta' | 'Factura' | 'Recibo' | null;
    nro_comprobante: string | null;
    estado: 'valido' | 'anulado';
    motivo_anulacion?: string | null;
    anulado_por?: number | null;
    anulado_en?: string | null;
    matricula?: Matricula;
    usuario?: Usuario;
    anulacion?: string | null;
    cuotas?: (Cuota & { pivot: { monto_aplicado: Decimal } })[];
};

export type PagoDocente = {
    id_pago_docente: number;
    id_ciclo: number | null;
    ciclo?: Ciclo | null;
    id_profesor: number;
    id_usuario: number;
    monto: Decimal;
    fecha_pago: string;
    periodo: string;
    metodo_pago: 'Efectivo' | 'Transferencia' | 'Tarjeta' | 'Yape/Plin';
    nro_comprobante: string | null;
    estado: 'valido' | 'anulado';
    motivo_anulacion: string | null;
    anulado_por: number | null;
    anulado_en: string | null;
    profesor?: Profesor;
    usuario?: Usuario;
};

// ---------- SIMULACROS ----------
export type Simulacro = {
    id_simulacro: number;
    titulo: string;
    fecha_aplicacion: string;
    id_ciclo: number;
    puntaje_maximo: Decimal;
    resultados_count?: number;
    ciclo?: Ciclo;
};

export type ResultadoSimulacro = {
    id_resultado: number;
    id_simulacro: number;
    id_matricula: number;                 // CAMBIO: antes id_estudiante
    puntaje_obtenido: Decimal;
    puesto_general: number | null;
    puesto_carrera: number | null;
    es_rezagado: boolean;
    fecha_rendicion: string | null;       // NUEVO
    estado_ranking: 'procesado' | 'pendiente_actualizar';
    simulacro?: Simulacro;
    matricula?: Matricula;
    resultados_curso?: ResultadoCurso[];  // NUEVO
};

export type ResultadoCurso = {            // NUEVO
    id_resultado_curso: number;
    id_resultado: number;
    id_curso: number;
    correctas: number;
    incorrectas: number;
    en_blanco: number;
    puntaje: Decimal;
    curso?: Curso;
};
