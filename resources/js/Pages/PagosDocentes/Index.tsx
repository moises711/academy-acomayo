import { FormEvent } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { Banknote } from 'lucide-react';
import EmptyState from '@/Components/EmptyState';
import FormModal from '@/Components/FormModal';
import InputError from '@/Components/InputError';
import PageShell from '@/Components/PageShell';
import Pagination from '@/Components/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatusBadge from '@/Components/StatusBadge';
import Form from '@/Components/InertiaForm';
import { formatDate, formatMoney } from '@/lib/format';
import { Paginated } from '@/types/academia';
import { useState } from 'react';

type Teacher = { id_profesor: number; nombre: string; apellido: string; dni: string };
type TeacherPayment = {
    id_pago_docente: number;
    id_ciclo: number | null;
    ciclo: { id_ciclo: number; nombre_ciclo: string } | null;
    id_profesor: number;
    monto: string;
    fecha_pago: string;
    periodo: string;
    metodo_pago: string;
    nro_comprobante: string | null;
    estado: 'valido' | 'anulado';
    motivo_anulacion: string | null;
    profesor: Teacher;
    usuario: { id_usuario: number; name: string };
};
type Props = {
    docentes: Teacher[];
    ciclos: { id_ciclo: number; nombre_ciclo: string }[];
    profesorSeleccionado: { id_profesor: number; nombre: string } | null;
    pagos: Paginated<TeacherPayment>;
    filters: { id_profesor?: number };
    fechaHoy: string;
};

export default function PagosDocentesIndex({ docentes, ciclos, profesorSeleccionado, pagos, filters, fechaHoy }: Props) {
    const [anular, setAnular] = useState<TeacherPayment | null>(null);
    const create = useForm({
        id_ciclo: '',
        id_profesor: String(profesorSeleccionado?.id_profesor ?? ''),
        monto: '',
        fecha_pago: fechaHoy,
        periodo: fechaHoy.slice(0, 7),
        metodo_pago: 'Transferencia',
        nro_comprobante: '',
    });
    const annul = useForm({ motivo_anulacion: '' });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        create.post(route('pagos-docentes.store'), {
            preserveScroll: true,
            onSuccess: () => create.reset('monto', 'nro_comprobante'),
        });
    };

    const submitAnnulment = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!anular) return;
        annul.post(route('pagos-docentes.anular', anular.id_pago_docente), {
            preserveScroll: true,
            onSuccess: () => { annul.reset(); setAnular(null); },
        });
    };

    return (
        <PageShell title="Pagos a docentes" description="Registra honorarios por período y conserva comprobantes y anulaciones."
            actions={profesorSeleccionado ? <Link href={route('docentes.index')} className="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300">Volver a docentes</Link> : undefined}>
            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.75fr)]">
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="flex flex-col gap-4 border-b border-slate-100 p-5 dark:border-slate-800 sm:flex-row sm:items-end sm:justify-between">
                        <div><h2 className="font-semibold text-slate-900 dark:text-white">Historial de pagos</h2><p className="text-sm text-slate-500">{profesorSeleccionado ? `Pagos de ${profesorSeleccionado.nombre}` : 'Pagos registrados para el equipo docente'}</p></div>
                        <Form action={route('pagos-docentes.index')} method="get" className="flex flex-wrap items-end gap-2">
                            <label className="grid gap-1 text-xs font-medium text-slate-600 dark:text-slate-300">Docente<select name="id_profesor" defaultValue={filters.id_profesor ?? ''} className="rounded-lg border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="">Todos</option>{docentes.map((teacher) => <option key={teacher.id_profesor} value={teacher.id_profesor}>{teacher.nombre} {teacher.apellido} · {teacher.dni}</option>)}</select></label>
                            <button className="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white">Filtrar</button>
                        </Form>
                    </div>
                    {pagos.data.length === 0 ? <div className="p-5"><EmptyState title="Sin pagos registrados" description="Los pagos a docentes aparecerán aquí después de registrarlos." /></div> : <>
                        <div className="overflow-x-auto"><table className="w-full min-w-[780px] text-left text-sm">
                            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950"><tr><th className="px-4 py-3">Docente</th><th className="px-4 py-3">Período</th><th className="px-4 py-3">Fecha / medio</th><th className="px-4 py-3">Comprobante</th><th className="px-4 py-3">Monto / estado</th><th className="px-4 py-3 text-right">Acciones</th></tr></thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">{pagos.data.map((payment) => <tr key={payment.id_pago_docente}>
                                <td className="px-4 py-4"><p className="font-medium text-slate-900 dark:text-white">{payment.profesor.nombre} {payment.profesor.apellido}</p><p className="text-xs text-slate-500">DNI {payment.profesor.dni}</p></td>
                                <td className="px-4 py-4">{payment.periodo}<p className="mt-1 text-xs text-slate-500">{payment.ciclo?.nombre_ciclo ?? 'Sin ciclo asignado'}</p></td>
                                <td className="px-4 py-4">{formatDate(payment.fecha_pago)}<p className="text-xs text-slate-500">{payment.metodo_pago}</p></td>
                                <td className="px-4 py-4">{payment.nro_comprobante ?? '—'}</td>
                                <td className="px-4 py-4"><p className="font-semibold tabular-nums">{formatMoney(payment.monto)}</p><StatusBadge value={payment.estado} />{payment.motivo_anulacion && <p className="mt-1 max-w-44 text-xs text-red-700">{payment.motivo_anulacion}</p>}</td>
                                <td className="px-4 py-4 text-right">{payment.estado === 'valido' && <button type="button" onClick={() => setAnular(payment)} className="text-sm font-semibold text-red-700 hover:underline dark:text-red-300">Anular</button>}</td>
                            </tr>)}</tbody>
                        </table></div>
                        <div className="p-4"><Pagination links={pagos.meta.links} /></div>
                    </>}
                </section>

                <section className="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div className="mb-5 flex items-center gap-3"><div className="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><Banknote className="size-5" /></div><div><h2 className="font-semibold text-slate-900 dark:text-white">Registrar pago</h2><p className="text-sm text-slate-500">Se guardará quién lo registró y quedará auditado.</p></div></div>
                    <form onSubmit={submit} className="grid gap-4">
                        <Field label="Ciclo académico" error={create.errors.id_ciclo}><select required value={create.data.id_ciclo} onChange={(event) => create.setData('id_ciclo', event.target.value)}><option value="">Selecciona un ciclo</option>{ciclos.map((cycle) => <option key={cycle.id_ciclo} value={cycle.id_ciclo}>{cycle.nombre_ciclo}</option>)}</select></Field>
                        <Field label="Docente" error={create.errors.id_profesor}><select required value={create.data.id_profesor} onChange={(event) => create.setData('id_profesor', event.target.value)} className="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-950"><option value="">Selecciona un docente</option>{docentes.map((teacher) => <option key={teacher.id_profesor} value={teacher.id_profesor}>{teacher.nombre} {teacher.apellido} · {teacher.dni}</option>)}</select></Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Monto (S/)" error={create.errors.monto}><input type="number" min="0.01" max="99999999.99" step="0.01" required value={create.data.monto} onChange={(event) => create.setData('monto', event.target.value)} className="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-950" /></Field>
                            <Field label="Período" error={create.errors.periodo}><input type="month" required value={create.data.periodo} onChange={(event) => create.setData('periodo', event.target.value)} className="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-950" /></Field>
                            <Field label="Fecha de pago" error={create.errors.fecha_pago}><input type="date" max={fechaHoy} required value={create.data.fecha_pago} onChange={(event) => create.setData('fecha_pago', event.target.value)} className="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-950" /></Field>
                            <Field label="Método" error={create.errors.metodo_pago}><select value={create.data.metodo_pago} onChange={(event) => create.setData('metodo_pago', event.target.value)} className="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-950">{['Efectivo', 'Transferencia', 'Tarjeta', 'Yape/Plin'].map((method) => <option key={method}>{method}</option>)}</select></Field>
                        </div>
                        <Field label="Número de comprobante (opcional)" error={create.errors.nro_comprobante}><input maxLength={30} value={create.data.nro_comprobante} onChange={(event) => create.setData('nro_comprobante', event.target.value)} className="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-950" /></Field>
                        <PrimaryButton disabled={create.processing || docentes.length === 0 || ciclos.length === 0} className="justify-center">{create.processing ? 'Guardando…' : 'Registrar pago'}</PrimaryButton>
                    </form>
                    {docentes.length === 0 && <p className="mt-3 text-sm text-slate-500">Registra un docente antes de crear un pago.</p>}
                </section>
            </div>

            <FormModal show={Boolean(anular)} title="Anular pago a docente" onClose={() => { setAnular(null); annul.reset(); }}>
                <form onSubmit={submitAnnulment} className="grid gap-4">
                    <p className="text-sm text-slate-600 dark:text-slate-300">Se conservará el registro original y se excluirá de los pagos válidos.</p>
                    <label className="grid gap-1.5 text-sm font-medium text-slate-700 dark:text-slate-200">Motivo<input required maxLength={200} value={annul.data.motivo_anulacion} onChange={(event) => annul.setData('motivo_anulacion', event.target.value)} className="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-950" /><InputError message={annul.errors.motivo_anulacion} /></label>
                    <div className="flex justify-end gap-2"><SecondaryButton type="button" onClick={() => setAnular(null)}>Cancelar</SecondaryButton><PrimaryButton disabled={annul.processing} className="bg-red-700 hover:bg-red-800">{annul.processing ? 'Anulando…' : 'Confirmar anulación'}</PrimaryButton></div>
                </form>
            </FormModal>
        </PageShell>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <label className="grid min-w-0 gap-1.5 text-sm font-medium text-slate-700 dark:text-slate-200">{label}{children}<InputError message={error} /></label>;
}
