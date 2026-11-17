import { Link } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import PageShell from '@/Components/PageShell';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Ciclo } from '@/types/academia';

type Props = {
    ciclos: Ciclo[];
};

export default function SimulacrosCreate({ ciclos }: Props) {
    return (
        <PageShell title="Nuevo simulacro" description="Programa un examen de simulación para los estudiantes.">
            <section className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <Form action={route('simulacros.store')} method="post" className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {({ errors, processing }) => (
                        <>
                            <div className="md:col-span-2">
                                <InputLabel htmlFor="titulo" value="Título del simulacro" />
                                <TextInput id="titulo" name="titulo" className="mt-1 block w-full" placeholder="Ej. Primer Simulacro General CEPRE" required />
                                <InputError message={errors.titulo} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="id_ciclo" value="Ciclo académico" />
                                <select
                                    id="id_ciclo"
                                    name="id_ciclo"
                                    className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Selecciona un ciclo</option>
                                    {ciclos.map((ciclo) => (
                                        <option key={ciclo.id_ciclo} value={ciclo.id_ciclo}>
                                            {ciclo.nombre_ciclo}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.id_ciclo} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="fecha_aplicacion" value="Fecha de aplicación" />
                                <TextInput id="fecha_aplicacion" name="fecha_aplicacion" type="date" className="mt-1 block w-full" required />
                                <InputError message={errors.fecha_aplicacion} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="puntaje_maximo" value="Puntaje máximo" />
                                <TextInput id="puntaje_maximo" name="puntaje_maximo" type="number" min="0" step="0.01" defaultValue="1000" className="mt-1 block w-full" required />
                                <InputError message={errors.puntaje_maximo} className="mt-1" />
                            </div>

                            <div className="md:col-span-2 mt-4 flex gap-2">
                                <PrimaryButton disabled={processing}>Guardar simulacro</PrimaryButton>
                                <Link href={route('simulacros.index')}>
                                    <SecondaryButton type="button">Cancelar</SecondaryButton>
                                </Link>
                            </div>
                        </>
                    )}
                </Form>
            </section>
        </PageShell>
    );
}