import { Link, router } from '@inertiajs/react';
import { Award, BookOpen, CalendarDays, Clock3, CreditCard, FileText, GraduationCap, Heart, UserRound } from 'lucide-react';
import { useMemo } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

type StudentChoice = { id_estudiante: number; nombre_completo: string };
type Student = { id_estudiante: number; nombre: string; apellido: string; dni: string; email: string | null; telefono: string | null; codigo_qr: string };
type Enrollment = { id_matricula: number; estado_matricula: string; id_grupo: number | null; ciclo?: { nombre_ciclo: string }; grupo?: { nombre: string; turno: string } | null; carrera?: { nombre: string } | null };
type Schedule = { id_horario: number; dia_semana: number; hora_inicio: string; hora_fin: string; curso?: { nombre_curso: string }; profesor?: { nombre: string; apellido: string }; aula?: { nombre: string } };
type Attendance = { id_asistencia: number; fecha: string; estado: string; horario?: { curso?: { nombre_curso: string } } };
type Note = { id_nota: number; nota: string; observacion: string | null; evaluacion?: { titulo: string; fecha: string; nota_maxima: string; curso?: { nombre_curso: string } } };
type ExamResult = { id_resultado: number; puntaje_obtenido: string; puesto_general: number | null; puesto_carrera: number | null; simulacro?: { titulo: string; fecha_aplicacion: string; puntaje_maximo: string } };
type Fee = { id_cuota: number; concepto: string; monto_final: string; monto_pendiente: string; fecha_vencimiento: string; estado_pago: string };
type Payment = { id_pago: number; fecha_pago: string; monto: string; metodo_pago: string; nro_comprobante: string | null; cuotas: { id_cuota: number; concepto: string }[] };
type Material = { id_material: number; titulo: string; tipo: string; url: string; curso?: { nombre_curso: string } };
type Announcement = { id_anuncio: number; titulo: string; contenido: string; publicado_en: string; user?: { name: string } };
type Props = {
    estudiantes: StudentChoice[]; estudiante: Student; matriculas: Enrollment[]; matricula: Enrollment | null;
    horario: Schedule[]; anuncios: Announcement[]; materiales: Material[]; asistencias: Attendance[];
    notas: Note[]; simulacros: ExamResult[]; cuotas: Fee[]; pagos: Payment[];
    resumen: { asistencia_porcentaje: number; pagado: string; deuda: string; promedio: number | null };
    esApoderado: boolean; diaSemanaHoy: number;
};

const panel = 'rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900';
const links = [
    ['Inicio', '#inicio'], ['Notas', '#notas'], ['Asistencia', '#asistencia'], ['Pagos', '#pagos'],
    ['Materiales', '#materiales'], ['Horario', '#horario'], ['Mis datos', '#datos'],
] as const;
const weekdays = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
const money = (amount: string | number) => `S/ ${Number(amount).toFixed(2)}`;
const date = (value: string) => new Intl.DateTimeFormat('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(`${value.slice(0, 10)}T12:00:00`));

export default function StudentDashboard(props: Props) {
    const { estudiante, matricula, estudiantes, horario, anuncios, materiales, asistencias, notas, simulacros, cuotas, pagos, resumen, esApoderado, diaSemanaHoy } = props;
    const gradesByCourse = useMemo(() => {
        const grouped = new Map<string, number[]>();
        notas.forEach((note) => {
            const course = note.evaluacion?.curso?.nombre_curso ?? 'Otros';
            grouped.set(course, [...(grouped.get(course) ?? []), Number(note.nota)]);
        });
        return [...grouped.entries()].map(([course, values]) => ({ course, average: values.reduce((total, value) => total + value, 0) / values.length }));
    }, [notas]);


    return (
        <AuthenticatedLayout>
            <main className="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <section id="inicio" className="overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-blue-800 to-indigo-950 p-6 text-white shadow-lg sm:p-9">
                    <div className="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
                        <div className="max-w-2xl">
                            <p className="text-sm font-semibold text-blue-200">Portal {esApoderado ? 'familiar' : 'del estudiante'}</p>
                            <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Hola, {estudiante.nombre}</h1>
                            <p className="mt-2 text-blue-100">Consulta tus clases, avances, asistencia y estado de cuenta desde un solo lugar.</p>
                            {esApoderado && <label className="mt-5 block max-w-sm text-sm font-medium">Estudiante
                                <select className="mt-1 min-h-11 w-full rounded-xl border border-white/20 bg-white/10 px-3 text-white outline-none focus:ring-2 focus:ring-white/60" value={estudiante.id_estudiante} onChange={(event) => router.get(route('mi.index'), { id_estudiante: event.target.value }, { preserveScroll: true })}>
                                    {estudiantes.map((child) => <option className="text-slate-900" key={child.id_estudiante} value={child.id_estudiante}>{child.nombre_completo}</option>)}
                                </select>
                            </label>}
                            <p className="mt-4 text-sm text-blue-200">{matricula?.ciclo?.nombre_ciclo ?? 'Sin matrícula activa'}{matricula?.grupo ? ` · Grupo ${matricula.grupo.nombre}` : ''}</p>
                        </div>
                        <div className="flex w-full max-w-xs items-center gap-4 rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                            <div className="grid size-24 shrink-0 place-items-center rounded-xl bg-white p-2">
                                <QRCodeSVG value={estudiante.codigo_qr} size={96} level="H" title={`Código QR de carnet de ${estudiante.nombre} ${estudiante.apellido}`} className="size-full" />
                            </div>
                            <div className="min-w-0"><p className="font-semibold">Carnet digital</p><p className="mt-1 text-xs text-blue-100">Muestra este QR al ingresar a la academia.</p><p className="mt-2 break-all font-mono text-[10px] text-blue-200">{estudiante.codigo_qr}</p></div>
                        </div>
                    </div>
                    <nav className="mt-7 flex gap-2 overflow-x-auto pb-1" aria-label="Secciones del portal">
                        {links.map(([label, href]) => <a key={href} href={href} className="shrink-0 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/20">{label}</a>)}
                    </nav>
                </section>

                <section className="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Resumen académico">
                    <SummaryCard icon={Award} label="Promedio" value={resumen.promedio === null ? '—' : resumen.promedio.toFixed(2)} tone="violet" />
                    <SummaryCard icon={CalendarDays} label="Asistencia" value={`${resumen.asistencia_porcentaje}%`} tone="cyan" />
                    <SummaryCard icon={CreditCard} label="Deuda pendiente" value={money(resumen.deuda)} tone="amber" />
                    <SummaryCard icon={BookOpen} label="Materiales" value={materiales.length} tone="emerald" />
                </section>

                <div className="grid gap-5 xl:grid-cols-2">
                    <section className={panel}>
                        <SectionTitle icon={CalendarDays} title="Clases de hoy" />
                        {matricula && horario.filter((classItem) => classItem.dia_semana === diaSemanaHoy).length > 0 ? <div className="mt-4 space-y-3">
                            {horario.filter((classItem) => classItem.dia_semana === diaSemanaHoy).map((classItem) => <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-indigo-50 p-4 shadow-sm dark:border-blue-300/10 dark:from-blue-950/60 dark:via-slate-900 dark:to-indigo-950/50" key={classItem.id_horario}><div><p className="font-semibold text-slate-900 dark:text-white">{classItem.curso?.nombre_curso}</p><p className="text-sm text-slate-500">{classItem.profesor?.nombre} {classItem.profesor?.apellido} · {classItem.aula?.nombre}</p></div><p className="shrink-0 text-sm font-medium text-blue-700 dark:text-blue-300">{classItem.hora_inicio.slice(0, 5)}–{classItem.hora_fin.slice(0, 5)}</p></div>)}
                        </div> : <EmptyMessage text="No tienes clases programadas para hoy." />}
                    </section>
                    <section className={panel}>
                        <SectionTitle icon={FileText} title="Avisos" />
                        {anuncios.length ? <div className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">{anuncios.slice(0, 5).map((notice) => <article key={notice.id_anuncio} className="py-3 first:pt-0"><h3 className="font-semibold text-slate-900 dark:text-white">{notice.titulo}</h3><p className="mt-1 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{notice.contenido}</p><p className="mt-2 text-xs text-slate-400">{date(notice.publicado_en)}{notice.user?.name ? ` · ${notice.user.name}` : ''}</p></article>)}</div> : <EmptyMessage text="Todavía no hay avisos para tu grupo." />}
                    </section>
                </div>

                <section id="notas" className={panel}>
                    <SectionTitle icon={Award} title="Mis notas y simulacros" />
                    {gradesByCourse.length > 0 && <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{gradesByCourse.map(({ course, average }) => <div key={course} className="rounded-2xl border border-blue-200/80 bg-gradient-to-br from-blue-50 via-white to-indigo-100/80 p-4 shadow-sm dark:border-blue-300/10 dark:from-blue-950/70 dark:via-slate-900 dark:to-indigo-950/60"><p className="text-sm text-slate-600 dark:text-slate-300">{course}</p><p className="mt-1 text-2xl font-bold text-blue-800 dark:text-blue-200">{average.toFixed(2)}</p></div>)}</div>}
                    <div className="mt-4 grid gap-5 lg:grid-cols-2">
                        <div><h3 className="text-sm font-semibold text-slate-700 dark:text-slate-200">Evaluaciones</h3>{notas.length ? <div className="mt-2 divide-y divide-slate-100 dark:divide-slate-800">{notas.map((note) => <div key={note.id_nota} className="flex justify-between gap-4 py-3 text-sm"><div><p className="font-medium">{note.evaluacion?.titulo ?? 'Evaluación'} · {note.evaluacion?.curso?.nombre_curso}</p><p className="text-slate-500">{note.evaluacion?.fecha ? date(note.evaluacion.fecha) : ''}</p></div><p className="font-bold text-slate-900 dark:text-white">{note.nota}<span className="font-normal text-slate-500"> / {note.evaluacion?.nota_maxima ?? '20'}</span></p></div>)}</div> : <EmptyMessage text="Aún no tienes evaluaciones registradas." />}</div>
                        <div><h3 className="text-sm font-semibold text-slate-700 dark:text-slate-200">Simulacros</h3>{simulacros.length ? <div className="mt-2 space-y-2">{simulacros.map((result) => <div key={result.id_resultado} className="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-blue-50 p-4 shadow-sm dark:border-indigo-300/10 dark:from-indigo-950/60 dark:via-slate-900 dark:to-blue-950/50"><div className="flex flex-wrap items-center justify-between gap-3"><p className="font-medium">{result.simulacro?.titulo}</p><p className="font-bold text-blue-700 dark:text-blue-300">{result.puntaje_obtenido} pts</p></div><p className="mt-1 text-xs text-slate-500">Puesto general: {result.puesto_general ?? 'En proceso'} · Carrera: {result.puesto_carrera ?? '—'}</p></div>)}</div> : <EmptyMessage text="Tus resultados de simulacros aparecerán aquí." />}</div>
                    </div>
                </section>

                <div className="grid gap-5 xl:grid-cols-2">
                    <section id="asistencia" className={panel}>
                        <SectionTitle icon={CalendarDays} title="Mi asistencia" subtitle={`${resumen.asistencia_porcentaje}% de asistencia`} />
                        {asistencias.length ? <div className="mt-4 max-h-80 divide-y divide-slate-100 overflow-auto dark:divide-slate-800">{asistencias.map((entry) => <div key={entry.id_asistencia} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm"><div><p className="font-medium">{entry.horario?.curso?.nombre_curso ?? 'Clase'}</p><p className="text-slate-500">{date(entry.fecha)}</p></div><span className={`rounded-full px-3 py-1 text-xs font-semibold ${entry.estado === 'Presente' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' : entry.estado === 'Tarde' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200'}`}>{entry.estado}</span></div>)}</div> : <EmptyMessage text="Aún no hay registros de asistencia." />}
                    </section>
                    <section id="pagos" className={panel}>
                        <SectionTitle icon={CreditCard} title="Mis pagos" subtitle={`Pagado ${money(resumen.pagado)} · Saldo ${money(resumen.deuda)}`} />
                        <h3 className="mt-4 text-sm font-semibold text-slate-700 dark:text-slate-200">Cuotas</h3>
                        {cuotas.length ? <div className="mt-2 divide-y divide-slate-100 dark:divide-slate-800">{cuotas.map((fee) => <div key={fee.id_cuota} className="flex flex-wrap items-center justify-between gap-2 py-3 text-sm"><div><p className="font-medium">{fee.concepto}</p><p className="text-slate-500">Vence {date(fee.fecha_vencimiento)} · {fee.estado_pago}</p></div><p className="font-semibold">{money(fee.monto_pendiente)}</p></div>)}</div> : <EmptyMessage text="No hay cuotas asociadas a esta matrícula." />}
                        <h3 className="mt-5 text-sm font-semibold text-slate-700 dark:text-slate-200">Historial reciente</h3>
                        {pagos.length ? <div className="mt-2 divide-y divide-slate-100 dark:divide-slate-800">{pagos.map((payment) => <div key={payment.id_pago} className="flex justify-between gap-3 py-3 text-sm"><div><p className="font-medium">{payment.nro_comprobante ?? `Pago #${payment.id_pago}`}</p><p className="text-slate-500">{date(payment.fecha_pago)} · {payment.metodo_pago}</p><Link href={route('mi.pagos.comprobante', payment.id_pago)} className="mt-1 inline-flex text-xs font-semibold text-blue-700 hover:underline dark:text-blue-300">Ver comprobante</Link></div><p className="font-semibold">{money(payment.monto)}</p></div>)}</div> : <EmptyMessage text="Aún no tienes pagos registrados." />}
                    </section>
                </div>

                <div className="grid gap-5 xl:grid-cols-2">
                    <section id="materiales" className={panel}>
                        <SectionTitle icon={BookOpen} title="Material de estudio" />
                        {materiales.length ? <div className="mt-4 grid gap-3 sm:grid-cols-2">{materiales.map((material) => <a key={material.id_material} href={material.url} target="_blank" rel="noreferrer" className="rounded-2xl border border-blue-100 bg-gradient-to-br from-white via-blue-50 to-indigo-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md dark:border-blue-300/10 dark:from-slate-900 dark:via-blue-950/40 dark:to-indigo-950/50 dark:hover:border-blue-400/30"><p className="font-semibold">{material.titulo}</p><p className="mt-1 text-sm text-slate-500">{material.curso?.nombre_curso} · {material.tipo}</p></a>)}</div> : <EmptyMessage text="Todavía no hay materiales para tus cursos." />}
                    </section>
                    <section id="horario" className={panel}>
                        <SectionTitle icon={Clock3} title="Mi horario semanal" />
                        {horario.length ? <div className="mt-4 space-y-3">{horario.map((classItem) => <div key={classItem.id_horario} className="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-3 rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-indigo-50 p-4 dark:border-blue-300/10 dark:from-blue-950/60 dark:via-slate-900 dark:to-indigo-950/50"><p className="text-sm font-semibold text-blue-700 dark:text-blue-300">{weekdays[classItem.dia_semana]}</p><div><p className="font-medium">{classItem.curso?.nombre_curso}</p><p className="text-sm text-slate-500">{classItem.hora_inicio.slice(0, 5)}–{classItem.hora_fin.slice(0, 5)} · {classItem.aula?.nombre}</p></div></div>)}</div> : <EmptyMessage text="No hay horarios publicados para tu grupo." />}
                    </section>
                </div>

                <section id="datos" className={panel}>
                    <SectionTitle icon={UserRound} title="Mis datos" />
                    <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{[['Nombre', `${estudiante.nombre} ${estudiante.apellido}`], ['DNI', estudiante.dni], ['Correo', estudiante.email ?? '—'], ['Teléfono', estudiante.telefono ?? '—']].map(([label, value]) => <div key={label}><p className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</p><p className="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{value}</p></div>)}</div>
                    <div className="mt-5 flex flex-wrap gap-3"><Link href={route('profile.edit')} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Cambiar contraseña o correo</Link>{esApoderado && <span className="inline-flex items-center gap-2 text-sm text-slate-500"><Heart className="size-4" />Información del estudiante seleccionado</span>}</div>
                </section>
            </main>
        </AuthenticatedLayout>
    );
}

function SummaryCard({ icon: Icon, label, value, tone = 'blue' }: { icon: typeof Award; label: string; value: string | number; tone?: 'blue' | 'cyan' | 'violet' | 'amber' | 'emerald' }) {
    const tones = {
        blue: { surface: 'border-blue-200/80 from-white via-blue-50 to-indigo-100/90 dark:border-blue-300/15 dark:from-[#0b1730] dark:via-[#111d3b] dark:to-[#17285a]', stripe: 'before:from-cyan-400 before:via-blue-600 before:to-violet-500', glow: 'bg-blue-400/35 group-hover:bg-blue-500/45 dark:bg-blue-500/20 dark:group-hover:bg-blue-500/35', icon: 'from-cyan-400 via-blue-600 to-indigo-700 dark:from-cyan-400 dark:via-blue-500 dark:to-indigo-600' },
        cyan: { surface: 'border-cyan-200/80 from-white via-cyan-50 to-teal-100/90 dark:border-cyan-300/15 dark:from-[#0a1c2a] dark:via-[#0b2938] dark:to-[#104355]', stripe: 'before:from-teal-300 before:via-cyan-500 before:to-blue-600', glow: 'bg-cyan-400/35 group-hover:bg-cyan-500/45 dark:bg-cyan-500/20 dark:group-hover:bg-cyan-500/35', icon: 'from-teal-400 via-cyan-600 to-blue-700 dark:from-teal-300 dark:via-cyan-500 dark:to-blue-600' },
        violet: { surface: 'border-violet-200/80 from-white via-violet-50 to-fuchsia-100/90 dark:border-violet-300/15 dark:from-[#1a1230] dark:via-[#281544] dark:to-[#431c5b]', stripe: 'before:from-violet-300 before:via-purple-500 before:to-fuchsia-600', glow: 'bg-violet-400/35 group-hover:bg-violet-500/45 dark:bg-violet-500/20 dark:group-hover:bg-violet-500/35', icon: 'from-violet-400 via-purple-600 to-fuchsia-700 dark:from-violet-300 dark:via-purple-500 dark:to-fuchsia-600' },
        amber: { surface: 'border-amber-200/80 from-white via-amber-50 to-orange-100/90 dark:border-amber-300/15 dark:from-[#26190c] dark:via-[#39200d] dark:to-[#54270e]', stripe: 'before:from-yellow-300 before:via-amber-500 before:to-rose-500', glow: 'bg-amber-400/35 group-hover:bg-orange-500/45 dark:bg-amber-500/20 dark:group-hover:bg-orange-500/35', icon: 'from-amber-400 via-orange-500 to-rose-600 dark:from-amber-300 dark:via-orange-500 dark:to-rose-500' },
        emerald: { surface: 'border-emerald-200/80 from-white via-emerald-50 to-teal-100/90 dark:border-emerald-300/15 dark:from-[#0b211b] dark:via-[#103329] dark:to-[#145146]', stripe: 'before:from-lime-300 before:via-emerald-500 before:to-cyan-500', glow: 'bg-emerald-400/35 group-hover:bg-teal-500/45 dark:bg-emerald-500/20 dark:group-hover:bg-teal-500/35', icon: 'from-emerald-400 via-teal-500 to-cyan-700 dark:from-emerald-300 dark:via-teal-500 dark:to-cyan-600' },
    }[tone];

    return <article className={`group relative isolate overflow-hidden rounded-[2rem] border bg-gradient-to-br p-5 shadow-[0_16px_40px_rgba(37,99,235,0.12)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_22px_48px_rgba(37,99,235,0.2)] before:absolute before:bottom-7 before:left-0 before:top-7 before:w-1 before:rounded-r-full before:bg-gradient-to-b before:content-[''] motion-reduce:transform-none motion-reduce:transition-none ${tones.surface} ${tones.stripe}`}><div aria-hidden="true" className={`absolute -right-8 -top-10 -z-10 size-36 rounded-full blur-3xl transition-colors ${tones.glow}`} /><span className={`grid size-11 place-items-center rounded-2xl bg-gradient-to-br text-white shadow-lg shadow-blue-700/25 ring-1 ring-inset ring-white/35 transition duration-300 group-hover:scale-110 group-hover:rotate-3 ${tones.icon}`}><Icon aria-hidden="true" className="size-5" /></span><p className="mt-4 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-blue-200/70">{label}</p><p className="mt-2 break-words text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl">{value}</p></article>;
}

function SectionTitle({ icon: Icon, title, subtitle }: { icon: typeof Award; title: string; subtitle?: string }) {
    return <div className="flex items-center gap-3"><span className="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200"><Icon className="size-5" /></span><div><h2 className="font-semibold text-slate-900 dark:text-white">{title}</h2>{subtitle && <p className="text-xs text-slate-500">{subtitle}</p>}</div></div>;
}

function EmptyMessage({ text }: { text: string }) {
    return <p className="mt-4 rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700">{text}</p>;
}
