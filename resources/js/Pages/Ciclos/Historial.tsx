import { Link, router, useForm } from '@inertiajs/react';
import { Archive, CheckCircle2, Download, LoaderCircle, RefreshCw, Trash2 } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import EmptyState from '@/Components/EmptyState';
import FormModal from '@/Components/FormModal';
import InputError from '@/Components/InputError';
import Pagination from '@/Components/Pagination';
import { Skeleton } from '@/Components/ui/skeleton';
import { formatDate, formatMoney } from '@/lib/format';
import type { CicloHistoryProps, HistoryCycle } from '@/types/ciclo-history';
import type { PagoDocente } from '@/types/academia';

const labels: Record<string, string> = {
    ciclos: 'Ciclo', grupos: 'Grupos', matriculas: 'Matrículas', horarios: 'Horarios',
    pagos: 'Pagos de estudiantes', cuotas: 'Cuotas', pagos_cuotas: 'Aplicaciones de pagos',
    pagos_docentes: 'Pagos a docentes', asistencias: 'Asistencias', registros_ingreso: 'Ingresos',
    congelamientos: 'Congelamientos', evaluaciones: 'Evaluaciones', notas: 'Notas',
    simulacros: 'Simulacros', resultados_simulacros: 'Resultados de simulacros', resultados_curso: 'Resultados por curso',
    hojas_respuesta: 'Hojas de respuestas', respuestas_hoja: 'Respuestas de hojas', claves_respuesta: 'Claves de respuesta',
    plantillas_hoja: 'Plantillas del simulacro', anuncios: 'Anuncios del ciclo', materiales: 'Materiales del grupo',
};
const card = 'rounded-2xl border border-[hsl(var(--border))] bg-[hsl(var(--surface))] p-4 shadow-sm sm:p-6';

export default function Historial({ ciclos, seleccionado, resumen, errorResumen, puedeEliminar, pagosSinCiclo, totalSinCiclo, filters }: CicloHistoryProps) {
    const [loading, setLoading] = useState(false);
    const [downloading, setDownloading] = useState(false);
    const [downloadError, setDownloadError] = useState('');
    const [downloadToken, setDownloadToken] = useState('');
    const [confirmation, setConfirmation] = useState(false);
    const [search, setSearch] = useState(filters.buscar ?? '');
    const remove = useForm({ nombre_ciclo: '', respaldo_verificado: false, token_respaldo: '' });
    const ready = puedeEliminar && totalSinCiclo === 0 && resumen !== null && !errorResumen;

    useEffect(() => {
        setDownloadToken('');
        setDownloadError('');
        setConfirmation(false);
        remove.reset();
    }, [seleccionado?.id_ciclo, totalSinCiclo]);

    const visit = (id: string, buscar = search) => router.get(route('historial-ciclos.index'), { id_ciclo: id || undefined, buscar: buscar || undefined }, {
        preserveScroll: true,
        onStart: () => setLoading(true),
        onFinish: () => setLoading(false),
    });

    const download = async () => {
        if (!seleccionado) return;
        setDownloading(true);
        setDownloadToken('');
        setDownloadError('');
        try {
            const response = await window.axios.post(route('historial-ciclos.export', seleccionado.id_ciclo), {}, { responseType: 'blob', headers: { Accept: 'application/json' } });
            const token = String(response.headers['x-respaldo-token'] ?? '');
            if (!token || response.data.type !== 'application/zip') throw new Error('No se pudo generar el archivo de respaldo.');
            const objectUrl = URL.createObjectURL(response.data);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = `ciclo-${seleccionado.id_ciclo}.zip`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            // Dar tiempo al navegador para iniciar la descarga antes de liberar el archivo.
            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
            setDownloadToken(token);
        } catch (error: unknown) {
            const failure = error as { response?: { data?: Blob }; message?: string };
            let message = 'No se pudo descargar. Revisa la conexión y vuelve a intentarlo.';
            if (failure.response?.data instanceof Blob) {
                try {
                    const body = JSON.parse(await failure.response.data.text()) as { errors?: Record<string, string[]>; message?: string };
                    message = Object.values(body.errors ?? {}).flat()[0] ?? body.message ?? message;
                } catch { /* La respuesta puede ser una página de error del servidor. */ }
            }
            setDownloadError(message);
        } finally {
            setDownloading(false);
        }
    };

    const destroy = (event: FormEvent) => {
        event.preventDefault();
        if (!seleccionado || !downloadToken) return;
        remove.transform((data) => ({ ...data, token_respaldo: downloadToken }));
        remove.delete(route('historial-ciclos.destroy', seleccionado.id_ciclo), {
            onSuccess: () => { setConfirmation(false); setDownloadToken(''); },
        });
    };

    return <PageShell title="Historial de ciclos" description="Descarga los registros de un ciclo pasado y elimina su historial después de revisar el respaldo.">
        <section className={card}>
            <label htmlFor="history-cycle" className="block text-sm font-semibold">Selecciona un ciclo</label>
            <select id="history-cycle" value={seleccionado?.id_ciclo ?? ''} onChange={(event) => visit(event.target.value)} disabled={loading || downloading || remove.processing} className="mt-2 w-full">
                <option value="">Selecciona el ciclo que deseas revisar</option>
                {ciclos.map((cycle) => <option key={cycle.id_ciclo} value={cycle.id_ciclo}>{cycle.nombre_ciclo} · {cycle.estado}</option>)}
            </select>
            <p className="mt-3 text-sm text-[hsl(var(--muted))]">Las fichas de estudiantes, docentes y usuarios, los catálogos y la auditoría se conservan. Los registros de otros ciclos no se eliminan.</p>
        </section>

        {loading ? <section className={card} aria-busy="true" aria-label="Cargando historial"><Skeleton className="h-6 w-1/2" /><div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{Array.from({ length: 6 }, (_, index) => <Skeleton key={index} className="h-20 w-full" />)}</div></section>
            : errorResumen ? <section className={card} role="alert"><h2 className="font-semibold">No se pudo preparar el historial</h2><p className="my-3 text-sm text-[hsl(var(--muted))]">{errorResumen}</p><SecondaryButton onClick={() => visit(String(seleccionado?.id_ciclo ?? ''))}><RefreshCw className="size-4" />Reintentar</SecondaryButton></section>
            : !seleccionado ? <EmptyState title={ciclos.length ? 'Elige un ciclo para comenzar' : 'Todavía no hay ciclos'} description="Selecciona un período para ver qué registros contiene." action={<Link href={route('ciclos.index')} className="text-sm font-semibold text-[hsl(var(--action))]">Ir a ciclos</Link>} />
            : <section className={card}>
                <div className="flex flex-wrap items-start justify-between gap-3"><div><h2 className="text-xl font-bold">{seleccionado.nombre_ciclo}</h2><p className="mt-1 text-sm text-[hsl(var(--muted))]">{formatDate(seleccionado.fecha_inicio)} – {formatDate(seleccionado.fecha_fin)}</p></div><span className="rounded-xl bg-[hsl(var(--background))] px-3 py-2 text-sm font-semibold">{seleccionado.estado}</span></div>
                {!puedeEliminar && <p role="status" className="mt-4 rounded-xl border border-[hsl(var(--border))] p-3 text-sm">Puedes revisar este ciclo. La descarga para eliminación y el borrado se habilitan cuando esté cerrado y su fecha de fin haya pasado.</p>}
                <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">{Object.entries(resumen ?? {}).map(([table, count]) => <div key={table} className="rounded-xl bg-[hsl(var(--background))] p-3"><p className="text-sm text-[hsl(var(--muted))]">{labels[table] ?? table}</p><p className="mt-1 text-xl font-bold tabular-nums">{count}</p></div>)}</div>
                <div className="mt-6 space-y-3 border-t border-[hsl(var(--border))] pt-5">
                    <p className="text-sm text-[hsl(var(--muted))]">El ZIP contiene CSV, JSON y las imágenes de hojas de respuestas. Incluye los pagos de estudiantes y docentes asignados a este ciclo. Guarda y revisa el archivo antes de eliminar; no es una restauración automática.</p>
                    {totalSinCiclo > 0 && <p role="status" className="text-sm font-semibold">Primero asigna un ciclo a los {totalSinCiclo} pagos docentes pendientes que aparecen debajo.</p>}
                    <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <PrimaryButton onClick={download} disabled={!ready || downloading || remove.processing}>{downloading ? <LoaderCircle className="size-4 animate-spin" /> : <Download className="size-4" />}{downloading ? 'Preparando respaldo…' : '1. Descargar todos los registros'}</PrimaryButton>
                        <DangerButton onClick={() => { remove.clearErrors(); setConfirmation(true); }} disabled={!ready || !downloadToken || downloading}><Trash2 className="size-4" />2. Eliminar ciclo y registros</DangerButton>
                    </div>
                    {downloadToken && <p role="status" className="flex items-start gap-2 text-sm"><CheckCircle2 aria-hidden="true" className="size-5 shrink-0 text-[hsl(var(--action))]" />Descarga iniciada. Comprueba que el ZIP está guardado y se puede abrir. La autorización caduca en una hora; si cambian los registros, tendrás que descargarlo de nuevo.</p>}
                    {downloadError && <div role="alert" className="space-y-2"><p className="text-sm">{downloadError}</p><SecondaryButton onClick={download} disabled={downloading || !ready}><RefreshCw className="size-4" />Reintentar descarga</SecondaryButton></div>}
                </div>
            </section>}

        {totalSinCiclo > 0 && <section className={card}>
            <h2 className="flex items-center gap-2 text-lg font-bold"><Archive aria-hidden="true" className="size-5" />Pagos docentes sin ciclo</h2>
            <p className="mt-2 text-sm text-[hsl(var(--muted))]">Selecciona el ciclo real de cada pago antiguo. No se asignan por fecha, porque pueden existir ciclos simultáneos.</p>
            <form onSubmit={(event) => { event.preventDefault(); visit(String(seleccionado?.id_ciclo ?? ''), search); }} className="mt-4 flex flex-col gap-2 sm:flex-row">
                <label htmlFor="pending-search" className="sr-only">Buscar docente</label><input id="pending-search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Buscar por nombre del docente" className="min-w-0 flex-1" /><SecondaryButton type="submit">Buscar</SecondaryButton>
            </form>
            {pagosSinCiclo.data.length ? <div className="mt-4 grid gap-3 lg:grid-cols-2">{pagosSinCiclo.data.map((payment) => <AssignPayment key={payment.id_pago_docente} payment={payment} cycles={ciclos} />)}</div> : <div className="mt-4"><EmptyState title="Sin coincidencias" description="Prueba con otro nombre de docente." action={<SecondaryButton onClick={() => { setSearch(''); visit(String(seleccionado?.id_ciclo ?? ''), ''); }}>Limpiar búsqueda</SecondaryButton>} /></div>}
            <div className="mt-4"><Pagination links={pagosSinCiclo.meta.links} /></div>
        </section>}

        <FormModal show={confirmation} title="Confirmar eliminación definitiva" onClose={() => { if (!remove.processing) setConfirmation(false); }}>
            <form onSubmit={destroy} className="space-y-4">
                <p className="text-sm text-[hsl(var(--muted))]">Se eliminará <strong>{seleccionado?.nombre_ciclo}</strong> y sus matrículas, cuotas, pagos de estudiantes y docentes, notas, asistencias, simulacros y otros registros asociados. Esta acción no se puede deshacer desde el sistema.</p>
                <label className="flex items-start gap-3 text-sm"><input type="checkbox" checked={remove.data.respaldo_verificado} onChange={(event) => remove.setData('respaldo_verificado', event.target.checked)} className="mt-1" />Guardé el ZIP, pude abrirlo y revisé sus registros.</label>
                <InputError message={remove.errors.respaldo_verificado} />
                <label htmlFor="cycle-confirm" className="block text-sm font-medium">Escribe exactamente: <strong>{seleccionado?.nombre_ciclo}</strong></label>
                <input id="cycle-confirm" autoComplete="off" value={remove.data.nombre_ciclo} onChange={(event) => remove.setData('nombre_ciclo', event.target.value)} className="w-full" disabled={remove.processing} />
                <InputError message={remove.errors.nombre_ciclo} />
                <InputError message={remove.errors.token_respaldo} />
                <InputError message={(remove.errors as Record<string, string>).respaldo ?? (remove.errors as Record<string, string>).ciclo ?? (remove.errors as Record<string, string>).pagos_docentes} />
                <div className="flex flex-col gap-2 sm:flex-row sm:justify-end"><SecondaryButton onClick={() => setConfirmation(false)} disabled={remove.processing}>Cancelar</SecondaryButton><DangerButton type="submit" disabled={remove.processing || !remove.data.respaldo_verificado || remove.data.nombre_ciclo !== seleccionado?.nombre_ciclo}>{remove.processing ? 'Eliminando…' : 'Eliminar definitivamente'}</DangerButton></div>
            </form>
        </FormModal>
    </PageShell>;
}

function AssignPayment({ payment, cycles }: { payment: PagoDocente; cycles: HistoryCycle[] }) {
    const form = useForm({ id_ciclo: '' });
    return <form onSubmit={(event) => { event.preventDefault(); form.patch(route('historial-ciclos.assign', payment.id_pago_docente), { preserveScroll: true }); }} className="rounded-xl border border-[hsl(var(--border))] p-4">
        <h3 className="font-semibold">{payment.profesor?.nombre} {payment.profesor?.apellido}</h3>
        <p className="mt-1 text-sm text-[hsl(var(--muted))]">{formatMoney(payment.monto)} · {payment.periodo} · {payment.nro_comprobante ?? 'Sin comprobante'} · {payment.estado}</p>
        <label htmlFor={`assign-${payment.id_pago_docente}`} className="mt-3 block text-sm font-medium">Ciclo de este pago</label>
        <select id={`assign-${payment.id_pago_docente}`} value={form.data.id_ciclo} onChange={(event) => form.setData('id_ciclo', event.target.value)} className="mt-1 w-full" required disabled={form.processing}><option value="">Selecciona un ciclo</option>{cycles.map((cycle) => <option key={cycle.id_ciclo} value={cycle.id_ciclo}>{cycle.nombre_ciclo}</option>)}</select>
        <InputError message={form.errors.id_ciclo} />
        <SecondaryButton type="submit" className="mt-3 w-full" disabled={!form.data.id_ciclo || form.processing}>{form.processing ? 'Guardando…' : 'Asignar ciclo'}</SecondaryButton>
    </form>;
}
