import { router, useForm } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { Calendar, Clock, MapPin, Plus, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';
import EmptyState from '@/Components/EmptyState';
import FormModal from '@/Components/FormModal';
import InputError from '@/Components/InputError';
import PageShell from '@/Components/PageShell';
import Pagination from '@/Components/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatCard from '@/Components/StatCard';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/Table';
import { Aula, Curso, Grupo, Horario, Paginated, Profesor } from '@/types/academia';

type Props = {
    horarios: Paginated<Horario>;
    filters: { dia?: string; id_grupo?: string };
    cursos: Curso[];
    grupos: Grupo[];
    profesores: Profesor[];
    aulas: Aula[];
    stats: { total: number; aulas: number; cursos: number };
};

const dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

export default function HorariosIndex({ horarios, filters, cursos, grupos, profesores, aulas, stats }: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        id_grupo: '', id_curso: '', id_profesor: '', id_aula: '', dia_semana: '1', hora_inicio: '', hora_fin: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(route('horarios.store'), {
            preserveScroll: true,
            onSuccess: () => { reset(); setModalOpen(false); },
        });
    };

    return (
        <PageShell title="Horarios" description="Programa clases y detecta cruces de docentes y aulas."
            actions={<PrimaryButton onClick={() => setModalOpen(true)}><Plus className="mr-1 size-4" />Nuevo horario</PrimaryButton>}>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard title="Total horarios" value={stats.total} icon={Calendar} />
                <StatCard title="Aulas asignadas" value={stats.aulas} icon={MapPin} />
                <StatCard title="Cursos" value={stats.cursos} icon={Clock} />
            </div>

            <section className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <Form action={route('horarios.index')} method="get" className="mb-4 flex flex-wrap gap-3">
                    <select name="dia" defaultValue={filters.dia ?? ''} aria-label="Filtrar por día" className="rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">Todos los días</option>
                        {dias.map((dia, index) => <option key={dia} value={index + 1}>{dia}</option>)}
                    </select>
                    <select name="id_grupo" defaultValue={filters.id_grupo ?? ''} aria-label="Filtrar por grupo" className="rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">Todos los grupos</option>
                        {grupos.map((grupo) => <option key={grupo.id_grupo} value={grupo.id_grupo}>{grupo.nombre} · {grupo.ciclo?.nombre_ciclo}</option>)}
                    </select>
                    <SecondaryButton type="submit">Filtrar</SecondaryButton>
                </Form>

                {horarios.data.length === 0 ? (
                    <EmptyState title="Sin horarios" description="Crea un horario para uno de los grupos disponibles." />
                ) : (
                    <>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader><TableRow><TableHead>Curso y grupo</TableHead><TableHead>Docente</TableHead><TableHead>Día / hora</TableHead><TableHead>Aula</TableHead><TableHead className="text-right">Acciones</TableHead></TableRow></TableHeader>
                                <TableBody>{horarios.data.map((horario) => (
                                    <TableRow key={horario.id_horario}>
                                        <TableCell><p className="font-medium text-gray-900 dark:text-white">{horario.curso?.nombre_curso ?? '—'}</p><p className="text-xs text-gray-500">{horario.grupo?.nombre ?? '—'} · {horario.grupo?.ciclo?.nombre_ciclo ?? '—'}</p></TableCell>
                                        <TableCell>{horario.profesor ? `${horario.profesor.nombre} ${horario.profesor.apellido}` : '—'}</TableCell>
                                        <TableCell><p className="font-medium">{dias[horario.dia_semana - 1] ?? '—'}</p><p className="flex items-center gap-1 text-xs text-gray-500"><Clock className="size-3" />{horario.hora_inicio}–{horario.hora_fin}</p></TableCell>
                                        <TableCell>{horario.aula?.nombre ?? '—'}</TableCell>
                                        <TableCell><div className="flex justify-end"><button type="button" aria-label="Eliminar horario" className="rounded-lg p-2 text-red-600 hover:bg-red-50" onClick={() => { if (confirm('¿Eliminar este horario?')) router.delete(route('horarios.destroy', horario.id_horario)); }}><Trash2 className="size-4" /></button></div></TableCell>
                                    </TableRow>
                                ))}</TableBody>
                            </Table>
                        </div>
                        <div className="mt-4"><Pagination links={horarios.meta.links} /></div>
                    </>
                )}
            </section>

            <FormModal show={modalOpen} title="Nuevo horario" onClose={() => setModalOpen(false)}>
                <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
                    <label className="grid gap-1 text-sm">Grupo<select required value={data.id_grupo} onChange={(event) => setData('id_grupo', event.target.value)} className="rounded-lg border-gray-300"><option value="">Seleccionar</option>{grupos.map((grupo) => <option key={grupo.id_grupo} value={grupo.id_grupo}>{grupo.nombre} · {grupo.ciclo?.nombre_ciclo}</option>)}</select><InputError message={errors.id_grupo} /></label>
                    <label className="grid gap-1 text-sm">Curso<select required value={data.id_curso} onChange={(event) => setData('id_curso', event.target.value)} className="rounded-lg border-gray-300"><option value="">Seleccionar</option>{cursos.map((curso) => <option key={curso.id_curso} value={curso.id_curso}>{curso.nombre_curso}</option>)}</select><InputError message={errors.id_curso} /></label>
                    <label className="grid gap-1 text-sm">Docente<select required value={data.id_profesor} onChange={(event) => setData('id_profesor', event.target.value)} className="rounded-lg border-gray-300"><option value="">Seleccionar</option>{profesores.map((profesor) => <option key={profesor.id_profesor} value={profesor.id_profesor}>{profesor.nombre} {profesor.apellido}</option>)}</select><InputError message={errors.id_profesor} /></label>
                    <label className="grid gap-1 text-sm">Aula<select required value={data.id_aula} onChange={(event) => setData('id_aula', event.target.value)} className="rounded-lg border-gray-300"><option value="">Seleccionar</option>{aulas.map((aula) => <option key={aula.id_aula} value={aula.id_aula}>{aula.nombre}</option>)}</select><InputError message={errors.id_aula} /></label>
                    <label className="grid gap-1 text-sm">Día<select value={data.dia_semana} onChange={(event) => setData('dia_semana', event.target.value)} className="rounded-lg border-gray-300">{dias.map((dia, index) => <option key={dia} value={index + 1}>{dia}</option>)}</select><InputError message={errors.dia_semana} /></label>
                    <label className="grid gap-1 text-sm">Hora de inicio<input type="time" required value={data.hora_inicio} onChange={(event) => setData('hora_inicio', event.target.value)} className="rounded-lg border-gray-300" /><InputError message={errors.hora_inicio} /></label>
                    <label className="grid gap-1 text-sm">Hora de fin<input type="time" required value={data.hora_fin} onChange={(event) => setData('hora_fin', event.target.value)} className="rounded-lg border-gray-300" /><InputError message={errors.hora_fin} /></label>
                    {Object.values(errors).length > 0 && <p role="alert" className="text-sm text-red-600 sm:col-span-2">Revisa los campos marcados y los posibles cruces de horario.</p>}
                    <div className="flex justify-end gap-2 sm:col-span-2"><SecondaryButton type="button" onClick={() => setModalOpen(false)}>Cancelar</SecondaryButton><PrimaryButton disabled={processing}>{processing ? 'Guardando…' : 'Guardar horario'}</PrimaryButton></div>
                </form>
            </FormModal>
        </PageShell>
    );
}
