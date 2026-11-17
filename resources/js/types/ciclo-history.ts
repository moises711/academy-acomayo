import type { Ciclo, PagoDocente, Paginated } from '@/types/academia';

export type HistoryCycle = Pick<Ciclo, 'id_ciclo' | 'nombre_ciclo' | 'fecha_inicio' | 'fecha_fin' | 'estado'>;
export type CicloHistoryProps = {
    ciclos: HistoryCycle[];
    seleccionado: HistoryCycle | null;
    resumen: Record<string, number> | null;
    errorResumen: string | null;
    puedeEliminar: boolean;
    pagosSinCiclo: Paginated<PagoDocente>;
    totalSinCiclo: number;
    filters: { id_ciclo?: number; buscar?: string };
};
