import { Link, router } from '@inertiajs/react';
import { FormEvent, useRef, useState } from 'react';
import { Download, GraduationCap, Plus, Printer, Search, UserMinus, Users } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import PageShell from '@/Components/PageShell';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
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
import { Estudiante, Paginated } from '@/types/academia';
import { fullName } from '@/lib/format';

type Props = {
    estudiantes: Paginated<Estudiante>;
    filters: { search?: string; estado?: string };
    stats: { total: number; activos: number; inactivos: number; egresados: number };
    canGenerateCarnets: boolean;
};

function StudentAvatar({ nombre, apellido, fotoUrl }: { nombre: string; apellido: string; fotoUrl?: string | null }) {
    const [imageFailed, setImageFailed] = useState(false);
    const initials = `${nombre.trim().charAt(0)}${apellido.trim().charAt(0)}`.toLocaleUpperCase('es');

    return (
        <div className="grid size-12 shrink-0 place-items-center overflow-hidden rounded-2xl bg-gradient-to-br from-blue-100 via-sky-50 to-amber-100 text-sm font-bold text-[#173B66] ring-1 ring-inset ring-blue-200/70 dark:from-blue-950 dark:via-slate-800 dark:to-amber-950 dark:text-blue-100 dark:ring-blue-900" aria-label={`Avatar de ${nombre} ${apellido}`}>
            {fotoUrl && !imageFailed
                ? <img src={fotoUrl} alt={`Foto de ${nombre} ${apellido}`} className="size-full object-cover" onError={() => setImageFailed(true)} />
                : <span aria-hidden="true">{initials || 'ES'}</span>}
        </div>
    );
}

function DownloadStudentQr({ value, code, name }: { value: string; code: string; name: string }) {
    const container = useRef<HTMLSpanElement>(null);

    function download() {
        const svg = container.current?.querySelector('svg');
        if (!svg) return;
        const data = new XMLSerializer().serializeToString(svg);
        const url = URL.createObjectURL(new Blob([data], { type: 'image/svg+xml;charset=utf-8' }));
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = `qr-${code}.svg`;
        anchor.click();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    return <><span ref={container} className="absolute -left-[9999px]" aria-hidden="true"><QRCodeSVG value={value} size={320} level="H" title={`QR de ${name}`} /></span><button type="button" onClick={download} className="inline-flex items-center gap-1 text-sm text-blue-700 hover:underline dark:text-blue-300" aria-label={`Descargar QR de carnet de ${name}`}><Download className="size-4" /> QR</button></>;
}

export default function EstudiantesIndex({ estudiantes, filters, stats, canGenerateCarnets }: Props) {
    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const values = new FormData(event.currentTarget);

        router.get(route('estudiantes.index'), {
            search: values.get('search')?.toString() ?? '',
            estado: values.get('estado')?.toString() ?? '',
        }, { preserveScroll: true });
    };

    return (
        <PageShell
            title="Estudiantes"
            description="Gestiona los datos y el estado académico de los estudiantes."
            actions={<div className="flex flex-wrap gap-2">{canGenerateCarnets && <Link href={route('estudiantes.carnets')}><SecondaryButton><Printer className="mr-2 size-4" />Carnets por grupo</SecondaryButton></Link>}<Link href={route('estudiantes.create')}><PrimaryButton><Plus className="mr-1 size-4" /> Nuevo estudiante</PrimaryButton></Link></div>}
        >
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard title="Total" value={stats.total} icon={Users} />
                <StatCard title="Activos" value={stats.activos} icon={GraduationCap} />
                <StatCard title="Inactivos" value={stats.inactivos} icon={UserMinus} />
                <StatCard title="Egresados" value={stats.egresados} icon={GraduationCap} />
            </div>

            <section className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <form action={route('estudiantes.index')} method="get" onSubmit={applyFilters} className="mb-4 flex flex-col gap-3 sm:flex-row">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-2.5 size-4 text-gray-400" />
                        <TextInput
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Buscar por nombre, apellido o correo"
                            className="w-full pl-9"
                        />
                    </div>
                    <div>
                        <InputLabel htmlFor="estado" value="Estado" className="sr-only" />
                        <select
                            id="estado"
                            name="estado"
                            defaultValue={filters.estado ?? ''}
                            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todos los estados</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                            <option value="egresado">Egresado</option>
                        </select>
                    </div>
                    <SecondaryButton type="submit">Filtrar</SecondaryButton>
                </form>

                {estudiantes.data.length === 0 ? (
                    <EmptyState
                        title="No hay estudiantes"
                        description="Registra el primer alumno para comenzar a matricular."
                        action={
                            <Link href={route('estudiantes.create')}>
                                <PrimaryButton>Registrar estudiante</PrimaryButton>
                            </Link>
                        }
                    />
                ) : (
                    <>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>DNI / Estudiante</TableHead>
                                    <TableHead>Código de registro</TableHead>
                                    <TableHead>Contacto</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {estudiantes.data.map((estudiante) => (
                                    <TableRow key={estudiante.id_estudiante}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <StudentAvatar nombre={estudiante.nombre} apellido={estudiante.apellido} fotoUrl={estudiante.fotoUrl} />
                                                <div className="min-w-0">
                                                    <p className="font-semibold text-slate-900 dark:text-white">{fullName(estudiante)}</p>
                                                    <p className="text-xs text-gray-500">DNI {estudiante.dni}</p>
                                                    <p className="text-xs text-gray-500">{estudiante.matriculas_count ?? 0} matrículas</p>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="font-mono text-xs">EST-{String(estudiante.id_estudiante).padStart(5, '0')}</TableCell>
                                        <TableCell>
                                            <p>{estudiante.email ?? 'Sin correo'}</p>
                                            <p className="text-xs text-gray-500">{estudiante.telefono ?? 'Sin teléfono'}</p>
                                        </TableCell>
                                        <TableCell><StatusBadge value={estudiante.estado} /></TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-2">
                                                <Link href={route('estudiantes.show', estudiante.id_estudiante)} className="text-sm text-blue-600 hover:underline">
                                                    Ver
                                                </Link>
                                                <Link href={route('estudiantes.edit', estudiante.id_estudiante)} className="text-sm text-gray-700 hover:underline">
                                                    Editar
                                                </Link>
                                                {canGenerateCarnets && estudiante.codigo_qr && <><Link href={route('estudiantes.carnet', estudiante.id_estudiante)} className="text-sm text-blue-700 hover:underline dark:text-blue-300">Carnet</Link><DownloadStudentQr value={estudiante.codigo_qr} code={`est-${String(estudiante.id_estudiante).padStart(5, '0')}`} name={fullName(estudiante)} /></>}
                                                <button
                                                    type="button"
                                                    className="text-sm text-red-600 hover:underline"
                                                    onClick={() => {
                                                        if (confirm('¿Eliminar este estudiante?')) {
                                                            router.delete(route('estudiantes.destroy', estudiante.id_estudiante));
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
                            <Pagination links={estudiantes.meta.links} />
                        </div>
                    </>
                )}
            </section>
        </PageShell>
    );
}
