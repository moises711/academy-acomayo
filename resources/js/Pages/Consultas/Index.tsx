import { router } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { MessageCircle, RotateCw } from 'lucide-react';
import EmptyState from '@/Components/EmptyState';
import PageShell from '@/Components/PageShell';
import Pagination from '@/Components/Pagination';
import StatCard from '@/Components/StatCard';
import { Consulta, Paginated } from '@/types/academia';
import { formatDate } from '@/lib/format';

type Props = {
    consultas: Paginated<Consulta>;
    stats: { nuevas: number; contactadas: number; descartadas: number };
};

export default function ConsultasIndex({ consultas, stats }: Props) {
    return (
        <PageShell title="Consultas del sitio web" description="Da seguimiento a las personas interesadas en los programas de la academia.">
            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard title="Nuevas" value={stats.nuevas} icon={MessageCircle} />
                <StatCard title="Contactadas" value={stats.contactadas} icon={MessageCircle} />
                <StatCard title="Descartadas" value={stats.descartadas} icon={MessageCircle} />
            </div>
            <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                {consultas.data.length === 0 ? (
                    <EmptyState title="Aún no hay consultas" description="Los mensajes enviados desde la página pública aparecerán aquí." />
                ) : (
                    <div className="space-y-3">
                        {consultas.data.map((consulta) => (
                            <article key={consulta.id_consulta} className="grid gap-4 rounded-xl border border-slate-200 p-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-semibold text-slate-900">{consulta.nombre}</h2>
                                        <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${consulta.estado === 'Nueva' ? 'bg-amber-50 text-amber-800' : consulta.estado === 'Contactada' ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600'}`}>{consulta.estado}</span>
                                    </div>
                                    <p className="mt-1 text-sm text-slate-600">{consulta.telefono}{consulta.email ? ` · ${consulta.email}` : ''}</p>
                                    <p className="mt-1 text-sm text-slate-500">{consulta.interes ?? 'Sin programa indicado'} · {formatDate(consulta.created_at)}</p>
                                    {consulta.mensaje && <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{consulta.mensaje}</p>}
                                </div>
                                <Form action={route('consultas.update', consulta.id_consulta)} method="put" className="flex flex-col gap-2 sm:flex-row lg:flex-col">
                                    {({ processing }) => <>
                                        <label className="sr-only" htmlFor={`estado-consulta-${consulta.id_consulta}`}>Estado de la consulta</label>
                                        <select id={`estado-consulta-${consulta.id_consulta}`} name="estado" defaultValue={consulta.estado} className="min-h-10 rounded-lg border-slate-200 text-sm focus:border-blue-600 focus:ring-blue-600">
                                            <option value="Nueva">Nueva</option>
                                            <option value="Contactada">Contactada</option>
                                            <option value="Descartada">Descartada</option>
                                        </select>
                                        <button type="submit" disabled={processing} className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-blue-700 px-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:opacity-50">
                                            {processing ? <RotateCw className="size-4 animate-spin" /> : null}Guardar
                                        </button>
                                    </>}
                                </Form>
                            </article>
                        ))}
                    </div>
                )}
                <div className="mt-5"><Pagination links={consultas.meta.links} /></div>
                <button type="button" onClick={() => router.reload({ only: ['consultas', 'stats'] })} className="mt-3 inline-flex items-center gap-2 text-sm font-medium text-blue-700 hover:underline"><RotateCw className="size-4" />Actualizar lista</button>
            </section>
        </PageShell>
    );
}
