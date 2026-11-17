import { Link, router, useForm } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { Award, ClipboardList, RefreshCw, Save } from 'lucide-react';
import { useEffect } from 'react';
import EmptyState from '@/Components/EmptyState';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { formatDate } from '@/lib/format';

type Evaluation = { id_evaluacion: number; id_grupo: number; id_curso: number; titulo: string; tipo: string; fecha: string; nota_maxima: string | number; peso: string | number; notas_count: number; grupo?: { nombre: string; ciclo?: { nombre_ciclo: string } }; curso?: { nombre_curso: string } };
type StudentRow = { id_matricula: number; estudiante: { nombre: string; apellido: string; dni: string }; nota: string | number | null; observacion: string | null };
type GroupCourse = { id_grupo: number; grupo: string; ciclo: string; id_curso: number; curso: string };
type Props = { evaluaciones: Evaluation[]; evaluacionActual: Evaluation | null; estudiantes: StudentRow[]; grupos: GroupCourse[] };
type GradeForm = { notas: { id_matricula: number; nota: string; observacion: string }[] };
const inputClass = 'mt-1 min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-950';

export default function NotasIndex({ evaluaciones, evaluacionActual, estudiantes, grupos }: Props) {
    const form = useForm<GradeForm>({ notas: [] });
    useEffect(() => {
        form.setData('notas', estudiantes.map((row) => ({
            id_matricula: row.id_matricula,
            nota: row.nota === null ? '' : String(row.nota),
            observacion: row.observacion ?? '',
        })));
    // A new evaluation replaces the grade roster and its initial values.
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [evaluacionActual?.id_evaluacion, estudiantes]);

    function updateRow(index: number, field: 'nota' | 'observacion', value: string) {
        form.setData('notas', form.data.notas.map((row, rowIndex) => rowIndex === index ? { ...row, [field]: value } : row));
    }

    return (
        <PageShell title="Notas y evaluaciones" description="Crea evaluaciones por grupo y registra las calificaciones de tus estudiantes.">
            <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <div className="mb-4 flex items-center gap-3"><div className="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300"><Award className="size-5" /></div><div><h2 className="font-semibold text-slate-900 dark:text-white">Crear evaluación</h2><p className="text-sm text-slate-500">El grupo y el curso deben corresponder a un horario asignado.</p></div></div>
                {grupos.length === 0 ? <EmptyState title="Sin cursos asignados" description="Pide a administración que te asigne un horario antes de crear evaluaciones." /> : <Form action={route('evaluaciones.store')} method="post" className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {({ errors, processing }) => <>
                        <div className="sm:col-span-2"><InputLabel htmlFor="titulo" value="Nombre de la evaluación" /><input id="titulo" name="titulo" required maxLength={150} className={inputClass} placeholder="Ej. Práctica de funciones" /><InputError message={errors.titulo} /></div>
                        <div><InputLabel htmlFor="id_grupo" value="Grupo" /><select id="id_grupo" name="id_grupo" required className={inputClass}><option value="">Selecciona grupo</option>{[...new Map(grupos.map((item) => [item.id_grupo, item])).values()].map((item) => <option key={item.id_grupo} value={item.id_grupo}>{item.grupo} · {item.ciclo}</option>)}</select><InputError message={errors.id_grupo} /></div>
                        <div><InputLabel htmlFor="id_curso" value="Curso" /><select id="id_curso" name="id_curso" required className={inputClass}><option value="">Selecciona curso</option>{[...new Map(grupos.map((item) => [item.id_curso, item])).values()].map((item) => <option key={item.id_curso} value={item.id_curso}>{item.curso}</option>)}</select><InputError message={errors.id_curso} /></div>
                        <div><InputLabel htmlFor="tipo" value="Tipo" /><select id="tipo" name="tipo" className={inputClass}><option>Práctica</option><option>Examen</option><option>Tarea</option><option>Otro</option></select></div>
                        <div><InputLabel htmlFor="fecha" value="Fecha" /><input id="fecha" name="fecha" type="date" required defaultValue={new Date().toISOString().slice(0, 10)} className={inputClass} /><InputError message={errors.fecha} /></div>
                        <div><InputLabel htmlFor="nota_maxima" value="Nota máxima" /><input id="nota_maxima" name="nota_maxima" type="number" min="0.01" max="1000" step="0.01" defaultValue="20" required className={inputClass} /><InputError message={errors.nota_maxima} /></div>
                        <div><InputLabel htmlFor="peso" value="Peso" /><input id="peso" name="peso" type="number" min="0.01" max="100" step="0.01" defaultValue="1" required className={inputClass} /><InputError message={errors.peso} /></div>
                        <div className="flex items-end"><PrimaryButton disabled={processing}>{processing ? 'Guardando…' : 'Crear evaluación'}</PrimaryButton></div>
                    </>}
                </Form>}
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h2 className="font-semibold text-slate-900 dark:text-white">Evaluaciones recientes</h2><p className="text-sm text-slate-500">Selecciona una para revisar o registrar notas.</p></div><SecondaryButton type="button" onClick={() => router.reload({ only: ['evaluaciones', 'evaluacionActual', 'estudiantes'] })}><RefreshCw className="mr-2 size-4" />Actualizar</SecondaryButton></div>
                {evaluaciones.length === 0 ? <EmptyState title="Aún no hay evaluaciones" description="Crea una evaluación para cargar notas en el grupo." /> : <div className="mb-6 grid gap-3 lg:grid-cols-2">{evaluaciones.map((item) => <Link key={item.id_evaluacion} href={route('notas.index', { id_evaluacion: item.id_evaluacion })} className={`rounded-xl border p-4 transition hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-slate-800 ${evaluacionActual?.id_evaluacion === item.id_evaluacion ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200 dark:border-slate-700'}`}><div className="flex flex-wrap items-center justify-between gap-3"><h3 className="font-semibold text-slate-900 dark:text-white">{item.titulo}</h3><span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{item.notas_count} notas</span></div><p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{item.grupo?.nombre} · {item.curso?.nombre_curso}</p><p className="mt-1 text-xs text-slate-500">{formatDate(item.fecha)} · {item.tipo} · Máximo {item.nota_maxima}</p></Link>)}</div>}

                {evaluacionActual && <div className="border-t border-slate-100 pt-5 dark:border-slate-800"><div className="mb-4 flex items-center gap-3"><ClipboardList className="size-5 text-blue-700 dark:text-blue-300" /><div><h3 className="font-semibold">Notas · {evaluacionActual.titulo}</h3><p className="text-sm text-slate-500">Máximo {evaluacionActual.nota_maxima} · {estudiantes.length} estudiantes inscritos</p></div></div>
                    {estudiantes.length === 0 ? <EmptyState title="Sin estudiantes inscritos" description="Este grupo no tiene matrículas activas para la fecha de la evaluación." /> : <form onSubmit={(event) => { event.preventDefault(); form.put(route('evaluaciones.notas.store', evaluacionActual.id_evaluacion), { preserveScroll: true }); }}>
                        <div className="space-y-3">{estudiantes.map((student, index) => <div key={student.id_matricula} className="grid gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700 sm:grid-cols-[minmax(0,1.2fr)_8rem_minmax(0,1fr)] sm:items-center"><div className="min-w-0"><p className="truncate font-medium text-slate-900 dark:text-white">{student.estudiante.nombre} {student.estudiante.apellido}</p><p className="text-xs text-slate-500">DNI {student.estudiante.dni}</p></div><div><label htmlFor={`nota-${student.id_matricula}`} className="text-xs font-medium text-slate-600 dark:text-slate-300">Nota / {evaluacionActual.nota_maxima}</label><input id={`nota-${student.id_matricula}`} type="number" min="0" max={evaluacionActual.nota_maxima} step="0.01" value={form.data.notas[index]?.nota ?? ''} onChange={(event) => updateRow(index, 'nota', event.target.value)} className="mt-1 min-h-10 w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError message={form.errors[`notas.${index}.nota` as keyof typeof form.errors]} /></div><div><label htmlFor={`observacion-${student.id_matricula}`} className="text-xs font-medium text-slate-600 dark:text-slate-300">Observación (opcional)</label><input id={`observacion-${student.id_matricula}`} maxLength={200} value={form.data.notas[index]?.observacion ?? ''} onChange={(event) => updateRow(index, 'observacion', event.target.value)} className="mt-1 min-h-10 w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950" /><InputError message={form.errors[`notas.${index}.observacion` as keyof typeof form.errors]} /></div></div>)}</div>
                        <div className="mt-5 flex justify-end"><PrimaryButton disabled={form.processing}>{form.processing ? 'Guardando…' : <><Save className="mr-2 size-4" />Guardar notas</>}</PrimaryButton></div><InputError message={form.errors.notas} className="mt-2" />
                    </form>}
                </div>}
            </section>
        </PageShell>
    );
}
