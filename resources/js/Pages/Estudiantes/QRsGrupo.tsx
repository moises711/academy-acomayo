import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { QRCodeSVG } from 'qrcode.react';
import { Printer } from 'lucide-react';

type Estudiante = { id: number; nombre: string; codigo: string; qrValue: string };

export default function QRsGrupo({ grupo, estudiantes }: { grupo: { nombre: string; ciclo?: string | null }; estudiantes: Estudiante[] }) {
    return (
        <PageShell title="Códigos QR del grupo" description={`${grupo.nombre}${grupo.ciclo ? ` · ${grupo.ciclo}` : ''}`}>
            <div className="mb-5 flex gap-2 print:hidden">
                <PrimaryButton type="button" onClick={() => window.print()}><Printer className="mr-2 size-4" />Imprimir o guardar PDF</PrimaryButton>
                <SecondaryButton type="button" onClick={() => window.history.back()}>Volver</SecondaryButton>
            </div>
            {estudiantes.length === 0 ? <p className="rounded-2xl border border-dashed p-8 text-center">No hay estudiantes activos inscritos en este grupo.</p> : (
                <section className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 print:grid-cols-4">
                    {estudiantes.map((estudiante) => <article key={estudiante.id} className="flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900 print:break-inside-avoid print:rounded-none print:shadow-none">
                        <QRCodeSVG value={estudiante.qrValue} size={176} level="H" title={`QR de asistencia de ${estudiante.nombre}`} className="max-w-full" />
                        <h2 className="text-sm font-semibold text-slate-900 dark:text-white">{estudiante.nombre}</h2>
                        <p className="font-mono text-xs font-bold tracking-wide text-blue-800 dark:text-blue-200">{estudiante.codigo}</p>
                    </article>)}
                </section>
            )}
            <style>{'@media print { @page { margin: 10mm; } body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; } [data-slot="sidebar"], header, nav { display: none !important; } main { padding: 0 !important; } }'}</style>
        </PageShell>
    );
}
