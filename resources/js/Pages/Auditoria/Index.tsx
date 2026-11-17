import Form from '@/Components/InertiaForm';
import { History, Search } from 'lucide-react';
import EmptyState from '@/Components/EmptyState';
import PageShell from '@/Components/PageShell';
import Pagination from '@/Components/Pagination';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Paginated } from '@/types/academia';

type AuditEntry = {
    id_auditoria: number; id_usuario: number | null; accion: string; tabla: string;
    id_registro: string; datos_antes: Record<string, unknown> | null;
    datos_despues: Record<string, unknown> | null; ip: string | null; created_at: string;
    user?: { name: string; email: string } | null;
};
type Props = { registros: Paginated<AuditEntry>; filters: { tabla?: string; accion?: string }; tablas: string[] };

export default function AuditoriaIndex({ registros, filters, tablas }: Props) {
    return (
        <PageShell title="Auditoría" description="Consulta los cambios realizados en matrículas, pagos, notas, asistencia y usuarios.">
            <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
                <Form action={route('auditoria.index')} method="get" className="mb-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_14rem_auto]">
                    <div className="relative"><Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" /><TextInput name="tabla" list="auditoria-tablas" defaultValue={filters.tabla ?? ''} placeholder="Tabla (ej. pagos)" className="w-full pl-9" /><datalist id="auditoria-tablas">{tablas.map((table) => <option key={table} value={table} />)}</datalist></div>
                    <select aria-label="Filtrar por acción" name="accion" defaultValue={filters.accion ?? ''} className="rounded-md border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950"><option value="">Todas las acciones</option><option value="crear">Crear</option><option value="editar">Editar</option><option value="eliminar">Eliminar</option><option value="anular">Anular</option></select>
                    <SecondaryButton type="submit">Filtrar</SecondaryButton>
                </Form>

                {registros.data.length === 0 ? <EmptyState title="Sin movimientos" description="Cuando se registren cambios administrativos, aparecerán aquí." /> : <>
                    <div className="space-y-3">
                        {registros.data.map((entry) => <details key={entry.id_auditoria} className="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                            <summary className="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3">
                                <div className="flex min-w-0 items-center gap-3"><span className="grid size-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"><History className="size-5" /></span><div><p className="font-semibold capitalize text-slate-900 dark:text-white">{entry.accion} · {entry.tabla} #{entry.id_registro}</p><p className="text-sm text-slate-500">{entry.user?.name ?? 'Sistema'} · {entry.created_at}</p></div></div>
                                <span className="text-xs text-slate-500">{entry.ip ?? 'IP no disponible'}</span>
                            </summary>
                            <div className="mt-4 grid gap-3 lg:grid-cols-2">
                                <JsonBlock title="Antes" value={entry.datos_antes} />
                                <JsonBlock title="Después" value={entry.datos_despues} />
                            </div>
                        </details>)}
                    </div>
                    <div className="mt-5"><Pagination links={registros.meta.links} /></div>
                </>}
            </section>
        </PageShell>
    );
}

function JsonBlock({ title, value }: { title: string; value: Record<string, unknown> | null }) {
    return <div><h2 className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</h2><pre className="max-h-72 overflow-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700 dark:bg-slate-950 dark:text-slate-300">{JSON.stringify(value ?? {}, null, 2)}</pre></div>;
}
