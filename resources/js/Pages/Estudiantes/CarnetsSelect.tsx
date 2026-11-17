import Form from '@/Components/InertiaForm';
import { Printer, QrCode } from 'lucide-react';
import EmptyState from '@/Components/EmptyState';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useState } from 'react';

type Grupo = { id_grupo: number; nombre: string; matriculas_count: number; ciclo?: { nombre_ciclo: string } };

export default function CarnetsSelect({ grupos }: { grupos: Grupo[] }) {
    const [grupoId, setGrupoId] = useState(grupos[0]?.id_grupo.toString() ?? '');

    return (
        <PageShell title="Carnets y códigos QR" description="Imprime los carnets del grupo o prepara una hoja con todos los QR para asistencia.">
            {grupos.length === 0 ? <EmptyState title="No hay grupos" description="Crea un grupo y registra matrículas para poder imprimir carnets." /> : (
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-end">
                        <div><label htmlFor="id_grupo" className="text-sm font-medium text-slate-700 dark:text-slate-200">Grupo</label><select id="id_grupo" name="id_grupo" value={grupoId} onChange={(event) => setGrupoId(event.target.value)} required className="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950">{grupos.map((grupo) => <option key={grupo.id_grupo} value={grupo.id_grupo}>{grupo.nombre}{grupo.ciclo ? ` · ${grupo.ciclo.nombre_ciclo}` : ''} ({grupo.matriculas_count} matrículas)</option>)}</select></div>
                        <Form action={route('estudiantes.carnets')} method="get"><input type="hidden" name="id_grupo" value={grupoId} /><PrimaryButton disabled={!grupoId}><Printer className="mr-2 size-4" />Preparar impresión</PrimaryButton></Form>
                        <Form action={route('estudiantes.carnets.qr')} method="get"><input type="hidden" name="id_grupo" value={grupoId} /><SecondaryButton disabled={!grupoId}><QrCode className="mr-2 size-4" />Preparar QR del grupo</SecondaryButton></Form>
                    </div>
                    <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">La hoja se genera en el navegador. Desde Imprimir puedes guardarla como PDF.</p>
                </section>
            )}
        </PageShell>
    );
}
