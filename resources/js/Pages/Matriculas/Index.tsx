import { Link, router } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { ClipboardList, Plus, Snowflake, UserMinus } from 'lucide-react';
import PageShell from '@/Components/PageShell';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/Table';
import { Matricula, Paginated } from '@/types/academia';
import { formatDate, fullName } from '@/lib/format';

type Props = {
    matriculas: Paginated<Matricula>;
    filters: { estado?: string };
    stats: { total: number; inscritos: number; retirados: number; congelados: number };
};

export default function MatriculasIndex({ matriculas, filters, stats }: Props) {
    return (
        <PageShell
            title="Matrículas"
            description="Inscripciones, grupos asignados y carreras objetivo."
            actions={
                <Link href={route('matriculas.create')}>
                    <PrimaryButton>
                        <Plus className="mr-1 size-4" /> Nueva matrícula
                    </PrimaryButton>
                </Link>
            }
        >
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard title="Total" value={stats.total} icon={ClipboardList} />
                <StatCard title="Inscritos" value={stats.inscritos} icon={ClipboardList} />
                <StatCard title="Retirados" value={stats.retirados} icon={UserMinus} />
                <StatCard title="Congelados" value={stats.congelados} icon={Snowflake} />
            </div>

            <section className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <Form action={route('matriculas.index')} method="get" className="mb-4 flex gap-3">
                    <select name="estado" defaultValue={filters.estado ?? ''} className="rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">Todos los estados</option>
                        <option value="inscrito">Inscrito</option>
                        <option value="retirado">Retirado</option>
                        <option value="congelado">Congelado</option>
                    </select>
                    <SecondaryButton type="submit">Filtrar</SecondaryButton>
                </Form>

                {matriculas.data.length === 0 ? (
                    <EmptyState title="Sin matrículas" description="Matricula un estudiante activo en un ciclo." />
                ) : (
                    <>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Estudiante</TableHead>
                                    <TableHead>Ciclo</TableHead>
                                    <TableHead>Grupo / Carrera</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {matriculas.data.map((matricula) => (
                                    <TableRow key={matricula.id_matricula}>
                                        <TableCell>{fullName(matricula.estudiante)}</TableCell>
                                        <TableCell>{matricula.ciclo?.nombre_ciclo ?? '—'}</TableCell>
                                        <TableCell>
                                            <p>{matricula.grupo?.nombre ?? 'Grupo pendiente'}</p>
                                            <p className="text-xs text-gray-500">{matricula.carrera?.nombre ?? 'Carrera pendiente'}</p>
                                        </TableCell>
                                        <TableCell>{formatDate(matricula.fecha_matricula)}</TableCell>
                                        <TableCell><StatusBadge value={matricula.estado_matricula} /></TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-2">
                                                <Link href={route('matriculas.show', matricula.id_matricula)} className="text-sm text-blue-600 hover:underline">Ver</Link>
                                                <button
                                                    type="button"
                                                    className="text-sm text-red-600 hover:underline"
                                                    onClick={() => {
                                                        if (confirm('¿Eliminar esta matrícula?')) {
                                                            router.delete(route('matriculas.destroy', matricula.id_matricula));
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
                            <Pagination links={matriculas.meta.links} />
                        </div>
                    </>
                )}
            </section>
        </PageShell>
    );
}
