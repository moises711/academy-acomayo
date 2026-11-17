import { Link, router } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { Search, UserRound, Users, UserX } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/Components/EmptyState';
import FormModal from '@/Components/FormModal';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageShell from '@/Components/PageShell';
import Pagination from '@/Components/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/Table';
import { Paginated, Profesor } from '@/types/academia';

type Docente = Profesor & { cursos_count: number };
type Props = {
    docentes: Paginated<Docente>;
    filters: { search?: string; estado?: 'activo' | 'inactivo' };
    stats: { total: number; activos: number; inactivos: number };
    canManagePayments: boolean;
};

export default function DocentesIndex({ docentes, filters, stats, canManagePayments }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Docente | null>(null);

    return (
        <PageShell
            title="Docentes"
            description="Administra el equipo docente, sus cursos y su estado en la academia."
            actions={<PrimaryButton type="button" onClick={() => { setEditing(null); setOpen(true); }}><UserRound className="mr-2 size-4" />Registrar docente</PrimaryButton>}
        >
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard title="Total" value={stats.total} icon={Users} />
                <StatCard title="Activos" value={stats.activos} icon={UserRound} />
                <StatCard title="Inactivos" value={stats.inactivos} icon={UserX} />
            </div>

            <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
                <Form action={route('docentes.index')} method="get" className="mb-4 flex flex-col gap-3 sm:flex-row">
                    <div className="relative flex-1"><Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" /><TextInput name="search" defaultValue={filters.search ?? ''} placeholder="Buscar por nombre, DNI o correo" className="w-full pl-9" /></div>
                    <select aria-label="Filtrar por estado" name="estado" defaultValue={filters.estado ?? ''} className="rounded-md border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">Todos los estados</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option>
                    </select>
                    <SecondaryButton type="submit">Buscar</SecondaryButton>
                </Form>

                {docentes.data.length === 0 ? <EmptyState title="No hay docentes" description="Registra al primer docente para asignarle cursos y horarios." action={<PrimaryButton type="button" onClick={() => { setEditing(null); setOpen(true); }}>Registrar docente</PrimaryButton>} /> : <>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader><TableRow><TableHead>Docente</TableHead><TableHead>Contacto</TableHead><TableHead>Cursos y horarios</TableHead><TableHead>Estado</TableHead><TableHead className="text-right">Acciones</TableHead></TableRow></TableHeader>
                            <TableBody>{docentes.data.map((docente) => <TableRow key={docente.id_profesor}>
                                <TableCell><p className="font-semibold text-slate-900 dark:text-white">{docente.nombre} {docente.apellido}</p><p className="text-xs text-slate-500">DNI {docente.dni}</p></TableCell>
                                <TableCell><p>{docente.email ?? 'Sin correo'}</p><p className="text-xs text-slate-500">{docente.telefono ?? 'Sin teléfono'}</p></TableCell>
                                <TableCell>{docente.cursos_count} cursos · {docente.horarios_count} horarios</TableCell>
                                <TableCell><StatusBadge value={docente.estado} /></TableCell>
                                <TableCell><div className="flex flex-wrap justify-end gap-3">{canManagePayments && <Link href={route('docentes.pagos.index', docente.id_profesor)} className="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-300">Pagos</Link>}<button type="button" className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-300" onClick={() => { setEditing(docente); setOpen(true); }}>Editar</button><button type="button" className="text-sm font-medium text-red-600 hover:underline" onClick={() => { if (confirm('¿Eliminar este docente? Solo se puede eliminar si no tiene cursos, horarios, pagos ni una cuenta vinculada.')) router.delete(route('docentes.destroy', docente.id_profesor)); }}>Eliminar</button></div></TableCell>
                            </TableRow>)}</TableBody>
                        </Table>
                    </div>
                    <div className="mt-4"><Pagination links={docentes.meta.links} /></div>
                </>}
            </section>

            <FormModal show={open} title={editing ? 'Editar docente' : 'Registrar docente'} onClose={() => setOpen(false)}>
                <Form key={editing?.id_profesor ?? 'nuevo'} action={editing ? route('docentes.update', editing.id_profesor) : route('docentes.store')} method={editing ? 'put' : 'post'} onSuccess={() => setOpen(false)} className="space-y-4">
                    {({ errors, processing }) => <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field name="nombre" label="Nombres" value={editing?.nombre ?? ''} error={errors.nombre} required />
                            <Field name="apellido" label="Apellidos" value={editing?.apellido ?? ''} error={errors.apellido} required />
                            <Field name="dni" label="DNI" value={editing?.dni ?? ''} error={errors.dni} required />
                            <Field name="telefono" label="Teléfono" value={editing?.telefono ?? ''} error={errors.telefono} />
                        </div>
                        <Field name="email" label="Correo electrónico" type="email" value={editing?.email ?? ''} error={errors.email} />
                        <div><InputLabel htmlFor="estado" value="Estado" /><select id="estado" name="estado" defaultValue={editing?.estado ?? 'activo'} className="mt-1 block w-full rounded-md border-slate-300 shadow-sm dark:border-slate-700 dark:bg-slate-950"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select><InputError message={errors.estado} className="mt-1" /></div>
                        <div className="flex justify-end gap-2"><SecondaryButton type="button" onClick={() => setOpen(false)}>Cancelar</SecondaryButton><PrimaryButton disabled={processing}>{processing ? 'Guardando…' : 'Guardar docente'}</PrimaryButton></div>
                    </>}
                </Form>
            </FormModal>
        </PageShell>
    );
}

function Field({ name, label, value, error, type = 'text', required = false }: { name: string; label: string; value: string; error?: string; type?: string; required?: boolean }) {
    return <div><InputLabel htmlFor={name} value={label} /><TextInput id={name} name={name} type={type} defaultValue={value} required={required} className="mt-1 block w-full" /><InputError message={error} className="mt-1" /></div>;
}
