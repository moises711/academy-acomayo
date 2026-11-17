import { Link } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import PageShell from '@/Components/PageShell';
import StatusBadge from '@/Components/StatusBadge';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Matricula } from '@/types/academia';
import { formatDate, fullName } from '@/lib/format';

export default function MatriculasShow({ matricula }: { matricula: Matricula }) {
    return (
        <PageShell
            title="Detalle de matrícula"
            description={`${fullName(matricula.estudiante)} · ${matricula.ciclo?.nombre_ciclo ?? ''}`}
            actions={
                <Link href={route('matriculas.index')}>
                    <SecondaryButton type="button">Volver</SecondaryButton>
                </Link>
            }
        >
            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <p className="text-sm text-gray-500">Estudiante</p>
                    <p className="mt-1 font-medium">{fullName(matricula.estudiante)}</p>
                </div>
                <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <p className="text-sm text-gray-500">Ciclo</p>
                    <p className="mt-1 font-medium">{matricula.ciclo?.nombre_ciclo ?? '—'}</p>
                </div>
                <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <p className="text-sm text-gray-500">Estado</p>
                    <div className="mt-1"><StatusBadge value={matricula.estado_matricula} /></div>
                </div>
            </div>

            <section className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <dl className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt className="text-sm text-gray-500">Universidad</dt>
                        <dd className="font-medium">{matricula.carrera?.universidad?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-gray-500">Carrera</dt>
                        <dd className="font-medium">{matricula.carrera?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-gray-500">Área</dt>
                        <dd className="font-medium">{matricula.carrera?.area?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-gray-500">Fecha</dt>
                        <dd className="font-medium">{formatDate(matricula.fecha_matricula)}</dd>
                    </div>
                </dl>

                <Form action={route('matriculas.update', matricula.id_matricula)} method="put" className="mt-6 flex items-end gap-3">
                    {({ processing }) => (
                        <>
                            <div>
                                <InputLabel htmlFor="estado_matricula" value="Cambiar estado" />
                                <select id="estado_matricula" name="estado_matricula" defaultValue={matricula.estado_matricula} className="mt-1 rounded-md border-gray-300 shadow-sm">
                                    <option value="inscrito">Inscrito</option>
                                    <option value="retirado">Retirado</option>
                                    <option value="congelado">Congelado</option>
                                    <option value="finalizado">Finalizado</option>
                                </select>
                            </div>
                            <PrimaryButton disabled={processing}>Actualizar</PrimaryButton>
                        </>
                    )}
                </Form>
            </section>
        </PageShell>
    );
}
