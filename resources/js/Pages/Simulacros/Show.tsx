import { Link } from '@inertiajs/react';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Simulacro } from '@/types/academia';
import { formatDate } from '@/lib/format';
import { Calendar, Award, BookOpen, Users } from 'lucide-react';

type Props = {
    simulacro: Simulacro & { resultados_count?: number };
};

export default function SimulacrosShow({ simulacro }: Props) {
    return (
        <PageShell
            title={`Simulacro: ${simulacro.titulo}`}
            description="Detalles generales y métricas del examen."
            actions={
                <div className="flex gap-2">
                    <Link href={route('simulacros.resultados', simulacro.id_simulacro)}>
                        <PrimaryButton>Ver notas y rankings</PrimaryButton>
                    </Link>
                    <Link href={route('simulacros.index')}>
                        <SecondaryButton>Volver</SecondaryButton>
                    </Link>
                </div>
            }
        >
            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm space-y-4 md:col-span-2">
                    <h3 className="text-lg font-medium text-gray-900 border-b pb-3">Información General</h3>
                    
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider">Ciclo Académico</span>
                            <p className="text-sm font-medium text-gray-800 flex items-center gap-2 mt-1">
                                <BookOpen className="size-4 text-indigo-500" /> {simulacro.ciclo?.nombre_ciclo ?? '—'}
                            </p>
                        </div>
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider">Fecha de Aplicación</span>
                            <p className="text-sm font-medium text-gray-800 flex items-center gap-2 mt-1">
                                <Calendar className="size-4 text-indigo-500" /> {formatDate(simulacro.fecha_aplicacion)}
                            </p>
                        </div>
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider">Puntaje Máximo</span>
                            <p className="text-sm font-semibold text-indigo-600 flex items-center gap-2 mt-1">
                                <Award className="size-4 text-indigo-500" /> {simulacro.puntaje_maximo} puntos
                            </p>
                        </div>
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider">Alumnos Evaluados</span>
                            <p className="text-sm font-medium text-gray-800 flex items-center gap-2 mt-1">
                                <Users className="size-4 text-indigo-500" /> {simulacro.resultados_count ?? 0} estudiantes con nota
                            </p>
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 className="text-base font-medium text-gray-900 mb-2">Acciones Rápidas</h3>
                        <p className="text-xs text-gray-500 mb-4">Gestiona las calificaciones y los puestos obtenidos por los alumnos en este examen.</p>
                    </div>
                    <Link href={route('simulacros.resultados', simulacro.id_simulacro)} className="w-full">
                        <SecondaryButton className="w-full justify-center">Gestionar Resultados</SecondaryButton>
                    </Link>
                </div>
            </div>
        </PageShell>
    );
}
