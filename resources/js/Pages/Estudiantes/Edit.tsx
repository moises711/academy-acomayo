import { Link } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import PageShell from '@/Components/PageShell';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Estudiante } from '@/types/academia';
import { dateInputValue } from '@/lib/format';
import { useEffect, useState } from 'react';

export default function EstudiantesEdit({ estudiante, fotoUrl }: { estudiante: Estudiante; fotoUrl: string | null }) {
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);
    const [photoWarning, setPhotoWarning] = useState<string | null>(null);
    useEffect(() => () => { if (photoPreview) URL.revokeObjectURL(photoPreview); }, [photoPreview]);
    return (
        <PageShell title="Editar estudiante" description={`Actualiza la ficha de ${estudiante.nombre} ${estudiante.apellido}.`}>
            <section className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <Form action={route('estudiantes.update', estudiante.id_estudiante)} method="put" encType="multipart/form-data" className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-3 md:col-span-2 sm:grid-cols-[7rem_minmax(0,1fr)] sm:items-center">
                                <div className="grid size-28 place-items-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-950">
                                    {photoPreview ? <img src={photoPreview} alt="Vista previa de la foto seleccionada" className="size-full object-cover" /> : fotoUrl ? <img src={fotoUrl} alt={`Foto de ${estudiante.nombre} ${estudiante.apellido}`} className="size-full object-cover" /> : <span>Sin foto</span>}
                                </div>
                                <div className="grid gap-1.5">
                                    <InputLabel htmlFor="foto" value="Foto del estudiante (opcional)" />
                                    <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => { const file = event.currentTarget.files?.[0]; if (file && file.size > 10 * 1024 * 1024) { event.currentTarget.value = ''; setPhotoPreview(null); setPhotoWarning('La imagen supera el límite de 10 MB. Elige un archivo más pequeño.'); return; } setPhotoWarning(null); setPhotoPreview(file ? URL.createObjectURL(file) : null); }} className="block w-full text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-800 hover:file:bg-blue-100 dark:file:bg-blue-950 dark:file:text-blue-200" />
                                    <p className="text-xs text-slate-500">JPG, PNG o WebP · máximo 10 MB. La imagen se recortará dentro del carnet.</p>
                                    {photoWarning && <p role="alert" className="text-sm text-amber-700 dark:text-amber-300">{photoWarning}</p>}
                                    <InputError message={errors.foto} />
                                </div>
                            </div>
                            <div>
                                <InputLabel htmlFor="dni" value="DNI" />
                                <TextInput id="dni" name="dni" inputMode="numeric" maxLength={15} defaultValue={estudiante.dni} className="mt-1 block w-full" required />
                                <InputError message={errors.dni} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="nombre" value="Nombre" />
                                <TextInput id="nombre" name="nombre" defaultValue={estudiante.nombre} className="mt-1 block w-full" required />
                                <InputError message={errors.nombre} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="apellido" value="Apellido" />
                                <TextInput id="apellido" name="apellido" defaultValue={estudiante.apellido} className="mt-1 block w-full" required />
                                <InputError message={errors.apellido} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="email" value="Correo" />
                                <TextInput id="email" name="email" type="email" defaultValue={estudiante.email ?? ''} className="mt-1 block w-full" />
                                <InputError message={errors.email} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="telefono" value="Teléfono" />
                                <TextInput id="telefono" name="telefono" defaultValue={estudiante.telefono ?? ''} className="mt-1 block w-full" />
                                <InputError message={errors.telefono} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="fecha_nacimiento" value="Fecha de nacimiento" />
                                <TextInput id="fecha_nacimiento" name="fecha_nacimiento" type="date" defaultValue={dateInputValue(estudiante.fecha_nacimiento)} className="mt-1 block w-full" />
                                <InputError message={errors.fecha_nacimiento} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="direccion" value="Dirección" />
                                <TextInput id="direccion" name="direccion" defaultValue={estudiante.direccion ?? ''} maxLength={200} className="mt-1 block w-full" />
                                <InputError message={errors.direccion} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="colegio_procedencia" value="Colegio de procedencia" />
                                <TextInput id="colegio_procedencia" name="colegio_procedencia" defaultValue={estudiante.colegio_procedencia ?? ''} maxLength={150} className="mt-1 block w-full" />
                                <InputError message={errors.colegio_procedencia} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="anio_egreso" value="Año de egreso" />
                                <TextInput id="anio_egreso" name="anio_egreso" type="number" min={1990} max={new Date().getFullYear() + 1} defaultValue={estudiante.anio_egreso ?? ''} className="mt-1 block w-full" />
                                <InputError message={errors.anio_egreso} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="estado" value="Estado" />
                                <select id="estado" name="estado" defaultValue={estudiante.estado} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                    <option value="egresado">Egresado</option>
                                </select>
                                <InputError message={errors.estado} className="mt-1" />
                            </div>
                            <div className="md:col-span-2 mt-2 flex gap-2">
                                <PrimaryButton disabled={processing}>Actualizar</PrimaryButton>
                                <Link href={route('estudiantes.show', estudiante.id_estudiante)}>
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
