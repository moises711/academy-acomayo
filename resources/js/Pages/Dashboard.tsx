import { Link } from '@inertiajs/react';
import {
    BookOpen,
    CalendarRange,
    ClipboardList,
    Clock,
    FileText,
    Users,
    Wallet,
} from 'lucide-react';
import PageShell from '@/Components/PageShell';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/Table';
import EmptyState from '@/Components/EmptyState';
import { formatDate, formatMoney, fullName } from '@/lib/format';
import { Consulta, Horario, Matricula, Pago, Simulacro } from '@/types/academia';

type DashboardProps = {
    role: 'admin' | 'secretaria' | 'caja' | 'profesor';
    stats: {
        estudiantes?: number;
        estudiantes_activos?: number;
        matriculas?: number;
        ciclos?: number;
        cursos?: number;
        pagos_pendientes?: number;
        monto_pendiente?: string;
        ingresos_mes?: string | number;
        cuotas_vencidas?: number;
        asistencia_hoy?: number;
        simulacros?: number;
        horarios?: number;
        consultas_nuevas?: number;
        clases_hoy?: number;
        grupos?: number;
    };
    matriculasRecientes?: Matricula[];
    pagosRecientes?: Pago[];
    proximosSimulacros?: Simulacro[];
    consultasNuevas?: Consulta[];
    clasesHoy?: Horario[];
    ingresosMensuales?: { mes: string; monto: string | number }[];
    promediosSimulacros?: { titulo: string; promedio: number; maximo: number }[];
};

export default function Dashboard({
    role,
    stats,
    matriculasRecientes = [],
    pagosRecientes = [],
    proximosSimulacros = [],
    consultasNuevas = [],
    clasesHoy = [],
    ingresosMensuales = [],
    promediosSimulacros = [],
}: DashboardProps) {
    if (role === 'secretaria') {
        return <PageShell title="Panel de secretaría" description="Atiende matrículas, ingresos y consultas de aspirantes.">
            <div className="grid gap-4 sm:grid-cols-3"><StatCard title="Estudiantes activos" value={stats.estudiantes_activos ?? 0} icon={Users} tone="cyan" /><StatCard title="Matrículas inscritas" value={stats.matriculas ?? 0} icon={ClipboardList} tone="violet" /><StatCard title="Consultas nuevas" value={stats.consultas_nuevas ?? 0} icon={FileText} tone="amber" /></div>
            <div className="grid gap-5 xl:grid-cols-2">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div className="mb-4 flex flex-wrap items-center justify-between"><h2 className="font-semibold">Accesos rápidos</h2><Link href="/asistencias" className="text-sm text-blue-700 hover:underline">Abrir escáner QR</Link></div><div className="flex flex-wrap gap-3"><Link href="/matriculas/create" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Nueva matrícula</Link><Link href="/consultas" className="rounded-lg border px-4 py-2 text-sm font-semibold">Ver consultas web</Link></div></section>
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><h2 className="mb-3 font-semibold">Consultas nuevas</h2>{consultasNuevas.length ? consultasNuevas.map((consulta) => <div key={consulta.id_consulta} className="border-t py-3 first:border-0"><p className="font-medium">{consulta.nombre} · {consulta.telefono}</p><p className="text-sm text-slate-500">{consulta.interes ?? 'Interés por definir'}</p></div>) : <EmptyState title="Sin consultas nuevas" description="Las solicitudes de la web aparecerán aquí." />}</section>
            </div>
        </PageShell>;
    }

    if (role === 'caja') {
        return <PageShell title="Panel de caja" description="Revisa los cobros del mes y los saldos pendientes.">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><StatCard title="Ingresos del mes" value={formatMoney(stats.ingresos_mes ?? 0)} icon={Wallet} tone="emerald" /><StatCard title="Cuotas pendientes" value={stats.pagos_pendientes ?? 0} icon={ClipboardList} tone="amber" /><StatCard title="Cuotas vencidas" value={stats.cuotas_vencidas ?? 0} icon={FileText} tone="violet" /><StatCard title="Saldo por cobrar" value={formatMoney(stats.monto_pendiente ?? '0')} icon={Wallet} tone="cyan" /></div>
            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div className="mb-4 flex flex-wrap items-center justify-between"><h2 className="font-semibold">Cobros recientes</h2><Link href="/pagos" className="text-sm text-blue-700 hover:underline">Registrar pago</Link></div>{pagosRecientes.length ? <div className="divide-y">{pagosRecientes.map((payment) => <div key={payment.id_pago} className="flex flex-wrap justify-between gap-3 py-3"><div><p className="font-medium">{fullName(payment.matricula?.estudiante)}</p><p className="text-sm text-slate-500">{payment.nro_comprobante ?? 'Sin comprobante'} · {payment.metodo_pago}</p></div><p className="font-semibold">{formatMoney(payment.monto)}</p></div>)}</div> : <EmptyState title="Aún no hay cobros" description="Los pagos válidos aparecerán aquí." />}</section>
        </PageShell>;
    }

    if (role === 'profesor') {
        return <PageShell title="Panel docente" description="Estas son las clases que tienes programadas para hoy.">
            <div className="grid gap-4 sm:grid-cols-2"><StatCard title="Clases de hoy" value={stats.clases_hoy ?? 0} icon={Clock} tone="cyan" /><StatCard title="Grupos" value={stats.grupos ?? 0} icon={Users} tone="violet" /></div>
            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div className="mb-4 flex flex-wrap items-center justify-between gap-3"><h2 className="font-semibold">Mi horario de hoy</h2><div className="flex gap-3"><Link href="/asistencias" className="text-sm text-blue-700 hover:underline">Tomar asistencia</Link><Link href="/notas" className="text-sm text-blue-700 hover:underline">Cargar notas</Link></div></div>{clasesHoy.length ? <div className="space-y-3">{clasesHoy.map((classItem) => <div key={classItem.id_horario} className="flex flex-wrap justify-between gap-3 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800"><div><p className="font-semibold">{classItem.curso?.nombre_curso} · {classItem.grupo?.nombre}</p><p className="text-sm text-slate-500">{classItem.aula?.nombre} · {classItem.grupo?.ciclo?.nombre_ciclo}</p></div><p className="text-sm font-semibold text-blue-700 dark:text-blue-300">{classItem.hora_inicio}–{classItem.hora_fin}</p></div>)}</div> : <EmptyState title="Sin clases para hoy" description="Si crees que esto es un error, contacta con administración." />}</section>
        </PageShell>;
    }

    return (
        <PageShell
            title="Dashboard"
            description="Resumen operativo de matrículas, pagos y evaluaciones."
        >
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard title="Estudiantes" value={stats.estudiantes ?? 0} hint={`${stats.estudiantes_activos ?? 0} activos`} icon={Users} tone="cyan" />
                <StatCard title="Matrículas" value={stats.matriculas ?? 0} hint={`${stats.ciclos ?? 0} ciclos`} icon={ClipboardList} tone="violet" />
                <StatCard title="Ingresos del mes" value={formatMoney(stats.ingresos_mes ?? 0)} icon={Wallet} tone="emerald" />
                <StatCard title="Cuotas vencidas" value={stats.cuotas_vencidas ?? 0} hint={`${stats.pagos_pendientes ?? 0} cuotas pendientes · ${formatMoney(stats.monto_pendiente ?? '0')}`} icon={Wallet} tone="amber" />
                <StatCard title="Asistencias de hoy" value={stats.asistencia_hoy ?? 0} icon={Users} tone="blue" />
                <StatCard title="Simulacros" value={stats.simulacros ?? 0} hint={`${stats.horarios ?? 0} horarios`} icon={FileText} tone="violet" />
            </div>

            <div className="grid gap-5 xl:grid-cols-2">
                <section className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 className="font-semibold text-slate-900 dark:text-white">Ingresos · últimos seis meses</h2>
                    {ingresosMensuales.length === 0 ? <EmptyState title="Sin ingresos registrados" description="Los cobros del sistema aparecerán en este gráfico." /> : <div className="mt-5 flex h-48 items-end justify-around gap-3" role="img" aria-label="Gráfico de ingresos de los últimos seis meses">
                        {(() => { const maximum = Math.max(...ingresosMensuales.map((item) => Number(item.monto)), 1); return ingresosMensuales.map((item) => <div key={item.mes} className="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2"><span className="text-xs font-medium text-slate-600 dark:text-slate-300">{formatMoney(item.monto)}</span><div className="flex w-full flex-1 items-end"><div className="mx-auto w-full max-w-12 rounded-t-lg bg-blue-600" style={{ height: `${Math.max(Number(item.monto) / maximum * 100, Number(item.monto) > 0 ? 5 : 0)}%` }} /></div><span className="text-xs capitalize text-slate-500">{item.mes}</span></div>); })()}
                    </div>}
                </section>
                <section className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 className="font-semibold text-slate-900 dark:text-white">Promedios de simulacros</h2>
                    {promediosSimulacros.length === 0 ? <EmptyState title="Sin resultados de simulacros" description="Cuando haya calificaciones, verás el promedio por examen." /> : <div className="mt-5 space-y-4">{promediosSimulacros.map((item) => <div key={item.titulo}><div className="mb-1 flex justify-between gap-3 text-sm"><span className="truncate font-medium text-slate-700 dark:text-slate-200">{item.titulo}</span><span className="shrink-0 text-slate-500">{item.promedio} / {item.maximo}</span></div><div className="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div className="h-full rounded-full bg-amber-500" style={{ width: `${Math.min(item.promedio / Math.max(item.maximo, 1) * 100, 100)}%` }} /></div></div>)}</div>}
                </section>
            </div>

            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <Link href="/cursos" className="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm hover:border-blue-200">
                    <BookOpen className="size-5 text-blue-600" />
                    <p className="mt-3 font-semibold text-gray-900">Cursos</p>
                    <p className="text-sm text-gray-500">{stats.cursos ?? 0} registrados</p>
                </Link>
                <Link href="/ciclos" className="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm hover:border-blue-200">
                    <CalendarRange className="size-5 text-blue-600" />
                    <p className="mt-3 font-semibold text-gray-900">Ciclos</p>
                    <p className="text-sm text-gray-500">Planificación académica</p>
                </Link>
                <Link href="/horarios" className="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm hover:border-blue-200">
                    <Clock className="size-5 text-blue-600" />
                    <p className="mt-3 font-semibold text-gray-900">Horarios</p>
                    <p className="text-sm text-gray-500">Aulas y turnos</p>
                </Link>
                <Link href="/reportes" className="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm hover:border-blue-200">
                    <ClipboardList className="size-5 text-blue-600" />
                    <p className="mt-3 font-semibold text-gray-900">Reportes</p>
                    <p className="text-sm text-gray-500">Indicadores del ciclo</p>
                </Link>
            </div>

            <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <section className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm xl:col-span-2">
                    <div className="mb-4 flex flex-wrap items-center justify-between">
                        <h2 className="font-semibold text-gray-900">Matrículas recientes</h2>
                        <Link href="/matriculas" className="text-sm text-blue-600 hover:underline">Ver todas</Link>
                    </div>
                    {matriculasRecientes.length === 0 ? (
                        <EmptyState title="Sin matrículas" description="Registra la primera matrícula para verla aquí." />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Estudiante</TableHead>
                                    <TableHead>Ciclo</TableHead>
                                    <TableHead>Carrera</TableHead>
                                    <TableHead>Estado</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {matriculasRecientes.map((matricula) => (
                                    <TableRow key={matricula.id_matricula}>
                                        <TableCell>{fullName(matricula.estudiante)}</TableCell>
                                        <TableCell>{matricula.ciclo?.nombre_ciclo ?? '—'}</TableCell>
                                            <TableCell>{matricula.carrera?.nombre ?? '—'}</TableCell>
                                        <TableCell><StatusBadge value={matricula.estado_matricula} /></TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </section>

                <section className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 className="mb-4 font-semibold text-gray-900">Próximos simulacros</h2>
                    {proximosSimulacros.length === 0 ? (
                        <p className="text-sm text-gray-500">No hay simulacros programados.</p>
                    ) : (
                        <ul className="space-y-3">
                            {proximosSimulacros.map((simulacro) => (
                                <li key={simulacro.id_simulacro} className="rounded-lg border border-gray-100 p-3">
                                    <p className="font-medium text-gray-900">{simulacro.titulo}</p>
                                    <p className="text-xs text-gray-500">{formatDate(simulacro.fecha_aplicacion)} · {simulacro.ciclo?.nombre_ciclo}</p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            <section className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div className="mb-4 flex flex-wrap items-center justify-between">
                    <h2 className="font-semibold text-gray-900">Últimos pagos</h2>
                    <Link href="/pagos" className="text-sm text-blue-600 hover:underline">Ir a caja</Link>
                </div>
                {pagosRecientes.length === 0 ? (
                    <EmptyState title="Sin pagos" description="Cuando registres cuotas aparecerán en este listado." />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Estudiante</TableHead>
                                <TableHead>Concepto</TableHead>
                                <TableHead>Monto</TableHead>
                                <TableHead>Estado</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pagosRecientes.map((pago) => (
                                <TableRow key={pago.id_pago}>
                                    <TableCell>{fullName(pago.matricula?.estudiante)}</TableCell>
                                    <TableCell>{pago.cuotas?.map((cuota) => cuota.concepto).join(', ') || 'Pago aplicado'}</TableCell>
                                    <TableCell>{formatMoney(pago.monto)}</TableCell>
                                    <TableCell><StatusBadge value={pago.estado === 'valido' ? 'Pagado' : 'Anulado'} /></TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </section>
        </PageShell>
    );
}
