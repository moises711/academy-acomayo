import { Link, useForm } from '@inertiajs/react';
import { Award, ArrowLeft, Medal, Users } from 'lucide-react';
import PageShell from '@/Components/PageShell';
import StatCard from '@/Components/StatCard';
import EmptyState from '@/Components/EmptyState';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/Table';
import { ResultadoSimulacro, Simulacro } from '@/types/academia';
import { formatDate } from '@/lib/format';

type MatriculaOption = {
    id_matricula: number;
    estudiante: { nombre: string; apellido: string; dni: string };
};

type Props = {
    simulacro: Simulacro;
    resultados: ResultadoSimulacro[];
    matriculas: MatriculaOption[];
    stats: { promedio: number; maximo: number; rezagados: number; total: number };
};

export default function SimulacroResultados({ simulacro, resultados, matriculas, stats }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        id_simulacro: simulacro.id_simulacro,
        id_matricula: '',
        puntaje_obtenido: '',
        es_rezagado: false,
    });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(route('resultados.store'), {
            preserveScroll: true,
            onSuccess: () => reset('id_matricula', 'puntaje_obtenido', 'es_rezagado'),
        });
    };

    return (
        <PageShell
            title={`Resultados · ${simulacro.titulo}`}
            description={`${simulacro.ciclo?.nombre_ciclo ?? 'Ciclo académico'} · aplicación ${formatDate(simulacro.fecha_aplicacion)}`}
            actions={<Link href={route('simulacros.show', simulacro.id_simulacro)}><SecondaryButton><ArrowLeft className="mr-2 size-4" />Volver al simulacro</SecondaryButton></Link>}
        >
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard title="Evaluados" value={stats.total} icon={Users} />
                <StatCard title="Promedio" value={stats.promedio.toFixed(2)} icon={Award} />
                <StatCard title="Puntaje máximo logrado" value={stats.maximo.toFixed(2)} icon={Medal} />
                <StatCard title="Rezagados" value={stats.rezagados} icon={Users} />
            </div>

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Registrar o corregir un resultado</h2>
                <form onSubmit={submit} className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 xl:items-end">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="id_matricula" value="Estudiante matriculado en este ciclo" />
                        <select id="id_matricula" value={data.id_matricula} onChange={(event) => setData('id_matricula', event.target.value)} className="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950" required>
                            <option value="">Selecciona un estudiante</option>
                            {matriculas.map((matricula) => <option key={matricula.id_matricula} value={matricula.id_matricula}>{matricula.estudiante.apellido}, {matricula.estudiante.nombre} · DNI {matricula.estudiante.dni}</option>)}
                        </select>
                        <InputError message={errors.id_matricula} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="puntaje_obtenido" value={`Puntaje (máximo ${simulacro.puntaje_maximo})`} />
                        <TextInput id="puntaje_obtenido" type="number" name="puntaje_obtenido" min="0" max={simulacro.puntaje_maximo} step="0.01" value={data.puntaje_obtenido} onChange={(event) => setData('puntaje_obtenido', event.target.value)} className="mt-1 block w-full" required />
                        <InputError message={errors.puntaje_obtenido} className="mt-1" />
                    </div>
                    <div className="flex items-center gap-3">
                        <input id="es_rezagado" type="checkbox" checked={data.es_rezagado} onChange={(event) => setData('es_rezagado', event.target.checked)} className="size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600" />
                        <InputLabel htmlFor="es_rezagado" value="Rindió en fecha rezagada" />
                    </div>
                    <div className="xl:col-span-4"><PrimaryButton disabled={processing || matriculas.length === 0}>{processing ? 'Guardando…' : 'Guardar resultado'}</PrimaryButton></div>
                </form>
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Tabla de posiciones</h2>
                {resultados.length === 0 ? <EmptyState title="Todavía no hay resultados" description="Registra puntajes o procesa las hojas OMR para mostrar el ranking." /> : (
                    <div className="overflow-x-auto"><Table>
                        <TableHeader><TableRow><TableHead>Puesto</TableHead><TableHead>Estudiante</TableHead><TableHead>Grupo</TableHead><TableHead>Puntaje</TableHead><TableHead>Carrera</TableHead><TableHead>Estado</TableHead></TableRow></TableHeader>
                        <TableBody>{resultados.map((resultado) => <TableRow key={resultado.id_resultado}>
                            <TableCell className="font-semibold">{resultado.puesto_general ?? 'Pendiente'}</TableCell>
                            <TableCell><p className="font-medium">{resultado.matricula?.estudiante?.apellido}, {resultado.matricula?.estudiante?.nombre}</p><p className="text-xs text-slate-500">DNI {resultado.matricula?.estudiante?.dni}</p></TableCell>
                            <TableCell>{resultado.matricula?.grupo?.nombre ?? '—'}</TableCell>
                            <TableCell className="font-semibold text-blue-700 dark:text-blue-300">{resultado.puntaje_obtenido} / {simulacro.puntaje_maximo}</TableCell>
                            <TableCell>{resultado.matricula?.carrera?.nombre ?? '—'}</TableCell>
                            <TableCell>{resultado.es_rezagado ? 'Rezagado' : resultado.estado_ranking === 'procesado' ? `Puesto ${resultado.puesto_carrera ?? '—'} por carrera` : 'Ranking pendiente'}</TableCell>
                        </TableRow>)}</TableBody>
                    </Table></div>
                )}
            </section>
        </PageShell>
    );
}
