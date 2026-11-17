import { router } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { BookOpen, Megaphone, Pencil, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/Components/EmptyState';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageShell from '@/Components/PageShell';
import Pagination from '@/Components/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Paginated } from '@/types/academia';

type Anuncio = { id_anuncio: number; id_ciclo: number | null; id_grupo: number | null; titulo: string; contenido: string; publicado_en: string; vence_en: string | null; ciclo?: { nombre_ciclo: string }; grupo?: { nombre: string }; user?: { name: string } };
type Material = { id_material: number; id_curso: number; id_grupo: number | null; titulo: string; tipo: string; url: string; created_at: string; curso?: { nombre_curso: string }; grupo?: { nombre: string }; user?: { name: string } };
type Ciclo = { id_ciclo: number; nombre_ciclo: string };
type Grupo = { id_grupo: number; id_ciclo: number; nombre: string; ciclo?: Ciclo };
type Curso = { id_curso: number; nombre_curso: string };
type Publication = (Anuncio & { tipo_registro: 'anuncio' }) | (Material & { tipo_registro: 'material' });
type Props = {
    anuncios: Paginated<Anuncio> | null;
    materiales: Paginated<Material> | null;
    filters: { search?: string; tipo_registro?: 'anuncio' | 'material' };
    ciclos: Ciclo[];
    grupos: Grupo[];
    cursos: Curso[];
};

const control = 'mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-950';

export default function ComunicacionIndex({ anuncios, materiales, filters, ciclos, grupos, cursos }: Props) {
    const [editing, setEditing] = useState<Publication | null>(null);
    const [newType, setNewType] = useState<'anuncio' | 'material'>('anuncio');
    const selectedType = editing?.tipo_registro ?? newType;
    const editingAnnouncement = editing?.tipo_registro === 'anuncio' ? editing : null;
    const editingMaterial = editing?.tipo_registro === 'material' ? editing : null;
    const editingId = editingAnnouncement?.id_anuncio ?? editingMaterial?.id_material ?? 'nuevo';
    const records: Publication[] = [
        ...(anuncios?.data ?? []).map((item) => ({ ...item, tipo_registro: 'anuncio' as const })),
        ...(materiales?.data ?? []).map((item) => ({ ...item, tipo_registro: 'material' as const })),
    ];

    function beginEdit(record: Publication) {
        setEditing(record);
        setNewType(record.tipo_registro);
        document.getElementById('form-publicacion')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function clearForm() {
        setEditing(null);
        setNewType('anuncio');
    }

    return (
        <PageShell title="Anuncios y materiales" description="Publica avisos y comparte recursos con los grupos de la academia.">
            <section id="form-publicacion" className="scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <div className="mb-5 flex items-start gap-3">
                    <div className="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">{selectedType === 'anuncio' ? <Megaphone className="size-5" /> : <BookOpen className="size-5" />}</div>
                    <div><h2 className="font-semibold text-slate-900 dark:text-white">{editing ? 'Editar publicación' : 'Nueva publicación'}</h2><p className="text-sm text-slate-500">Los anuncios aparecen en el portal de los estudiantes del ciclo o grupo elegido.</p></div>
                </div>
                {!editing && <div className="mb-4 flex flex-wrap gap-2" role="group" aria-label="Tipo de publicación">
                    <button type="button" onClick={() => setNewType('anuncio')} className={`rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600 ${selectedType === 'anuncio' ? 'bg-blue-700 text-white' : 'border border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-200'}`}>Anuncio</button>
                    <button type="button" onClick={() => setNewType('material')} className={`rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600 ${selectedType === 'material' ? 'bg-blue-700 text-white' : 'border border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-200'}`}>Material</button>
                </div>}
                <Form key={`${selectedType}-${editingId}`} action={editing ? route('comunicacion.update', [editing.tipo_registro, editingId]) : route('comunicacion.store')} method={editing ? 'put' : 'post'} onSuccess={clearForm} className="grid gap-4 md:grid-cols-2">
                    {({ errors, processing }) => <>
                        <input type="hidden" name="tipo_registro" value={selectedType} />
                        <div className="md:col-span-2"><InputLabel htmlFor="titulo" value="Título" /><TextInput id="titulo" name="titulo" defaultValue={editing?.titulo ?? ''} required maxLength={150} className={control} /><InputError message={errors.titulo} className="mt-1" /></div>
                        {selectedType === 'anuncio' ? <>
                            <div className="md:col-span-2"><InputLabel htmlFor="contenido" value="Mensaje" /><textarea id="contenido" name="contenido" defaultValue={editingAnnouncement?.contenido ?? ''} required rows={4} maxLength={10000} className={control} /><InputError message={errors.contenido} className="mt-1" /></div>
                            <SelectField id="id_ciclo" label="Ciclo (opcional)" error={errors.id_ciclo} defaultValue={editingAnnouncement?.id_ciclo ?? ''}><option value="">Todos los ciclos</option>{ciclos.map((item) => <option key={item.id_ciclo} value={item.id_ciclo}>{item.nombre_ciclo}</option>)}</SelectField>
                            <SelectField id="id_grupo" label="Grupo (opcional)" error={errors.id_grupo} defaultValue={editingAnnouncement?.id_grupo ?? ''}><option value="">Todos los grupos</option>{grupos.map((item) => <option key={item.id_grupo} value={item.id_grupo}>{item.nombre}{item.ciclo ? ` · ${item.ciclo.nombre_ciclo}` : ''}</option>)}</SelectField>
                            <div><InputLabel htmlFor="vence_en" value="Vence (opcional)" /><TextInput id="vence_en" name="vence_en" type="datetime-local" defaultValue={editingAnnouncement?.vence_en ? editingAnnouncement.vence_en.slice(0, 16) : ''} className={control} /><InputError message={errors.vence_en} className="mt-1" /></div>
                        </> : <>
                            <SelectField id="id_curso" label="Curso" error={errors.id_curso} required defaultValue={editingMaterial?.id_curso ?? ''}><option value="">Selecciona un curso</option>{cursos.map((item) => <option key={item.id_curso} value={item.id_curso}>{item.nombre_curso}</option>)}</SelectField>
                            <SelectField id="tipo" label="Tipo de recurso" error={errors.tipo} required defaultValue={editingMaterial?.tipo ?? 'PDF'}><option>PDF</option><option>Video</option><option>Enlace</option><option>Otro</option></SelectField>
                            <div><InputLabel htmlFor="url" value="Enlace del recurso" /><TextInput id="url" name="url" type="url" defaultValue={editingMaterial?.url ?? ''} required maxLength={500} placeholder="https://…" className={control} /><InputError message={errors.url} className="mt-1" /></div>
                            <SelectField id="id_grupo" label="Grupo (opcional)" error={errors.id_grupo} defaultValue={editingMaterial?.id_grupo ?? ''}><option value="">Todos los grupos del curso</option>{grupos.map((item) => <option key={item.id_grupo} value={item.id_grupo}>{item.nombre}{item.ciclo ? ` · ${item.ciclo.nombre_ciclo}` : ''}</option>)}</SelectField>
                        </>}
                        <div className="flex flex-wrap justify-end gap-2 md:col-span-2">{editing && <SecondaryButton type="button" onClick={clearForm}>Cancelar edición</SecondaryButton>}<PrimaryButton disabled={processing}>{processing ? 'Guardando…' : <><Plus className="mr-2 size-4" />{editing ? 'Guardar cambios' : 'Publicar'}</>}</PrimaryButton></div>
                    </>}
                </Form>
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <Form action={route('comunicacion.index')} method="get" className="mb-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto]">
                    <TextInput name="search" defaultValue={filters.search ?? ''} placeholder="Buscar publicaciones…" aria-label="Buscar publicaciones" className={control} />
                    <select name="tipo_registro" defaultValue={filters.tipo_registro ?? ''} aria-label="Filtrar por tipo" className={control}><option value="">Todos los tipos</option><option value="anuncio">Anuncios</option><option value="material">Materiales</option></select>
                    <div className="flex gap-2"><PrimaryButton>Buscar</PrimaryButton><SecondaryButton type="button" onClick={() => router.reload({ only: ['anuncios', 'materiales'] })} aria-label="Actualizar publicaciones"><RefreshCw className="size-4" /></SecondaryButton></div>
                </Form>
                {records.length === 0 ? <EmptyState title="Aún no hay publicaciones" description="Crea un anuncio o comparte un material para tus estudiantes." action={<a href="#form-publicacion" className="inline-flex min-h-10 items-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white">Crear publicación</a>} /> : <div className="grid gap-3 lg:grid-cols-2">
                    {records.map((record) => <article key={`${record.tipo_registro}-${record.tipo_registro === 'anuncio' ? record.id_anuncio : record.id_material}`} className="flex min-w-0 flex-col justify-between gap-4 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <div className="min-w-0"><div className="flex flex-wrap items-center gap-2"><span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-950 dark:text-blue-200">{record.tipo_registro === 'anuncio' ? 'Anuncio' : record.tipo}</span><h3 className="font-semibold text-slate-900 dark:text-white">{record.titulo}</h3></div>
                            {record.tipo_registro === 'anuncio' ? <p className="mt-2 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{record.contenido}</p> : <a className="mt-2 block truncate text-sm text-blue-700 underline dark:text-blue-300" href={record.url} target="_blank" rel="noreferrer">{record.url}</a>}
                            <p className="mt-3 text-xs text-slate-500">{record.tipo_registro === 'anuncio' ? [record.ciclo?.nombre_ciclo, record.grupo?.nombre].filter(Boolean).join(' · ') || 'Todos los estudiantes' : [record.curso?.nombre_curso, record.grupo?.nombre].filter(Boolean).join(' · ')} · {record.user?.name ?? 'Equipo académico'}</p>
                        </div><div className="flex justify-end gap-3 border-t border-slate-100 pt-3 dark:border-slate-800"><button type="button" onClick={() => beginEdit(record)} className="inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline dark:text-blue-300"><Pencil className="size-4" />Editar</button><button type="button" onClick={() => { if (confirm('¿Eliminar esta publicación?')) router.delete(route('comunicacion.destroy', [record.tipo_registro, record.tipo_registro === 'anuncio' ? record.id_anuncio : record.id_material])); }} className="inline-flex items-center gap-1 text-sm font-medium text-red-600 hover:underline"><Trash2 className="size-4" />Eliminar</button></div>
                    </article>)}
                </div>}
                <div className="mt-5 grid gap-3 sm:grid-cols-2">{anuncios && <Pagination links={anuncios.meta.links} />}{materiales && <Pagination links={materiales.meta.links} />}</div>
            </section>
        </PageShell>
    );
}

function SelectField({ id, label, error, required = false, defaultValue, children }: { id: string; label: string; error?: string; required?: boolean; defaultValue?: string | number; children: React.ReactNode }) {
    return <div><InputLabel htmlFor={id} value={label} /><select id={id} name={id} defaultValue={defaultValue} required={required} className={control}>{children}</select><InputError message={error} className="mt-1" /></div>;
}
