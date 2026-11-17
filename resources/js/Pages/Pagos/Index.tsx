import { Link, useForm } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { Banknote, FilePlus2, ReceiptText, Wallet } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import EmptyState from '@/Components/EmptyState';
import FormModal from '@/Components/FormModal';
import InputError from '@/Components/InputError';
import PageShell from '@/Components/PageShell';
import Pagination from '@/Components/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatCard from '@/Components/StatCard';
import { formatDate, formatMoney, fullName } from '@/lib/format';
import { Ciclo, Cuota, Decimal, Estudiante, Matricula, Paginated, Pago } from '@/types/academia';

type MatriculaCobranza = Matricula & { estudiante: Estudiante; ciclo: Ciclo };
type CuotaCobranza = Cuota & {
    monto_pagado: Decimal;
    monto_pendiente: Decimal;
    matricula: MatriculaCobranza;
};
type PagoCobrado = Pago & {
    matricula: MatriculaCobranza;
    usuario: { name: string };
    cuotas: Cuota[];
};

type Props = {
    cuotas: Paginated<CuotaCobranza>;
    cuotasDisponibles: CuotaCobranza[];
    pagos: Paginated<PagoCobrado>;
    matriculas: MatriculaCobranza[];
    resumenCaja: { metodo_pago: string; operaciones: number; total: string }[];
    filters: { estado?: string; buscar?: string; fecha?: string };
    stats: { cuotas_pendientes: number; cuotas_vencidas: number; deuda_pendiente: string; recaudado_hoy: string };
};

type Aplicacion = { id_cuota: number; monto_aplicado: string };
const estados = ['Pendiente', 'Parcial', 'Pagado', 'Vencido', 'Anulado'];
const metodos = ['Efectivo', 'Transferencia', 'Tarjeta', 'Yape/Plin'];
const comprobantes = ['Recibo', 'Boleta', 'Factura'];

export default function PagosIndex({ cuotas, cuotasDisponibles, pagos, matriculas, resumenCaja, filters, stats }: Props) {
    const [showFeeForm, setShowFeeForm] = useState(false);
    const [showPaymentForm, setShowPaymentForm] = useState(false);
    const [paymentToCancel, setPaymentToCancel] = useState<PagoCobrado | null>(null);
    const feeForm = useForm({ id_matricula: '', concepto: '', numero_cuota: '', monto_original: '', monto_descuento: '0.00', monto_final: '', fecha_vencimiento: '' });
    const paymentForm = useForm({ id_matricula: '', monto: '0.00', metodo_pago: 'Efectivo', tipo_comprobante: 'Recibo', aplicaciones: [] as Aplicacion[] });
    const annulForm = useForm({ motivo_anulacion: '' });

    const feesForMatricula = useMemo(
        () => cuotasDisponibles.filter((fee) => String(fee.id_matricula) === paymentForm.data.id_matricula),
        [cuotasDisponibles, paymentForm.data.id_matricula],
    );

    const updateFeeAmount = (field: 'monto_original' | 'monto_descuento', value: string) => {
        feeForm.setData((current) => {
            const next = { ...current, [field]: value };
            const finalCents = Math.max(0, toCents(next.monto_original) - toCents(next.monto_descuento));
            return { ...next, monto_final: fromCents(finalCents) };
        });
    };

    const toggleFee = (fee: CuotaCobranza, checked: boolean) => {
        const applications = checked
            ? [...paymentForm.data.aplicaciones, { id_cuota: fee.id_cuota, monto_aplicado: String(fee.monto_pendiente) }]
            : paymentForm.data.aplicaciones.filter((application) => application.id_cuota !== fee.id_cuota);
        paymentForm.setData('aplicaciones', applications);
        paymentForm.setData('monto', fromCents(applications.reduce((total, application) => total + toCents(application.monto_aplicado), 0)));
    };

    const updateApplication = (idCuota: number, amount: string) => {
        const applications = paymentForm.data.aplicaciones.map((application) => application.id_cuota === idCuota
            ? { ...application, monto_aplicado: amount }
            : application);
        paymentForm.setData('aplicaciones', applications);
        paymentForm.setData('monto', fromCents(applications.reduce((total, application) => total + toCents(application.monto_aplicado), 0)));
    };

    const submitFee = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        feeForm.transform((data) => ({ ...data, numero_cuota: data.numero_cuota || null }));
        feeForm.post(route('cuotas.store'), { preserveScroll: true, onSuccess: () => { feeForm.reset(); setShowFeeForm(false); } });
    };

    const submitPayment = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        paymentForm.post(route('pagos.store'), { preserveScroll: true, onSuccess: () => { paymentForm.reset(); setShowPaymentForm(false); } });
    };

    const submitAnnulment = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!paymentToCancel) return;
        annulForm.post(route('pagos.anular', paymentToCancel.id_pago), {
            preserveScroll: true,
            onSuccess: () => { annulForm.reset(); setPaymentToCancel(null); },
        });
    };

    return (
        <PageShell title="Caja y cuotas" description="Administra saldos, registra cobros y conserva los comprobantes y anulaciones."
            actions={<div className="flex flex-wrap gap-2"><SecondaryButton onClick={() => setShowFeeForm(true)}><FilePlus2 className="mr-2 size-4" />Crear cuota</SecondaryButton><PrimaryButton onClick={() => setShowPaymentForm(true)}><Banknote className="mr-2 size-4" />Registrar pago</PrimaryButton></div>}>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard title="Cuotas abiertas" value={stats.cuotas_pendientes} icon={ReceiptText} tone="cyan" />
                <StatCard title="Cuotas vencidas" value={stats.cuotas_vencidas} icon={ReceiptText} tone="amber" />
                <StatCard title="Deuda pendiente" value={formatMoney(stats.deuda_pendiente)} icon={Wallet} tone="violet" />
                <StatCard title="Recaudado hoy" value={formatMoney(stats.recaudado_hoy)} icon={Banknote} tone="emerald" />
            </div>

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 className="font-semibold text-slate-900 dark:text-white">Estado de cuotas</h2><p className="text-sm text-slate-500">Saldo calculado solo con pagos válidos.</p></div>
                    <Form action={route('pagos.index')} method="get" className="flex flex-wrap gap-2">
                        <input name="buscar" defaultValue={filters.buscar ?? ''} placeholder="DNI o nombre" aria-label="Buscar estudiante" className="w-40 rounded-lg border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        <select name="estado" defaultValue={filters.estado ?? ''} aria-label="Filtrar por estado" className="rounded-lg border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="">Todos los estados</option>{estados.map((estado) => <option key={estado}>{estado}</option>)}</select>
                        <button className="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white">Filtrar</button>
                    </Form>
                </div>
                {cuotas.data.length === 0 ? <EmptyState title="Sin cuotas para mostrar" description="Crea una cuota para una matrícula o cambia los filtros." action={<SecondaryButton onClick={() => setShowFeeForm(true)}>Crear cuota</SecondaryButton>} /> : <>
                    <div className="overflow-x-auto"><table className="w-full min-w-[800px] text-left text-sm"><thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950"><tr><th className="px-4 py-3">Estudiante / matrícula</th><th className="px-4 py-3">Concepto</th><th className="px-4 py-3">Vencimiento</th><th className="px-4 py-3">Monto</th><th className="px-4 py-3">Pagado</th><th className="px-4 py-3">Saldo / estado</th></tr></thead>
                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">{cuotas.data.map((fee) => <tr key={fee.id_cuota}>
                            <td className="px-4 py-4"><p className="font-medium text-slate-900 dark:text-white">{fullName(fee.matricula.estudiante)}</p><p className="text-xs text-slate-500">{fee.matricula.ciclo.nombre_ciclo}</p></td>
                            <td className="px-4 py-4 text-slate-700 dark:text-slate-200">{fee.concepto}{fee.numero_cuota ? ` · cuota ${fee.numero_cuota}` : ''}</td>
                            <td className="px-4 py-4 text-slate-600 dark:text-slate-300">{formatDate(fee.fecha_vencimiento)}</td>
                            <td className="px-4 py-4 tabular-nums">{formatMoney(fee.monto_final)}</td>
                            <td className="px-4 py-4 tabular-nums">{formatMoney(fee.monto_pagado)}</td>
                            <td className="px-4 py-4"><p className="font-semibold tabular-nums">{formatMoney(fee.monto_pendiente)}</p><StatusBadge state={fee.estado_pago} /></td>
                        </tr>)}</tbody>
                    </table></div>
                    <div className="mt-4"><Pagination links={cuotas.meta.links} /></div>
                </>}
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 className="font-semibold text-slate-900 dark:text-white">Resumen de caja</h2><p className="text-sm text-slate-500">Cobros válidos agrupados por medio de pago.</p></div>
                    <Form action={route('pagos.index')} method="get" className="flex gap-2"><input type="date" name="fecha" defaultValue={filters.fecha} aria-label="Fecha del resumen de caja" className="rounded-lg border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-950" /><button className="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white">Consultar</button></Form>
                </div>
                {resumenCaja.length === 0 ? <p className="rounded-xl bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-950">No hay cobros válidos en esta fecha.</p> : <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">{resumenCaja.map((row) => <article key={row.metodo_pago} className="rounded-xl border border-slate-100 p-4 dark:border-slate-800"><p className="text-sm text-slate-500">{row.metodo_pago}</p><p className="mt-1 text-xl font-semibold">{formatMoney(row.total)}</p><p className="mt-1 text-xs text-slate-500">{row.operaciones} operaciones</p></article>)}</div>}
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div className="mb-4"><h2 className="font-semibold text-slate-900 dark:text-white">Cobros y comprobantes</h2><p className="text-sm text-slate-500">Los pagos anulados se conservan y no se descuentan del estado de cuenta.</p></div>
                {pagos.data.length === 0 ? <EmptyState title="Aún no hay cobros" description="Los pagos registrados aparecerán en este historial." /> : <>
                    <div className="overflow-x-auto"><table className="w-full min-w-[780px] text-left text-sm"><thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950"><tr><th className="px-4 py-3">Fecha</th><th className="px-4 py-3">Estudiante</th><th className="px-4 py-3">Comprobante</th><th className="px-4 py-3">Método</th><th className="px-4 py-3">Monto / estado</th><th className="px-4 py-3 text-right">Acciones</th></tr></thead>
                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">{pagos.data.map((payment) => <tr key={payment.id_pago}>
                            <td className="px-4 py-4">{formatDate(payment.fecha_pago)}</td><td className="px-4 py-4 font-medium">{fullName(payment.matricula.estudiante)}</td>
                            <td className="px-4 py-4">{payment.nro_comprobante ?? '—'}</td><td className="px-4 py-4">{payment.metodo_pago}</td>
                            <td className="px-4 py-4"><p className="font-semibold">{formatMoney(payment.monto)}</p><StatusBadge state={payment.estado} /></td>
                            <td className="px-4 py-4"><div className="flex justify-end gap-3"><Link target="_blank" href={route('pagos.comprobante', payment.id_pago)} className="text-sm font-medium text-blue-700 hover:underline">Imprimir</Link>{payment.estado === 'valido' && <button type="button" onClick={() => setPaymentToCancel(payment)} className="text-sm font-medium text-red-700 hover:underline">Anular</button>}</div></td>
                        </tr>)}</tbody>
                    </table></div>
                    <div className="mt-4"><Pagination links={pagos.meta.links} /></div>
                </>}
            </section>

            <FormModal show={showFeeForm} title="Crear cuota" onClose={() => setShowFeeForm(false)}>
                <form onSubmit={submitFee} className="grid gap-4 sm:grid-cols-2">
                    <Field label="Matrícula" error={feeForm.errors.id_matricula}><select required value={feeForm.data.id_matricula} onChange={(event) => feeForm.setData('id_matricula', event.target.value)} className="rounded-lg border-slate-200"><option value="">Selecciona</option>{matriculas.map((matricula) => <option key={matricula.id_matricula} value={matricula.id_matricula}>{fullName(matricula.estudiante)} · {matricula.ciclo.nombre_ciclo}</option>)}</select></Field>
                    <Field label="Concepto" error={feeForm.errors.concepto}><input required maxLength={100} value={feeForm.data.concepto} onChange={(event) => feeForm.setData('concepto', event.target.value)} className="rounded-lg border-slate-200" placeholder="Matrícula, mensualidad…" /></Field>
                    <Field label="Número de cuota (opcional)" error={feeForm.errors.numero_cuota}><input type="number" min="1" value={feeForm.data.numero_cuota} onChange={(event) => feeForm.setData('numero_cuota', event.target.value)} className="rounded-lg border-slate-200" /></Field>
                    <Field label="Vencimiento" error={feeForm.errors.fecha_vencimiento}><input type="date" required value={feeForm.data.fecha_vencimiento} onChange={(event) => feeForm.setData('fecha_vencimiento', event.target.value)} className="rounded-lg border-slate-200" /></Field>
                    <Field label="Monto original (S/)" error={feeForm.errors.monto_original}><input type="number" min="0.01" step="0.01" required value={feeForm.data.monto_original} onChange={(event) => updateFeeAmount('monto_original', event.target.value)} className="rounded-lg border-slate-200" /></Field>
                    <Field label="Descuento (S/)" error={feeForm.errors.monto_descuento}><input type="number" min="0" step="0.01" value={feeForm.data.monto_descuento} onChange={(event) => updateFeeAmount('monto_descuento', event.target.value)} className="rounded-lg border-slate-200" /></Field>
                    <Field label="Monto final (S/)" error={feeForm.errors.monto_final}><input readOnly value={feeForm.data.monto_final} className="rounded-lg border-slate-200 bg-slate-50" /></Field>
                    <div className="flex justify-end gap-2 sm:col-span-2"><SecondaryButton type="button" onClick={() => setShowFeeForm(false)}>Cancelar</SecondaryButton><PrimaryButton disabled={feeForm.processing}>{feeForm.processing ? 'Guardando…' : 'Crear cuota'}</PrimaryButton></div>
                </form>
            </FormModal>

            <FormModal show={showPaymentForm} title="Registrar cobro" onClose={() => setShowPaymentForm(false)}>
                <form onSubmit={submitPayment} className="grid gap-4">
                    <Field label="Matrícula" error={paymentForm.errors.id_matricula}><select required value={paymentForm.data.id_matricula} onChange={(event) => { paymentForm.setData('id_matricula', event.target.value); paymentForm.setData('aplicaciones', []); paymentForm.setData('monto', '0.00'); }} className="rounded-lg border-slate-200"><option value="">Selecciona una matrícula</option>{matriculas.map((matricula) => <option key={matricula.id_matricula} value={matricula.id_matricula}>{fullName(matricula.estudiante)} · {matricula.ciclo.nombre_ciclo}</option>)}</select></Field>
                    <div className="max-h-64 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3">
                        {feesForMatricula.length === 0 ? <p className="text-sm text-slate-500">Selecciona una matrícula con saldo pendiente.</p> : feesForMatricula.map((fee) => {
                            const application = paymentForm.data.aplicaciones.find((item) => item.id_cuota === fee.id_cuota);
                            return <div key={fee.id_cuota} className="grid grid-cols-[auto_1fr_8rem] items-center gap-3 border-b border-slate-100 py-2 last:border-0">
                                <input type="checkbox" checked={Boolean(application)} onChange={(event) => toggleFee(fee, event.target.checked)} aria-label={`Aplicar pago a ${fee.concepto}`} />
                                <div><p className="text-sm font-medium">{fee.concepto}</p><p className="text-xs text-slate-500">Saldo {formatMoney(fee.monto_pendiente)} · vence {formatDate(fee.fecha_vencimiento)}</p></div>
                                <input type="number" min="0.01" max={fee.monto_pendiente} step="0.01" disabled={!application} value={application?.monto_aplicado ?? ''} onChange={(event) => updateApplication(fee.id_cuota, event.target.value)} aria-label={`Monto aplicado a ${fee.concepto}`} className="w-full rounded-lg border-slate-200 text-sm disabled:bg-slate-50" />
                            </div>;
                        })}
                    </div>
                    <InputError message={paymentForm.errors.aplicaciones} />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Importe cobrado (S/)" error={paymentForm.errors.monto}><input readOnly value={paymentForm.data.monto} className="rounded-lg border-slate-200 bg-slate-50" /></Field>
                        <Field label="Método" error={paymentForm.errors.metodo_pago}><select value={paymentForm.data.metodo_pago} onChange={(event) => paymentForm.setData('metodo_pago', event.target.value)} className="rounded-lg border-slate-200">{metodos.map((method) => <option key={method}>{method}</option>)}</select></Field>
                        <Field label="Comprobante" error={paymentForm.errors.tipo_comprobante}><select value={paymentForm.data.tipo_comprobante} onChange={(event) => paymentForm.setData('tipo_comprobante', event.target.value)} className="rounded-lg border-slate-200">{comprobantes.map((type) => <option key={type}>{type}</option>)}</select></Field>
                        <p className="self-end text-xs text-slate-500">Se genera un número de comprobante único automáticamente.</p>
                    </div>
                    <div className="flex justify-end gap-2"><SecondaryButton type="button" onClick={() => setShowPaymentForm(false)}>Cancelar</SecondaryButton><PrimaryButton disabled={paymentForm.processing || paymentForm.data.aplicaciones.length === 0}>{paymentForm.processing ? 'Guardando…' : 'Registrar e imprimir'}</PrimaryButton></div>
                </form>
            </FormModal>

            <FormModal show={paymentToCancel !== null} title="Anular pago" onClose={() => setPaymentToCancel(null)}>
                <form onSubmit={submitAnnulment} className="grid gap-4">
                    <p className="text-sm text-slate-600">El pago {paymentToCancel?.nro_comprobante ?? ''} se conservará como anulado y dejará de contar en el saldo del estudiante.</p>
                    <Field label="Motivo obligatorio" error={annulForm.errors.motivo_anulacion}><textarea required maxLength={200} rows={3} value={annulForm.data.motivo_anulacion} onChange={(event) => annulForm.setData('motivo_anulacion', event.target.value)} className="rounded-lg border-slate-200" /></Field>
                    <div className="flex justify-end gap-2"><SecondaryButton type="button" onClick={() => setPaymentToCancel(null)}>Cancelar</SecondaryButton><PrimaryButton disabled={annulForm.processing || !annulForm.data.motivo_anulacion.trim()}>{annulForm.processing ? 'Anulando…' : 'Confirmar anulación'}</PrimaryButton></div>
                </form>
            </FormModal>
        </PageShell>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <label className="grid gap-1.5 text-sm font-medium text-slate-700 dark:text-slate-200">{label}{children}<InputError message={error} /></label>;
}

function StatusBadge({ state }: { state: string }) {
    const styles = state === 'Pagado' || state === 'valido' ? 'bg-emerald-50 text-emerald-700' : state === 'Anulado' || state === 'anulado' || state === 'Vencido' ? 'bg-red-50 text-red-700' : state === 'Parcial' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700';
    return <span className={`mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${styles}`}>{state}</span>;
}

function toCents(value: string | number): number {
    const [units, fraction = ''] = String(value).split('.');
    return Number(units || 0) * 100 + Number(fraction.padEnd(2, '0').slice(0, 2) || 0);
}

function fromCents(value: number): string {
    return `${Math.floor(value / 100)}.${String(value % 100).padStart(2, '0')}`;
}
