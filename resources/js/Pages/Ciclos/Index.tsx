import { router } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { CalendarRange, Monitor, Plus, School } from 'lucide-react';
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
import { Ciclo } from '@/types/academia';
import { dateInputValue, formatDate } from '@/lib/format';

type Props = {
    ciclos: Ciclo[];
    stats: { total: number; presenciales: number; virtuales: number; hibridos: number };
};

export default function CiclosIndex({ ciclos, stats }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Ciclo | null>(null);

    return (
        <PageShell
            title="Ciclos"
            description="Periodos académicos, tipo, fechas y modalidad."
            actions={
                <PrimaryButton type="button" onClick={() => { setEditing(null); setOpen(true); }}>
                    <Plus className="mr-1 size-4" /> Nuevo ciclo
                </PrimaryButton>
            }
        >
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard title="Total" value={stats.total} icon={CalendarRange} />
                <StatCard title="Presenciales" value={stats.presenciales} icon={School} />
                <StatCard title="Virtuales" value={stats.virtuales} icon={Monitor} />
                <StatCard title="Híbridos" value={stats.hibridos} icon={CalendarRange} />
            </div>

            <section className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                {ciclos.length === 0 ? (
                    <EmptyState title="Sin ciclos" description="Crea un ciclo para matricular estudiantes." />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Ciclo</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Fechas</TableHead>
                                <TableHead>Modalidad</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Matrículas</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {ciclos.map((ciclo) => (
                                <TableRow key={ciclo.id_ciclo}>
                                    <TableCell>
                                        <p className="font-medium">{ciclo.nombre_ciclo}</p>
                                        <p className="text-xs text-gray-500">{ciclo.grupos_count ?? 0} grupos</p>
                                    </TableCell>
                                    <TableCell>{ciclo.tipo_ciclo}</TableCell>
                                    <TableCell>{formatDate(ciclo.fecha_inicio)} — {formatDate(ciclo.fecha_fin)}</TableCell>
                                    <TableCell><StatusBadge value={ciclo.modalidad} /></TableCell>
                                    <TableCell><StatusBadge value={ciclo.estado} /></TableCell>
                                    <TableCell>{ciclo.matriculas_count ?? 0}</TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-2">
                                            <button type="button" className="text-sm text-blue-600 hover:underline" onClick={() => { setEditing(ciclo); setOpen(true); }}>
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                className="text-sm text-red-600 hover:underline"
                                                onClick={() => {
                                                    if (confirm('¿Eliminar este ciclo?')) {
                                                        router.delete(route('ciclos.destroy', ciclo.id_ciclo));
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

            <FormModal show={open} title={editing ? 'Editar ciclo' : 'Nuevo ciclo'} onClose={() => setOpen(false)}>
                <Form
                    action={editing ? route('ciclos.update', editing.id_ciclo) : route('ciclos.store')}
                    method={editing ? 'put' : 'post'}
                    onSuccess={() => setOpen(false)}
                    className="grid grid-cols-1 gap-4 md:grid-cols-2"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="md:col-span-2">
                                <InputLabel htmlFor="nombre_ciclo" value="Nombre" />
                                <TextInput id="nombre_ciclo" name="nombre_ciclo" defaultValue={editing?.nombre_ciclo ?? ''} className="mt-1 block w-full" required />
                                <InputError message={errors.nombre_ciclo} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="tipo_ciclo" value="Tipo de ciclo" />
                                <select id="tipo_ciclo" name="tipo_ciclo" defaultValue={editing?.tipo_ciclo ?? 'Semestral'} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="Anual">Anual</option>
                                    <option value="Semestral">Semestral</option>
                                    <option value="Repaso">Repaso</option>
                                </select>
                                <InputError message={errors.tipo_ciclo} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="fecha_inicio" value="Inicio" />
                                <TextInput id="fecha_inicio" name="fecha_inicio" type="date" defaultValue={dateInputValue(editing?.fecha_inicio)} className="mt-1 block w-full" required />
                                <InputError message={errors.fecha_inicio} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="fecha_fin" value="Fin" />
                                <TextInput id="fecha_fin" name="fecha_fin" type="date" defaultValue={dateInputValue(editing?.fecha_fin)} className="mt-1 block w-full" required />
                                <InputError message={errors.fecha_fin} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="modalidad" value="Modalidad" />
                                <select id="modalidad" name="modalidad" defaultValue={editing?.modalidad ?? 'Presencial'} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="Presencial">Presencial</option>
                                    <option value="Virtual">Virtual</option>
                                    <option value="Híbrido">Híbrido</option>
                                </select>
                                <InputError message={errors.modalidad} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="estado" value="Estado" />
                                <select id="estado" name="estado" defaultValue={editing?.estado ?? 'Programado'} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="Programado">Programado</option>
                                    <option value="En curso">En curso</option>
                                    <option value="Cerrado">Cerrado</option>
                                </select>
                                <InputError message={errors.estado} className="mt-1" />
                            </div>
                            <div className="md:col-span-2 flex justify-end gap-2">
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
