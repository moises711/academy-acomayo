import { Link, router } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { Award, Calendar, Plus, Search, Trophy } from 'lucide-react';
import PageShell from '@/Components/PageShell';
import StatCard from '@/Components/StatCard';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/Table';
import { Paginated, Simulacro } from '@/types/academia';
import { formatDate } from '@/lib/format';

type Props = {
    simulacros: Paginated<Simulacro>;
    filters: { search?: string };
    stats: { total: number; totalResultados: number; promedioPuntaje: number | string };
};

export default function SimulacrosIndex({ simulacros, filters, stats }: Props) {
    return (
        <PageShell
            title="Simulacros"
            description="Administra los exámenes de simulación y evaluación académica."
            actions={
                <Link href={route('simulacros.create')}>
                    <PrimaryButton>
                        <Plus className="mr-1 size-4" /> Nuevo simulacro
                    </PrimaryButton>
                </Link>
            }
        >
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard title="Total Simulacros" value={stats.total} icon={Calendar} />
                <StatCard title="Resultados Registrados" value={stats.totalResultados} icon={Trophy} />
                <StatCard title="Puntaje Promedio Global" value={stats.promedioPuntaje ?? '0'} icon={Award} />
            </div>

            <section className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <Form action={route('simulacros.index')} method="get" className="mb-4 flex flex-col gap-3 sm:flex-row">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-2.5 size-4 text-gray-400" />
                        <TextInput
                            name="search"
                            defaultValue={filters?.search ?? ''}
                            placeholder="Buscar por título de simulacro"
                            className="w-full pl-9"
                        />
                    </div>
                    <SecondaryButton type="submit">Filtrar</SecondaryButton>
                </Form>

                {simulacros.data.length === 0 ? (
                    <EmptyState
                        title="No hay simulacros"
                        description="Crea el primer simulacro de examen para evaluar a los alumnos."
                        action={
                            <Link href={route('simulacros.create')}>
                                <PrimaryButton>Registrar simulacro</PrimaryButton>
                            </Link>
                        }
                    />
                ) : (
                    <>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Título</TableHead>
                                    <TableHead>Ciclo</TableHead>
                                    <TableHead>Fecha de Aplicación</TableHead>
                                    <TableHead>Puntaje Máximo</TableHead>
                                    <TableHead>Resultados</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {simulacros.data.map((simulacro) => (
                                    <TableRow key={simulacro.id_simulacro}>
                                        <TableCell>
                                            <p className="font-medium text-gray-900">{simulacro.titulo}</p>
                                        </TableCell>
                                        <TableCell>
                                            {simulacro.ciclo?.nombre_ciclo ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(simulacro.fecha_aplicacion)}
                                        </TableCell>
                                        <TableCell className="font-semibold text-indigo-600">
                                            {simulacro.puntaje_maximo} pts
                                        </TableCell>
                                        <TableCell>
                                            <span className="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                                {simulacro.resultados_count ?? 0} notas
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route('simulacros.show', simulacro.id_simulacro)}
                                                    className="text-sm text-slate-700 hover:underline dark:text-slate-200"
                                                >
                                                    Ver
                                                </Link>
                                                <Link
                                                    href={route('simulacros.edit', simulacro.id_simulacro)}
                                                    className="text-sm text-slate-700 hover:underline dark:text-slate-200"
                                                >
                                                    Editar
                                                </Link>
                                                <Link
                                                    href={route('simulacros.resultados', simulacro.id_simulacro)}
                                                    className="text-sm text-blue-600 hover:underline"
                                                >
                                                    Resultados
                                                </Link>
                                                <button
                                                    type="button"
                                                    className="text-sm text-red-600 hover:underline"
                                                    onClick={() => {
                                                        if (confirm('¿Eliminar este simulacro?')) {
                                                            router.delete(route('simulacros.destroy', simulacro.id_simulacro));
                                                        }
                                                    }}
                                                >
                                                    Eliminar
                                                </button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <div className="mt-4">
                            <Pagination links={simulacros.meta.links} />
                        </div>
                    </>
                )}
            </section>
        </PageShell>
    );
}
