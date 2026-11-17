import { router } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { BookOpen, Building2, GraduationCap, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import EmptyState from '@/Components/EmptyState';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';

type CatalogType = 'universidades' | 'areas' | 'carreras' | 'becas' | 'aulas' | 'grupos';
type CatalogRow = Record<string, unknown>;
type Option = { value: string | number; label: string };
type Field = { name: string; label: string; type?: 'text' | 'number' | 'url' | 'select' | 'checkbox'; required?: boolean; options?: Option[]; step?: string; min?: number; max?: number };
type Props = {
    universidades: CatalogRow[]; areas: CatalogRow[]; carreras: CatalogRow[]; becas: CatalogRow[]; aulas: CatalogRow[]; grupos: CatalogRow[];
    ciclos: { id_ciclo: number; nombre_ciclo: string }[];
};

const types: { id: CatalogType; label: string; icon: typeof Building2 }[] = [
    { id: 'universidades', label: 'Universidades', icon: Building2 },
    { id: 'areas', label: 'Áreas académicas', icon: BookOpen },
    { id: 'carreras', label: 'Carreras', icon: GraduationCap },
    { id: 'becas', label: 'Becas', icon: GraduationCap },
    { id: 'aulas', label: 'Aulas', icon: Building2 },
    { id: 'grupos', label: 'Grupos', icon: BookOpen },
];
const idFields: Record<CatalogType, string> = { universidades: 'id_universidad', areas: 'id_area', carreras: 'id_carrera', becas: 'id_beca', aulas: 'id_aula', grupos: 'id_grupo' };
const classes = 'mt-1 min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-950';

export default function CatalogosIndex(props: Props) {
    const [activeType, setActiveType] = useState<CatalogType>('universidades');
    const [editing, setEditing] = useState<{ type: CatalogType; row: CatalogRow } | null>(null);
    const [search, setSearch] = useState('');
    const selectedType = editing?.type ?? activeType;
    const rows = useMemo(() => (props[activeType] as CatalogRow[]).filter((row) => JSON.stringify(row).toLocaleLowerCase('es').includes(search.toLocaleLowerCase('es'))), [activeType, props, search]);
    const universities = props.universidades.map((row) => ({ value: row.id_universidad as number, label: String(row.nombre) }));
    const areas = props.areas.map((row) => ({ value: row.id_area as number, label: String(row.nombre) }));
    const cycles = props.ciclos.map((row) => ({ value: row.id_ciclo, label: row.nombre_ciclo }));
    const fields = fieldsFor(selectedType, universities, areas, cycles);
    const title = types.find((item) => item.id === selectedType)?.label ?? 'Catálogos';

    function chooseType(type: CatalogType) {
        setEditing(null);
        setActiveType(type);
        setSearch('');
    }

    function rowTitle(row: CatalogRow): string {
        if (activeType === 'carreras') return String(row.nombre ?? 'Carrera');
        if (activeType === 'grupos') return String(row.nombre ?? 'Grupo');
        return String(row.nombre ?? 'Registro');
    }

    function rowDescription(row: CatalogRow): string {
        if (activeType === 'universidades') return `${row.siglas || 'Sin siglas'} · ${row.tipo || 'Tipo sin definir'} · ${row.carreras_count ?? 0} carreras`;
        if (activeType === 'areas') return `${row.carreras_count ?? 0} carreras · ${row.grupos_count ?? 0} grupos`;
        if (activeType === 'carreras') return `${(row.universidad as CatalogRow | undefined)?.nombre ?? 'Universidad'} · ${(row.areaacademica as CatalogRow | undefined)?.nombre ?? 'Área'} · ${(row.matriculas_count as number) ?? 0} matrículas`;
        if (activeType === 'becas') return `${row.tipo} · ${row.porcentaje_descuento}% de descuento · ${row.activa ? 'Activa' : 'Inactiva'} · ${row.matriculas_count ?? 0} matrículas`;
        if (activeType === 'aulas') return `${row.tipo} · ${row.capacidad ?? 'Capacidad no definida'} plazas · ${row.horarios_count ?? 0} horarios`;
        return `${(row.ciclo as CatalogRow | undefined)?.nombre_ciclo ?? 'Ciclo'} · ${(row.areaacademica as CatalogRow | undefined)?.nombre ?? 'Área sin definir'} · ${row.matriculas_count ?? 0} matrículas · ${row.horarios_count ?? 0} horarios`;
    }

    return (
        <PageShell title="Catálogos académicos" description="Mantén actualizados los datos que se usan en matrículas, becas y horarios.">
            <div className="flex gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Tipos de catálogo">{types.map(({ id, label, icon: Icon }) => <button key={id} type="button" role="tab" aria-selected={activeType === id} onClick={() => chooseType(id)} className={`inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600 ${activeType === id ? 'bg-blue-700 text-white' : 'border border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200'}`}><Icon className="size-4" />{label}</button>)}</div>

            <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <div className="mb-5"><h2 className="font-semibold text-slate-900 dark:text-white">{editing ? `Editar ${title.toLocaleLowerCase('es')}` : `Registrar ${title.toLocaleLowerCase('es').replace(/s$/, '')}`}</h2><p className="text-sm text-slate-500">Los registros que ya se usan no se pueden eliminar para conservar su historial.</p></div>
                <Form id="catalogo-form" key={`${selectedType}-${editing?.row[idFields[selectedType]] ?? 'nuevo'}`} action={editing ? route('catalogos.update', [selectedType, editing.row[idFields[selectedType]] as number]) : route('catalogos.store', selectedType)} method={editing ? 'put' : 'post'} onSuccess={() => setEditing(null)} className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {({ errors, processing }) => <>
                        {fields.map((field) => <FieldInput key={field.name} field={field} value={editing?.row[field.name]} error={errors[field.name]} />)}
                        <div className="flex flex-wrap items-end gap-2">{editing && <SecondaryButton type="button" onClick={() => setEditing(null)}>Cancelar</SecondaryButton>}<PrimaryButton disabled={processing}>{processing ? 'Guardando…' : <><Plus className="mr-2 size-4" />{editing ? 'Guardar cambios' : 'Registrar'}</>}</PrimaryButton></div>
                    </>}
                </Form>
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 className="font-semibold text-slate-900 dark:text-white">{title}</h2><p className="text-sm text-slate-500">{rows.length} registros</p></div><div className="relative w-full sm:max-w-xs"><Search className="absolute left-3 top-3 size-4 text-slate-400" /><TextInput value={search} onChange={(event) => setSearch(event.target.value)} placeholder={`Buscar ${title.toLocaleLowerCase('es')}…`} aria-label={`Buscar ${title}`} className={`${classes} pl-9`} /></div></div>
                {rows.length === 0 ? <EmptyState title={`No hay ${title.toLocaleLowerCase('es')}`} description="Registra un elemento para tenerlo disponible en los otros módulos." /> : <div className="grid gap-3 lg:grid-cols-2">{rows.map((row) => {
                    const rowId = Number(row[idFields[activeType]]);
                    return <article key={rowId} className="flex min-w-0 items-center justify-between gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700"><div className="min-w-0"><h3 className="truncate font-semibold text-slate-900 dark:text-white">{rowTitle(row)}</h3><p className="mt-1 text-sm text-slate-500">{rowDescription(row)}</p></div><div className="flex shrink-0 gap-2"><button type="button" aria-label={`Editar ${rowTitle(row)}`} onClick={() => { setEditing({ type: activeType, row }); document.getElementById('catalogo-form')?.scrollIntoView({ behavior: 'smooth' }); }} className="rounded-lg p-2 text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-slate-800"><Pencil className="size-4" /></button><button type="button" aria-label={`Eliminar ${rowTitle(row)}`} onClick={() => { if (confirm(`¿Eliminar “${rowTitle(row)}”?`)) router.delete(route('catalogos.destroy', [activeType, rowId]), { preserveScroll: true }); }} className="rounded-lg p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950"><Trash2 className="size-4" /></button></div></article>;
                })}</div>}
            </section>
        </PageShell>
    );
}

function fieldsFor(type: CatalogType, universities: Option[], areas: Option[], cycles: Option[]): Field[] {
    switch (type) {
        case 'universidades': return [{ name: 'nombre', label: 'Nombre', required: true }, { name: 'siglas', label: 'Siglas' }, { name: 'tipo', label: 'Tipo', type: 'select', options: options(['Pública', 'Privada'], true) }];
        case 'areas': return [{ name: 'nombre', label: 'Nombre del área', required: true }];
        case 'carreras': return [{ name: 'id_universidad', label: 'Universidad', type: 'select', required: true, options: universities }, { name: 'id_area', label: 'Área académica', type: 'select', required: true, options: areas }, { name: 'nombre', label: 'Carrera', required: true }, { name: 'puntaje_minimo', label: 'Puntaje mínimo', type: 'number', min: 0, step: '0.01' }];
        case 'becas': return [{ name: 'nombre', label: 'Nombre', required: true }, { name: 'tipo', label: 'Tipo', type: 'select', required: true, options: options(['Beca', 'Media Beca', 'Convenio', 'Otro']) }, { name: 'porcentaje_descuento', label: 'Descuento (%)', type: 'number', required: true, min: 0, max: 100, step: '0.01' }, { name: 'activa', label: 'Activa', type: 'checkbox' }];
        case 'aulas': return [{ name: 'nombre', label: 'Nombre', required: true }, { name: 'tipo', label: 'Tipo', type: 'select', required: true, options: options(['Presencial', 'Virtual']) }, { name: 'capacidad', label: 'Capacidad', type: 'number', min: 1 }, { name: 'enlace_virtual', label: 'Enlace virtual', type: 'url' }];
        case 'grupos': return [{ name: 'id_ciclo', label: 'Ciclo', type: 'select', required: true, options: cycles }, { name: 'id_area', label: 'Área académica', type: 'select', options: areas }, { name: 'nombre', label: 'Nombre del grupo', required: true }, { name: 'turno', label: 'Turno', type: 'select', required: true, options: options(['Mañana', 'Tarde', 'Noche']) }, { name: 'capacidad', label: 'Capacidad', type: 'number', min: 1 }];
    }
}

function options(values: string[], optional = false): Option[] {
    return [...(optional ? [{ value: '', label: 'Selecciona…' }] : []), ...values.map((value) => ({ value, label: value }))];
}

function FieldInput({ field, value, error }: { field: Field; value: unknown; error?: string }) {
    const id = `catalogo-${field.name}`;
    return <div><InputLabel htmlFor={id} value={field.label} />{field.type === 'select' ? <select id={id} name={field.name} defaultValue={value === null || value === undefined ? '' : String(value)} required={field.required} className={classes}><option value="">Selecciona…</option>{field.options?.filter((option) => option.value !== '').map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select> : field.type === 'checkbox' ? <div className="mt-3 flex items-center gap-2"><input type="hidden" name={field.name} value="0" /><input id={id} name={field.name} type="checkbox" value="1" defaultChecked={value === undefined ? true : Boolean(value)} className="size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600" /><span className="text-sm text-slate-600 dark:text-slate-300">Sí</span></div> : <TextInput id={id} name={field.name} type={field.type ?? 'text'} defaultValue={value === null || value === undefined ? '' : String(value)} required={field.required} min={field.min} max={field.max} step={field.step} className={classes} />}{error && <InputError message={error} className="mt-1" />}</div>;
}
