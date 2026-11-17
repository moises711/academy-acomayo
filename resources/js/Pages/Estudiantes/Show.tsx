import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Activity, Award, BookOpen, CalendarDays, CreditCard, Users } from 'lucide-react';
import PageShell from '@/Components/PageShell';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import { Asistencia, Estudiante, Matricula, Nota, Pago, ResultadoSimulacro } from '@/types/academia';
import { formatDate, formatMoney, fullName } from '@/lib/format';

type Guardian = { id_apoderado: number; nombre: string; apellido: string; parentesco: string | null; telefono: string | null; email: string | null };
type Fee = { id_cuota: number; concepto: string; monto_final: string | number; fecha_vencimiento: string; estado_pago: string };
type Enrollment = Matricula & { apoderados?: never; cuotas?: Fee[]; pagos?: Pago[] };
type Attendance = Asistencia & { horario?: { curso?: { nombre_curso: string } } | null };
type Grade = Nota & { evaluacion?: { titulo: string; fecha: string; nota_maxima: string | number; curso?: { nombre_curso: string } } };
type ExamResult = ResultadoSimulacro & { simulacro?: { titulo: string; fecha_aplicacion: string }; matricula?: Matricula };
type Props = { estudiante: Estudiante & { apoderados?: Guardian[]; matriculas?: Enrollment[] }; asistencias: Attendance[]; notas: Grade[]; resultados: ExamResult[]; canGenerateCarnets: boolean };
type Tab = 'datos' | 'matriculas' | 'asistencia' | 'notas' | 'pagos' | 'simulacros';

const tabs: { id: Tab; label: string; icon: typeof Users }[] = [
    { id: 'datos', label: 'Datos', icon: Users },
    { id: 'matriculas', label: 'Matrículas', icon: BookOpen },
    { id: 'asistencia', label: 'Asistencia', icon: CalendarDays },
    { id: 'notas', label: 'Notas', icon: Award },
    { id: 'pagos', label: 'Pagos', icon: CreditCard },
    { id: 'simulacros', label: 'Simulacros', icon: Activity },
];
const card = 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5';

export default function EstudiantesShow({ estudiante, asistencias, notas, resultados, canGenerateCarnets }: Props) {
    const [activeTab, setActiveTab] = useState<Tab>('datos');
    const matriculas = estudiante.matriculas ?? [];
    const cuotas = matriculas.flatMap((matricula) => matricula.cuotas ?? []);

    return (
        <PageShell title={fullName(estudiante)} description="Ficha integral del estudiante, su actividad académica y financiera."
            actions={<div className="flex flex-wrap gap-2">{canGenerateCarnets && <Link href={route('estudiantes.carnet', estudiante.id_estudiante)}><PrimaryButton>Ver e imprimir carnet</PrimaryButton></Link>}<Link href={route('estudiantes.edit', estudiante.id_estudiante)}><PrimaryButton>Editar ficha</PrimaryButton></Link></div>}>
            <div className="flex gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Secciones de la ficha del estudiante">{tabs.map(({ id, label, icon: Icon }) => <button key={id} type="button" role="tab" aria-selected={activeTab === id} aria-controls={`panel-${id}`} onClick={() => setActiveTab(id)} className={`inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600 ${activeTab === id ? 'bg-blue-700 text-white' : 'border border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200'}`}><Icon className="size-4" />{label}</button>)}</div>

            {activeTab === 'datos' && <div id="panel-datos" role="tabpanel" className="space-y-5">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Info label="DNI" value={estudiante.dni} /><Info label="Correo" value={estudiante.email ?? 'Sin correo'} /><Info label="Teléfono" value={estudiante.telefono ?? 'Sin teléfono'} /><Info label="Estado" value={<StatusBadge value={estudiante.estado} />} />
                    <Info label="Fecha de nacimiento" value={estudiante.fecha_nacimiento ? formatDate(estudiante.fecha_nacimiento) : 'Sin registrar'} /><Info label="Dirección" value={estudiante.direccion ?? 'Sin registrar'} /><Info label="Colegio" value={estudiante.colegio_procedencia ?? 'Sin registrar'} /><Info label="Año de egreso" value={estudiante.anio_egreso ?? 'Sin registrar'} />
                </div>
                <section className={card}><h2 className="font-semibold text-slate-900 dark:text-white">Apoderados</h2>{(estudiante.apoderados ?? []).length ? <div className="mt-4 grid gap-3 sm:grid-cols-2">{estudiante.apoderados?.map((guardian) => <article key={guardian.id_apoderado} className="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><h3 className="font-medium">{guardian.nombre} {guardian.apellido}</h3><p className="mt-1 text-sm text-slate-500">{guardian.parentesco ?? 'Parentesco sin registrar'}</p><p className="mt-2 text-sm text-slate-600 dark:text-slate-300">{guardian.telefono ?? 'Sin teléfono'}{guardian.email ? ` · ${guardian.email}` : ''}</p></article>)}</div> : <Empty text="No hay apoderados registrados." />}</section>
            </div>}

            {activeTab === 'matriculas' && <section id="panel-matriculas" role="tabpanel" className={card}><h2 className="font-semibold text-slate-900 dark:text-white">Historial de matrículas</h2>{matriculas.length ? <div className="mt-4 grid gap-3 lg:grid-cols-2">{matriculas.map((matricula) => <article key={matricula.id_matricula} className="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><div className="flex flex-wrap items-center justify-between gap-2"><h3 className="font-semibold">{matricula.ciclo?.nombre_ciclo ?? 'Ciclo sin definir'}</h3><StatusBadge value={matricula.estado_matricula} /></div><p className="mt-2 text-sm text-slate-600 dark:text-slate-300">{matricula.grupo?.nombre ?? 'Sin grupo'} · {matricula.carrera?.nombre ?? 'Sin carrera objetivo'}</p><p className="mt-1 text-xs text-slate-500">Matriculado el {formatDate(matricula.fecha_matricula)}{matricula.beca ? ` · ${matricula.beca.nombre}` : ''}</p></article>)}</div> : <Empty text="Este estudiante aún no tiene matrículas." />}</section>}

            {activeTab === 'asistencia' && <section id="panel-asistencia" role="tabpanel" className={card}><h2 className="font-semibold text-slate-900 dark:text-white">Asistencia reciente</h2>{asistencias.length ? <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{asistencias.map((mark) => <article key={mark.id_asistencia} className="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><div className="flex flex-wrap items-center justify-between gap-3"><p className="font-medium">{mark.horario?.curso?.nombre_curso ?? 'Clase'}</p><StatusBadge value={mark.estado} /></div><p className="mt-2 text-sm text-slate-500">{formatDate(mark.fecha)} · {mark.origen === 'qr' ? 'Registro QR' : 'Registro manual'}</p></article>)}</div> : <Empty text="No hay registros de asistencia para sus matrículas." />}</section>}

            {activeTab === 'notas' && <section id="panel-notas" role="tabpanel" className={card}><h2 className="font-semibold text-slate-900 dark:text-white">Evaluaciones y notas</h2>{notas.length ? <div className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">{notas.map((grade) => <article key={grade.id_nota} className="flex flex-wrap items-center justify-between gap-3 py-3"><div><h3 className="font-medium">{grade.evaluacion?.titulo ?? 'Evaluación'} · {grade.evaluacion?.curso?.nombre_curso}</h3><p className="mt-1 text-sm text-slate-500">{grade.evaluacion?.fecha ? formatDate(grade.evaluacion.fecha) : 'Fecha sin registrar'}{grade.observacion ? ` · ${grade.observacion}` : ''}</p></div><p className="text-lg font-bold tabular-nums text-blue-800 dark:text-blue-200">{grade.nota} <span className="text-sm font-normal text-slate-500">/ {grade.evaluacion?.nota_maxima ?? '—'}</span></p></article>)}</div> : <Empty text="No hay notas registradas." />}</section>}

            {activeTab === 'pagos' && <section id="panel-pagos" role="tabpanel" className={card}><h2 className="font-semibold text-slate-900 dark:text-white">Cuotas y pagos</h2>{cuotas.length ? <div className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">{cuotas.map((fee) => <article key={fee.id_cuota} className="flex flex-wrap items-center justify-between gap-3 py-3"><div><h3 className="font-medium">{fee.concepto}</h3><p className="mt-1 text-sm text-slate-500">Vence {formatDate(fee.fecha_vencimiento)}</p></div><div className="flex items-center gap-3"><p className="font-semibold">{formatMoney(fee.monto_final)}</p><StatusBadge value={fee.estado_pago} /></div></article>)}</div> : <Empty text="No hay cuotas para las matrículas de este estudiante." />}</section>}

            {activeTab === 'simulacros' && <section id="panel-simulacros" role="tabpanel" className={card}><h2 className="font-semibold text-slate-900 dark:text-white">Resultados de simulacros</h2>{resultados.length ? <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{resultados.map((result) => <article key={result.id_resultado} className="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><h3 className="font-semibold">{result.simulacro?.titulo ?? 'Simulacro'}</h3><p className="mt-2 text-2xl font-bold text-blue-800 dark:text-blue-200">{result.puntaje_obtenido} <span className="text-sm font-normal text-slate-500">puntos</span></p><p className="mt-1 text-sm text-slate-500">Puesto general {result.puesto_general ?? 'pendiente'} · carrera {result.puesto_carrera ?? '—'}</p><p className="mt-2 text-xs text-slate-500">{result.fecha_rendicion ? formatDate(result.fecha_rendicion) : (result.simulacro?.fecha_aplicacion ? formatDate(result.simulacro.fecha_aplicacion) : 'Fecha sin registrar')}</p></article>)}</div> : <Empty text="No hay resultados de simulacros registrados." />}</section>}
        </PageShell>
    );
}

function Info({ label, value }: { label: string; value: React.ReactNode }) {
    return <div className={card}><p className="text-sm text-slate-500">{label}</p><div className="mt-1 break-words font-medium text-slate-900 dark:text-white">{value}</div></div>;
}

function Empty({ text }: { text: string }) {
    return <p className="mt-4 rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500 dark:border-slate-700">{text}</p>;
}
