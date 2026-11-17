import { ClipboardList, FileText, GraduationCap, TrendingUp, Users, Wallet } from 'lucide-react';
import EmptyState from '@/Components/EmptyState';
import PageShell from '@/Components/PageShell';
import StatCard from '@/Components/StatCard';
import { formatDate, formatMoney, fullName } from '@/lib/format';
import { Ciclo, Decimal } from '@/types/academia';

type CountRow = { estado?: string; categoria?: string; total: number };
type CicloReporte = Ciclo & { matriculas_count: number; simulacros_count: number; grupos_count: number };
type EstudianteReporte = { id_estudiante: number; dni: string; nombre: string; apellido: string; estado: string; matriculas_count: number };

type Props = {
    resumen: {
        estudiantes: number;
        estudiantes_activos: number;
        matriculas_activas: number;
        cuotas_vencidas: number;
        deuda_vencida: Decimal;
        ingresos_mes: Decimal;
        total_simulacros: number;
        estudiantes_por_estado: CountRow[];
        matriculas_por_estado: CountRow[];
        cursos_por_categoria: CountRow[];
    };
    ingresosPorMes: { periodo: string; mes: string; monto: Decimal }[];
    ciclos: CicloReporte[];
    topEstudiantes: EstudianteReporte[];
};

export default function ReportesIndex({ resumen, ingresosPorMes, ciclos, topEstudiantes }: Props) {
    const maxIncome = Math.max(1, ...ingresosPorMes.map((item) => Number(item.monto)));

    return (
        <PageShell title="Reportes" description="Resumen de actividad académica y financiera de la academia.">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard icon={Users} title="Estudiantes activos" value={`${resumen.estudiantes_activos} / ${resumen.estudiantes}`} tone="cyan" />
                <StatCard icon={ClipboardList} title="Matrículas inscritas" value={resumen.matriculas_activas} tone="violet" />
                <StatCard icon={Wallet} title="Ingresos del mes" value={formatMoney(resumen.ingresos_mes)} tone="emerald" />
                <StatCard icon={TrendingUp} title="Deuda vencida" value={formatMoney(resumen.deuda_vencida)} hint={`${resumen.cuotas_vencidas} cuotas`} tone="amber" />
            </div>

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <div className="mb-6 flex items-center gap-3"><div className="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700"><TrendingUp className="size-5" /></div><div><h2 className="font-semibold text-slate-900 dark:text-white">Ingresos · últimos seis meses</h2><p className="text-sm text-slate-500">Solo se incluyen pagos válidos.</p></div></div>
                {ingresosPorMes.length === 0 ? <EmptyState title="Sin datos de ingresos" description="Los cobros se mostrarán aquí cuando se registren." /> : <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">{ingresosPorMes.map((month) => {
                    const width = Math.max(3, (Number(month.monto) / maxIncome) * 100);
                    return <div key={month.periodo} className="min-w-0"><div className="flex h-36 items-end rounded-xl bg-slate-50 p-2 dark:bg-slate-950"><div className="w-full rounded-lg bg-blue-600 transition-all" style={{ height: `${width}%` }} title={formatMoney(month.monto)} /></div><p className="mt-2 truncate text-xs font-medium text-slate-600 dark:text-slate-300">{month.mes}</p><p className="mt-1 truncate text-xs tabular-nums text-slate-500">{formatMoney(month.monto)}</p></div>;
                })}</div>}
            </section>

            <div className="grid gap-6 xl:grid-cols-2">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 flex items-center gap-3"><GraduationCap className="size-5 text-blue-700" /><h2 className="font-semibold text-slate-900 dark:text-white">Ciclos académicos</h2></div>
                    {ciclos.length === 0 ? <EmptyState title="Sin ciclos" description="Crea ciclos para consultar su actividad." /> : <div className="overflow-x-auto"><table className="w-full min-w-[520px] text-left text-sm"><thead className="text-xs uppercase tracking-wide text-slate-500"><tr><th className="py-3 pr-3">Ciclo</th><th className="py-3 pr-3">Estado</th><th className="py-3 pr-3">Matrículas</th><th className="py-3 pr-3">Grupos</th><th className="py-3">Simulacros</th></tr></thead><tbody className="divide-y divide-slate-100 dark:divide-slate-800">{ciclos.map((cycle) => <tr key={cycle.id_ciclo}><td className="py-3 pr-3"><p className="font-medium">{cycle.nombre_ciclo}</p><p className="text-xs text-slate-500">{formatDate(cycle.fecha_inicio)}–{formatDate(cycle.fecha_fin)}</p></td><td className="py-3 pr-3">{cycle.estado}</td><td className="py-3 pr-3">{cycle.matriculas_count}</td><td className="py-3 pr-3">{cycle.grupos_count}</td><td className="py-3">{cycle.simulacros_count}</td></tr>)}</tbody></table></div>}
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 flex items-center gap-3"><Users className="size-5 text-blue-700" /><h2 className="font-semibold text-slate-900 dark:text-white">Estudiantes con más matrículas activas</h2></div>
                    {topEstudiantes.length === 0 ? <EmptyState title="Sin matrículas" description="Los estudiantes aparecerán cuando se registren matrículas." /> : <div className="space-y-2">{topEstudiantes.map((student) => <div key={student.id_estudiante} className="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-slate-50 p-3 dark:bg-slate-950"><div className="min-w-0"><p className="truncate font-medium text-slate-900 dark:text-white">{fullName(student)}</p><p className="text-xs text-slate-500">DNI {student.dni} · {student.estado}</p></div><span className="shrink-0 rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">{student.matriculas_count}</span></div>)}</div>}
                </section>
            </div>

            <div className="grid gap-6 md:grid-cols-3">
                <CountPanel title="Estudiantes" rows={resumen.estudiantes_por_estado} labelKey="estado" />
                <CountPanel title="Matrículas" rows={resumen.matriculas_por_estado} labelKey="estado" />
                <CountPanel title="Cursos" rows={resumen.cursos_por_categoria} labelKey="categoria" />
            </div>

            <section className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"><FileText className="size-5 text-blue-700" /><p className="text-sm text-slate-600 dark:text-slate-300">Simulacros registrados: <span className="font-semibold text-slate-900 dark:text-white">{resumen.total_simulacros}</span></p></section>
        </PageShell>
    );
}

function CountPanel({ title, rows, labelKey }: { title: string; rows: CountRow[]; labelKey: 'estado' | 'categoria' }) {
    return <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><h2 className="mb-4 font-semibold text-slate-900 dark:text-white">{title} por {labelKey === 'estado' ? 'estado' : 'categoría'}</h2>{rows.length === 0 ? <p className="text-sm text-slate-500">Sin datos disponibles.</p> : <ul className="space-y-3">{rows.map((row) => <li key={row[labelKey]} className="flex flex-wrap items-center justify-between gap-3 text-sm"><span className="text-slate-600 dark:text-slate-300">{row[labelKey]}</span><span className="font-semibold tabular-nums">{row.total}</span></li>)}</ul>}</section>;
}
