import type { Paginated } from './academia';

export type GrupoBeca = 'A' | 'B' | 'C' | 'D';
export type ConvocatoriaBeca = {
    id_convocatoria: number; nombre: string; descripcion: string | null;
    fecha_inicio: string; fecha_fin: string; examen_becas_activo: boolean; fecha_examen: string | null;
    hora_inicio_examen: string | null; hora_fin_examen: string | null; puntaje_minimo: string;
    estado: 'Borrador' | 'Abierta' | 'Cerrada'; id_simulacro_ab: number | null; id_simulacro_cd: number | null;
    aviso_examen_visible?: boolean; depurada_en: string | null; postulantes_count?: number;
};
export type PostulanteBeca = {
    id_postulante: number; id_convocatoria: number; dni: string; telefono: string; grupo: GrupoBeca;
    nota: string | null; estado: 'Inscrito' | 'Revision' | 'Calificado' | 'Becario';
};
export type ConvocatoriaPublica = Pick<ConvocatoriaBeca, 'id_convocatoria' | 'nombre' | 'descripcion' | 'fecha_fin'>;
export type BecasProps = {
    convocatorias: ConvocatoriaBeca[]; seleccionada: ConvocatoriaBeca | null;
    simulacros: { id_simulacro: number; titulo: string; ciclo?: { nombre_ciclo: string } }[];
    postulantes: Paginated<PostulanteBeca>;
    filters: { id_convocatoria?: number; buscar?: string; grupo?: GrupoBeca; orden?: string }; hoy: string;
};
