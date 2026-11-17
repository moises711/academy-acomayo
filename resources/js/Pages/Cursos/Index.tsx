import { Link, router } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { BookOpen, FlaskConical, Languages, Plus } from 'lucide-react';
import { useState } from 'react';
import PageShell from '@/Components/PageShell';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import FormModal from '@/Components/FormModal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/Table';
import { Curso } from '@/types/academia';

type Props = {
    cursos: Curso[];
    stats: { total: number; aptitud: number; ciencias: number; letras: number };
};

export default function CursosIndex({ cursos, stats }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Curso | null>(null);

    return (
        <PageShell
            title="Cursos"
            description="Catálogo de asignaturas por área."
            actions={
                <PrimaryButton type="button" onClick={() => { setEditing(null); setOpen(true); }}>
                    <Plus className="mr-1 size-4" /> Nuevo curso
                </PrimaryButton>
            }
        >
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard title="Total" value={stats.total} icon={BookOpen} />
                <StatCard title="Aptitud" value={stats.aptitud} icon={BookOpen} />
                <StatCard title="Ciencias" value={stats.ciencias} icon={FlaskConical} />
                <StatCard title="Letras" value={stats.letras} icon={Languages} />
            </div>

            <section className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                {cursos.length === 0 ? (
                    <EmptyState title="Sin cursos" description="Crea el primer curso para armar horarios." />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Curso</TableHead>
                                <TableHead>Categoría</TableHead>
                                <TableHead>Horarios</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {cursos.map((curso) => (
                                <TableRow key={curso.id_curso}>
                                    <TableCell className="font-medium">{curso.nombre_curso}</TableCell>
                                    <TableCell><StatusBadge value={curso.categoria} /></TableCell>
                                    <TableCell>{curso.horarios_count ?? 0}</TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-2">
                                            <button type="button" className="text-sm text-blue-600 hover:underline" onClick={() => { setEditing(curso); setOpen(true); }}>
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                className="text-sm text-red-600 hover:underline"
                                                onClick={() => {
                                                    if (confirm('¿Eliminar este curso?')) {
                                                        router.delete(route('cursos.destroy', curso.id_curso));
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
                )}
            </section>

            <FormModal
                show={open}
                title={editing ? 'Editar curso' : 'Nuevo curso'}
                onClose={() => setOpen(false)}
            >
                <Form
                    action={editing ? route('cursos.update', editing.id_curso) : route('cursos.store')}
                    method={editing ? 'put' : 'post'}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div>
                                <InputLabel htmlFor="nombre_curso" value="Nombre del curso" />
                                <TextInput id="nombre_curso" name="nombre_curso" defaultValue={editing?.nombre_curso ?? ''} className="mt-1 block w-full" required />
                                <InputError message={errors.nombre_curso} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="categoria" value="Categoría" />
                                <select id="categoria" name="categoria" defaultValue={editing?.categoria ?? 'Ciencias'} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="Aptitud">Aptitud</option>
                                    <option value="Ciencias">Ciencias</option>
                                    <option value="Letras">Letras</option>
                                </select>
                                <InputError message={errors.categoria} className="mt-1" />
                            </div>
                            <div className="flex justify-end gap-2">
                                <SecondaryButton type="button" onClick={() => setOpen(false)}>Cancelar</SecondaryButton>
                                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                            </div>
                        </>
                    )}
                </Form>
            </FormModal>
        </PageShell>
    );
}
