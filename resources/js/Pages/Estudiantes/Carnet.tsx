import Form from '@/Components/InertiaForm';
import { Printer } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

type CarnetData = { id_estudiante: number; nombre: string; codigo: string; vigencia: string | null; grupo: string | null; ciclo: string | null; qrValue: string; fotoUrl: string | null };
type Plantilla = { ancho_mm: number; alto_mm: number; orientacion: 'horizontal' | 'vertical'; qr_mm: number; qr_posicion: 'izquierda' | 'derecha' | 'centro' | 'esquina'; color_fondo: string; color_acento: string; color_texto: string; tamano_fuente: number; franja_superior: boolean; reverso: boolean; texto_reverso: string; texto_carnet: string; campos: Record<string, boolean> };
type Props = { carnets: CarnetData[]; regenerarUrl: string | null; canRegenerate: boolean; plantilla: Plantilla | null };

export default function Carnet({ carnets, regenerarUrl, canRegenerate, plantilla }: Props) {
    const style = plantilla ?? { ancho_mm: 85.6, alto_mm: 54, orientacion: 'horizontal' as const, qr_mm: 28, qr_posicion: 'derecha' as const, color_fondo: '#ffffff', color_acento: '#173b66', color_texto: '#172033', tamano_fuente: 10, franja_superior: true, reverso: false, texto_reverso: '', texto_carnet: '', campos: { nombre: true, dni: false, codigo: true, grupo: true, ciclo: true, foto: true, logo: true, nombre_academia: true, vigencia: false, texto_libre: false } };
    const qrSizeMm = Math.max(style.qr_mm, 28);
    return (
        <PageShell title={carnets.length === 1 ? 'Carnet de estudiante' : 'Carnets del grupo'} description="Usa la impresión del navegador para guardar o imprimir los carnets.">
            <div className="mb-5 flex flex-wrap gap-2 print:hidden"><PrimaryButton type="button" onClick={() => window.print()}><Printer className="mr-2 size-4" />Imprimir carnets</PrimaryButton><SecondaryButton type="button" onClick={() => window.history.back()}>Volver</SecondaryButton></div>
            {carnets.length === 0 ? <div className="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-600 dark:border-slate-700 dark:text-slate-300">No hay estudiantes inscritos activos en este grupo.</div> : <div className="grid gap-4 sm:grid-cols-2 print:block">
                {carnets.map((carnet) => <article key={carnet.id_estudiante} className={`relative mx-auto flex max-w-full items-center gap-3 overflow-hidden rounded-[1.1rem] border border-slate-200 p-3 shadow-[0_12px_30px_rgba(23,59,102,0.12)] ring-1 ring-inset ring-white/70 print:mb-5 print:break-inside-avoid print:border-slate-400 print:shadow-none print:ring-0 ${style.orientacion === 'vertical' || style.qr_posicion === 'centro' ? 'flex-col justify-center' : ''}`} style={{ width: `${style.ancho_mm}mm`, height: `${style.alto_mm}mm`, backgroundColor: style.color_fondo, color: style.color_texto, fontSize: `${style.tamano_fuente}px` }}>
                    {style.franja_superior && <div className="absolute inset-x-0 top-0 h-2" style={{ backgroundColor: style.color_acento }} />}
                    {style.campos.foto && <div className="grid size-[20%] shrink-0 place-items-center overflow-hidden rounded-2xl bg-gradient-to-br from-blue-100 via-sky-50 to-amber-50 text-[#173B66] ring-1 ring-inset ring-blue-100">{carnet.fotoUrl ? <img src={carnet.fotoUrl} alt={`Foto de ${carnet.nombre}`} className="size-full object-cover" /> : <span className="text-2xl font-bold">{carnet.nombre.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase()}</span>}</div>}
                    <div className="min-w-0 flex-1">{style.campos.logo && <span className="mr-1 inline-grid size-5 place-items-center rounded-lg bg-blue-100 text-[0.7em] font-black text-blue-900">A</span>}{style.campos.nombre_academia && <span className="text-[0.8em] font-bold uppercase tracking-[0.12em]" style={{ color: style.color_acento }}>Academia</span>}{style.campos.nombre && <h2 className="mt-1 truncate text-[1.2em] font-bold leading-tight">{carnet.nombre}</h2>}<p className="mt-1 inline-flex rounded-lg px-2 py-1 font-mono text-[0.82em] font-bold tracking-wide" style={{ backgroundColor: `${style.color_acento}18`, color: style.color_acento }}>Código {carnet.codigo}</p>{style.campos.grupo && <p className="mt-1 truncate font-semibold">{carnet.grupo ?? 'Grupo pendiente'}</p>}{style.campos.ciclo && <p className="truncate opacity-70">{carnet.ciclo ?? 'Ciclo pendiente'}</p>}{style.campos.vigencia && <p className="opacity-70">Vigencia {formatDate(carnet.vigencia)}</p>}{style.campos.texto_libre && <p className="mt-1 truncate opacity-70">{style.texto_carnet}</p>}</div>
                    <div className={`shrink-0 text-center ${style.qr_posicion === 'izquierda' ? 'order-first' : ''} ${style.qr_posicion === 'esquina' ? 'absolute bottom-2 right-2' : ''}`}><div className="rounded-xl border-4 border-white bg-white p-0.5 shadow-sm"><QRCodeSVG value={carnet.qrValue} size={160} level="H" title={`Código QR del carnet de ${carnet.nombre}`} style={{ width: `${qrSizeMm}mm`, height: `${qrSizeMm}mm` }} /></div><p className="mt-1 text-[0.7em] font-semibold opacity-70">Asistencia</p></div>
                    {canRegenerate && regenerarUrl && <Form action={regenerarUrl} method="post" className="absolute bottom-2 right-3 print:hidden">{({ processing }) => <button disabled={processing} onClick={(event) => { if (!window.confirm('El QR actual dejará de funcionar y tendrás que reimprimir el carnet. ¿Deseas continuar?')) event.preventDefault(); }} className="text-xs font-medium text-red-700 underline disabled:opacity-50">{processing ? 'Generando…' : 'Regenerar QR'}</button>}</Form>}
                </article>)}
            </div>}
            {style.reverso && <section className="mt-6 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300 print:hidden"><h2 className="font-semibold">Texto de reverso configurado</h2><p className="mt-1">{style.texto_reverso}</p><p className="mt-2 text-xs">El reverso automático a doble cara requiere la generación PDF.</p></section>}
            <style>{'@media print { @page { margin: 12mm; } body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; } [data-slot="sidebar"], header, nav { display: none !important; } main { padding: 0 !important; } }'}</style>
        </PageShell>
    );
}

function formatDate(value: string | null): string {
    if (!value) return '—';
    const [year, month, day] = value.slice(0, 10).split('-');
    return `${day}/${month}/${year}`;
}
