import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';
import { formatDate, formatMoney, fullName } from '@/lib/format';
import { Pago } from '@/types/academia';

type PagoComprobante = Pago & {
    matricula: {
        estudiante: { dni: string; nombre: string; apellido: string };
        ciclo: { nombre_ciclo: string };
    };
    usuario: { name: string };
    cuotas: Array<{ id_cuota: number; concepto: string; pivot: { monto_aplicado: string | number } }>;
};

export default function Comprobante({ pago, portal = false, returnUrl }: { pago: PagoComprobante; portal?: boolean; returnUrl?: string }) {
    return (
        <main className="mx-auto max-w-3xl p-4 text-slate-900 sm:p-8">
            <Head title={`Comprobante ${pago.nro_comprobante ?? pago.id_pago}`} />
            <div className="mb-6 flex flex-wrap gap-3 justify-between print:hidden">
                <Link href={returnUrl ?? route('pagos.index')} className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"><ArrowLeft className="size-4" />{portal ? 'Volver al portal' : 'Volver a caja'}</Link>
                <PrimaryButton onClick={() => window.print()}><Printer className="mr-2 size-4" />Imprimir</PrimaryButton>
            </div>
            <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10 print:rounded-none print:border-0 print:p-0 print:shadow-none">
                <header className="flex flex-col justify-between gap-6 border-b border-slate-200 pb-6 sm:flex-row sm:items-start">
                    <div><p className="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Academia · comprobante de pago</p><h1 className="mt-3 text-2xl font-bold">Constancia de cobro</h1><p className="mt-1 text-sm text-slate-500">Documento interno de control de pagos</p></div>
                    <div className="rounded-xl bg-slate-50 p-4 text-sm sm:min-w-52"><p className="text-slate-500">N.º de comprobante</p><p className="mt-1 font-mono font-semibold">{pago.nro_comprobante ?? '—'}</p><p className="mt-3 text-slate-500">Fecha</p><p className="mt-1 font-medium">{formatDate(pago.fecha_pago)}</p></div>
                </header>
                <section className="grid gap-5 border-b border-slate-200 py-6 sm:grid-cols-2">
                    <Info label="Estudiante" value={fullName(pago.matricula.estudiante)} />
                    <Info label="DNI" value={pago.matricula.estudiante.dni} />
                    <Info label="Ciclo" value={pago.matricula.ciclo.nombre_ciclo} />
                    <Info label="Método de pago" value={pago.metodo_pago} />
                    <Info label="Atendido por" value={pago.usuario.name} />
                    <Info label="Estado" value={pago.estado === 'valido' ? 'Válido' : 'Anulado'} />
                </section>
                <section className="py-6">
                    <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Cuotas cubiertas</h2>
                    <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b border-slate-200 text-slate-500"><th className="py-3 pr-3">Concepto</th><th className="py-3 text-right">Importe aplicado</th></tr></thead><tbody>
                        {pago.cuotas.map((cuota) => <tr key={cuota.id_cuota} className="border-b border-slate-100"><td className="py-3 pr-3">{cuota.concepto}</td><td className="py-3 text-right tabular-nums">{formatMoney(cuota.pivot.monto_aplicado)}</td></tr>)}
                    </tbody></table></div>
                    <div className="mt-5 flex justify-end"><div className="min-w-56 border-t-2 border-slate-900 pt-3 text-right"><p className="text-sm text-slate-500">Total recibido</p><p className="mt-1 text-2xl font-bold tabular-nums">{formatMoney(pago.monto)}</p></div></div>
                </section>
                {pago.estado === 'anulado' && <p className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">Pago anulado: {pago.motivo_anulacion ?? 'Motivo no disponible'}</p>}
                <footer className="mt-8 border-t border-dashed border-slate-300 pt-5 text-xs text-slate-500">Emitido por {pago.usuario.name}. Conserva este comprobante para cualquier consulta relacionada con el estado de cuenta.</footer>
            </article>
        </main>
    );
}

function Info({ label, value }: { label: string; value: string }) {
    return <div><p className="text-xs uppercase tracking-wide text-slate-500">{label}</p><p className="mt-1 font-medium">{value}</p></div>;
}
