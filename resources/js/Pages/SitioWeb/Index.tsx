import { Link, useForm } from '@inertiajs/react';
import { BarChart3, BookOpen, CalendarDays, Clock3, Eye, Globe2, MessageSquareQuote, PanelsTopLeft, Save, Users } from 'lucide-react';
import { ChangeEvent, ReactNode, useEffect, useState } from 'react';
import InputError from '@/Components/InputError';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { landingContent } from '@/data/landing';

type RecordValue = Record<string, any>;
type Teacher = { id_profesor: number; nombre: string; cursos: string[]; fotoUrl: string | null };
type TeacherProfile = { id_profesor: number; bio: string };
type Props = { contenido: RecordValue; docentes: Teacher[]; docentesSeleccionados: TeacherProfile[]; fotoPortadaUrl: string | null };
type SectionKey = 'portada' | 'matricula' | 'metodologia' | 'programas' | 'horarios' | 'resultados' | 'docentes' | 'testimonios' | 'contacto' | 'pie';

const sections: { id: SectionKey; label: string; icon: typeof Globe2 }[] = [
    { id: 'portada', label: 'Portada', icon: PanelsTopLeft },
    { id: 'matricula', label: 'Matrícula', icon: CalendarDays },
    { id: 'metodologia', label: 'Metodología', icon: BookOpen },
    { id: 'programas', label: 'Programas', icon: BookOpen },
    { id: 'horarios', label: 'Horarios', icon: Clock3 },
    { id: 'resultados', label: 'Resultados', icon: BarChart3 },
    { id: 'docentes', label: 'Docentes', icon: Users },
    { id: 'testimonios', label: 'Testimonios y FAQ', icon: MessageSquareQuote },
    { id: 'contacto', label: 'Contacto', icon: Globe2 },
    { id: 'pie', label: 'Pie de página', icon: PanelsTopLeft },
];

const panel = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6';
const input = 'mt-1 min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-950 dark:text-white';

function merge(base: RecordValue, override: RecordValue): RecordValue {
    const result: RecordValue = { ...base };
    Object.entries(override).forEach(([key, value]) => {
        result[key] = value && typeof value === 'object' && !Array.isArray(value)
            ? merge(base[key] ?? {}, value)
            : value;
    });
    return result;
}

function setAt(source: RecordValue, path: string[], value: string): RecordValue {
    const [head, ...tail] = path;
    if (!tail.length) return { ...source, [head]: value };
    return { ...source, [head]: setAt(source[head] ?? {}, tail, value) };
}

function Preview({ section, content, docentes, seleccionados, coverUrl, teacherPhotoPreviews }: { section: SectionKey; content: RecordValue; docentes: Teacher[]; seleccionados: TeacherProfile[]; coverUrl: string | null; teacherPhotoPreviews: Record<string, string> }) {
    const label = sections.find((item) => item.id === section)?.label ?? 'Sitio web';
    const selectedTeachers = seleccionados.map((profile) => ({ ...profile, teacher: docentes.find((teacher) => teacher.id_profesor === profile.id_profesor) })).filter((item) => item.teacher);
    const value = (text: unknown, fallback: string) => typeof text === 'string' && text.trim() ? text : fallback;
    const frame = (eyebrow: string, title: string, description: string, children: ReactNode) => <div className="grid gap-4 bg-gradient-to-br from-white via-blue-50/50 to-indigo-100/70 p-4 dark:from-[#071329] dark:via-[#0c1d39] dark:to-[#172554] sm:p-5"><div><p className="text-[9px] font-bold uppercase tracking-[.16em] text-blue-700 dark:text-blue-300">{eyebrow}</p><h3 className="mt-2 text-lg font-bold leading-tight">{title}</h3><p className="mt-2 text-[10px] leading-5 text-slate-600 dark:text-slate-300">{description}</p></div>{children}</div>;
    const mini = (title: string, text: string) => <div className="rounded-lg border border-blue-100 bg-white/80 p-3 dark:border-blue-900 dark:bg-slate-900/70"><p className="text-[9px] font-semibold uppercase tracking-wide text-slate-500">{title}</p><p className="mt-1 text-[10px] font-semibold">{text}</p></div>;
    let preview: ReactNode;

    switch (section) {
        case 'portada':
            preview = <div className="bg-gradient-to-br from-[#071329] via-[#102b4b] to-blue-800 p-5 text-white"><span className="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-[9px] font-semibold">{value(content.hero?.eyebrow, 'Frase destacada')}</span><h3 className="mt-4 text-xl font-bold leading-tight">{value(content.hero?.title, 'El título principal aparecerá aquí')}</h3><p className="mt-2 text-xs leading-5 text-blue-100">{value(content.hero?.description, 'La descripción de portada aparecerá aquí.')}</p>{coverUrl ? <img src={coverUrl} alt="Vista previa de portada" className="mt-4 h-28 w-full rounded-lg object-cover" /> : <div className="mt-4 grid h-24 place-items-center rounded-lg border border-white/15 bg-white/10 text-[10px] text-blue-100">Imagen de portada</div>}<div className="mt-4 flex flex-wrap gap-2"><span className="rounded-lg bg-blue-500 px-3 py-2 text-[10px] font-bold">{value(content.hero?.primary, 'Botón principal')}</span><span className="rounded-lg border border-white/30 px-3 py-2 text-[10px] font-semibold">{value(content.hero?.secondary, 'Botón secundario')}</span></div><div className="mt-5 grid grid-cols-3 gap-2 border-t border-white/15 pt-3">{(content.hero?.stats ?? []).map((stat: RecordValue, index: number) => <div key={index}><p className="text-sm font-bold text-amber-300">{stat.value}</p><p className="text-[9px] leading-4 text-blue-100">{stat.label}</p></div>)}</div></div>;
            break;
        case 'matricula':
            preview = frame('Admisión', 'Matrículas', value(content.admisiones?.mensaje, 'El mensaje para postulantes aparecerá aquí.'), <div className="grid grid-cols-2 gap-2">{mini('Apertura', value(content.admisiones?.fecha_apertura, 'Por definir'))}{mini('Cierre', value(content.admisiones?.fecha_cierre, 'Por definir'))}</div>);
            break;
        case 'metodologia':
            preview = frame(value(content.methodology?.eyebrow, 'Metodología'), value(content.methodology?.title, 'Título de metodología'), value(content.methodology?.description, 'La explicación del método aparecerá aquí.'), <div className="grid gap-2 sm:grid-cols-2">{(content.methodology?.features ?? []).slice(0, 4).map((feature: RecordValue, index: number) => <div key={index} className="rounded-lg border border-blue-100 bg-blue-50/70 p-3 dark:border-blue-900 dark:bg-blue-950/50"><p className="text-[11px] font-bold">{feature.title}</p><p className="mt-1 text-[9px] leading-4 text-slate-600 dark:text-slate-300">{feature.description}</p></div>)}</div>);
            break;
        case 'programas':
            preview = frame(value(content.programs?.eyebrow, 'Programas y ciclos'), value(content.programs?.title, 'Título de programas'), value(content.programs?.description, 'Descripción de los programas'), <><div className="grid gap-2">{(content.programs?.fallback ?? []).slice(0, 3).map((program: RecordValue, index: number) => <div key={index} className="rounded-lg border border-blue-100 p-3 dark:border-blue-900"><span className="rounded-full bg-blue-100 px-2 py-1 text-[9px] font-bold text-blue-800 dark:bg-blue-900 dark:text-blue-200">{program.type}</span><p className="mt-2 text-xs font-bold">{program.name}</p><p className="mt-1 text-[10px] text-slate-500">{program.modality} · {program.dates}</p></div>)}</div><p className="text-[9px] leading-4 text-slate-500">La web prioriza los ciclos vigentes; estos ejemplos se muestran cuando no hay ciclos publicados.</p></>);
            break;
        case 'horarios':
            preview = frame(value(content.schedule?.eyebrow, 'Horarios y modalidades'), value(content.schedule?.title, 'Título de horarios'), value(content.schedule?.description, 'Descripción de horarios'), <div className="grid gap-2 sm:grid-cols-2">{(content.schedule?.options ?? []).map((option: RecordValue, index: number) => <div key={index} className="rounded-lg border border-cyan-100 bg-cyan-50/60 p-3 dark:border-cyan-900 dark:bg-cyan-950/40"><p className="text-xs font-bold">{option.title}</p><p className="mt-1 text-[10px] leading-4 text-slate-600 dark:text-slate-300">{option.time}</p></div>)}</div>);
            break;
        case 'resultados':
            preview = frame(value(content.results?.eyebrow, 'Resultados'), value(content.results?.title, 'Título de resultados'), value(content.results?.description, 'Descripción de resultados'), <><div className="grid grid-cols-3 gap-2">{(content.results?.metrics ?? []).map((metric: RecordValue, index: number) => <div key={index} className="rounded-lg bg-gradient-to-br from-blue-50 to-violet-100 p-3 dark:from-blue-950 dark:to-violet-950"><p className="text-sm font-black text-blue-700 dark:text-blue-200">{metric.value}</p><p className="mt-1 text-[9px] leading-4 text-slate-600 dark:text-slate-300">{metric.label}</p></div>)}</div><p className="text-[9px] text-slate-500">{content.results?.note}</p></>);
            break;
        case 'docentes':
            preview = frame(value(content.faculty?.eyebrow, 'Nuestro equipo'), value(content.faculty?.title, 'Título de docentes'), value(content.faculty?.description, 'Descripción del equipo docente'), <div className="grid gap-2">{selectedTeachers.length ? selectedTeachers.slice(0, 4).map(({ teacher, bio }) => { const photo = teacherPhotoPreviews[String(teacher!.id_profesor)] ?? teacher!.fotoUrl; return <div key={teacher!.id_profesor} className="flex items-center gap-3 rounded-lg border border-blue-100 p-2.5 dark:border-blue-900"><div className="grid size-9 shrink-0 place-items-center overflow-hidden rounded-full bg-gradient-to-br from-cyan-400 to-indigo-600 text-xs font-bold text-white">{photo ? <img src={photo} alt="" className="size-full object-cover" /> : teacher!.nombre.slice(0, 1)}</div><div className="min-w-0"><p className="truncate text-[11px] font-bold">{teacher!.nombre}</p><p className="truncate text-[9px] text-slate-500">{teacher!.cursos.join(' · ')}</p>{bio && <p className="truncate text-[9px] text-slate-600 dark:text-slate-300">{bio}</p>}</div></div>; }) : <p className="rounded-lg bg-slate-100 p-3 text-[10px] text-slate-500 dark:bg-slate-800">Selecciona docentes para verlos aquí.</p>}</div>);
            break;
        case 'testimonios':
            preview = frame(value(content.testimonials?.eyebrow, 'Testimonios'), value(content.testimonials?.title, 'Título de testimonios'), value(content.testimonials?.items?.[0]?.quote, 'El testimonio aparecerá aquí.'), <><p className="text-[10px] font-bold">{content.testimonials?.items?.[0]?.name} <span className="font-normal text-slate-500">· {content.testimonials?.items?.[0]?.detail}</span></p><div className="border-t border-slate-200 pt-3 dark:border-slate-700"><p className="mb-2 text-[10px] font-bold">{value(content.faq?.title, 'Preguntas frecuentes')}</p>{(content.faq?.items ?? []).slice(0, 2).map((item: RecordValue, index: number) => <div key={index} className="border-t border-slate-100 py-2 dark:border-slate-800"><p className="text-[10px] font-semibold">{item.question}</p><p className="mt-1 text-[9px] leading-4 text-slate-500">{item.answer}</p></div>)}</div></>);
            break;
        case 'contacto':
            preview = frame(value(content.contact?.eyebrow, 'Contacto'), value(content.contact?.title, 'Título de contacto'), value(content.contact?.description, 'Descripción de contacto'), <><div className="grid gap-2 sm:grid-cols-2">{mini('Dirección', value(content.contact?.address, 'Dirección de la academia'))}{mini('Horario', value(content.contact?.hours, 'Horario de atención'))}{mini('Teléfono', value(content.contact?.publicPhone, 'No indicado'))}{mini('Correo', value(content.contact?.publicEmail, 'No indicado'))}</div><span className="inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-[10px] font-bold text-white">{value(content.contact?.whatsapp, 'WhatsApp')}</span><div className="border-t border-slate-200 pt-3 text-[9px] text-slate-500 dark:border-slate-700">{content.footer?.description}<br />{content.footer?.copyright}</div></>);
            break;
        case 'pie':
            preview = <div className="bg-[#040916] p-5 text-blue-100"><p className="text-sm font-bold text-white">{value(content.brand, 'Nombre de la academia')}</p><p className="mt-2 text-[10px] leading-5">{value(content.footer?.description, 'La descripción institucional aparecerá aquí.')}</p><div className="mt-4 border-t border-blue-200/15 pt-3"><p className="text-[9px] font-bold uppercase tracking-wide text-white">{value(content.footer?.linksTitle, 'Explora')}</p><div className="mt-2 flex flex-wrap gap-x-3 gap-y-1">{(content.navigation?.links ?? []).map((item: RecordValue, index: number) => <span key={index} className="text-[9px]">{item.label}</span>)}</div></div><p className="mt-4 border-t border-blue-200/15 pt-3 text-[9px] text-blue-200">{value(content.footer?.copyright, 'Aviso legal o copyright')}</p></div>;
            break;
    }

    return <section aria-label={`Vista previa de ${label}`} className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"><header className="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700"><div className="flex items-center gap-2"><Eye aria-hidden="true" className="size-4 text-blue-600 dark:text-blue-300" /><div><h2 className="text-sm font-bold">Vista previa en vivo</h2><p className="text-xs text-slate-500">Sección: {label}</p></div></div><span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Sin publicar</span></header><div className="bg-slate-100 p-3 dark:bg-slate-950 sm:p-4"><div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#070d1b]"><div className="flex h-8 items-center gap-1.5 border-b border-slate-200 bg-white px-3 dark:border-slate-800 dark:bg-slate-900"><span className="size-2 rounded-full bg-rose-400" /><span className="size-2 rounded-full bg-amber-400" /><span className="size-2 rounded-full bg-emerald-400" /><span className="ml-2 h-4 min-w-0 flex-1 truncate rounded bg-slate-100 px-2 text-[9px] leading-4 text-slate-400 dark:bg-slate-800">horizonte.edu.pe/{section}</span></div><div className="max-h-[70vh] min-h-[26rem] overflow-y-auto text-slate-900 dark:text-slate-100"><div className="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800"><span className="truncate text-xs font-black text-blue-900 dark:text-white">{value(content.brand, 'Nombre de la academia')}</span><span className="rounded-lg bg-blue-600 px-2.5 py-1.5 text-[10px] font-bold text-white">{value(content.navigation?.enroll, 'Quiero información')}</span></div>{preview}</div></div><p className="mt-2 text-center text-[10px] text-slate-500">Muestra parcial · los cambios se publican al guardar</p></div></section>;
}

function Section({ title, description, icon, children }: { title: string; description: string; icon: ReactNode; children: ReactNode }) {
    return <section className={panel}><div className="mb-5 flex items-start gap-3"><span className="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">{icon}</span><div><h2 className="font-semibold text-slate-900 dark:text-white">{title}</h2><p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p></div></div><div className="grid gap-4 sm:grid-cols-2">{children}</div></section>;
}

export default function SitioWebIndex({ contenido, docentes, docentesSeleccionados, fotoPortadaUrl }: Props) {
    const [fileWarnings, setFileWarnings] = useState<Record<string, string>>({});
    const [activeSection, setActiveSection] = useState<SectionKey>('portada');
    const [uploadedCoverPreview, setUploadedCoverPreview] = useState<string | null>(null);
    const [uploadedTeacherPreviews, setUploadedTeacherPreviews] = useState<Record<string, string>>({});
    const defaults = merge({
        ...landingContent,
        hero: { ...landingContent.hero, caption: 'Una imagen de estudiantes podrá reemplazar esta ilustración.' },
        admisiones: { fecha_apertura: '', fecha_cierre: '', mensaje: '' },
        contact: { ...landingContent.contact, publicPhone: '', publicEmail: '', phoneLabel: landingContent.contact.phone, emailLabel: landingContent.contact.email },
    }, contenido);
    const form = useForm({
        contenido: defaults,
        docentes: docentesSeleccionados.map(({ id_profesor, bio }) => ({ id_profesor, bio })),
        fotos_docentes: {} as Record<string, File>,
        foto_portada: null as File | null,
    });

    useEffect(() => {
        if (!form.data.foto_portada) {
            setUploadedCoverPreview(null);
            return;
        }

        const previewUrl = URL.createObjectURL(form.data.foto_portada);
        setUploadedCoverPreview(previewUrl);

        return () => URL.revokeObjectURL(previewUrl);
    }, [form.data.foto_portada]);

    useEffect(() => {
        const previews = Object.fromEntries(Object.entries(form.data.fotos_docentes).map(([id, file]) => [id, URL.createObjectURL(file)]));
        setUploadedTeacherPreviews(previews);

        return () => Object.values(previews).forEach((previewUrl) => URL.revokeObjectURL(previewUrl));
    }, [form.data.fotos_docentes]);

    const textField = (label: string, path: string[], multiline = false, type = 'text') => {
        const value = path.reduce<any>((item, part) => item?.[part], form.data.contenido) ?? '';
        return <label key={path.join('.')} className="block text-sm font-medium text-slate-700 dark:text-slate-200">{label}
            {multiline ? <textarea rows={3} className={input} value={value} onChange={(event) => form.setData('contenido', setAt(form.data.contenido, path, event.target.value))} /> : <input type={type} className={input} value={value} onChange={(event) => form.setData('contenido', setAt(form.data.contenido, path, event.target.value))} />}
            {form.errors[`contenido.${path.join('.')}` as keyof typeof form.errors] && <InputError message={form.errors[`contenido.${path.join('.')}` as keyof typeof form.errors]} className="mt-1" />}
        </label>;
    };

    const toggleTeacher = (teacher: Teacher, checked: boolean) => {
        const selected = form.data.docentes;
        form.setData('docentes', checked
            ? [...selected, { id_profesor: teacher.id_profesor, bio: '' }]
            : selected.filter((item) => item.id_profesor !== teacher.id_profesor));
    };

    const onFile = (event: ChangeEvent<HTMLInputElement>, field: string, callback: (file: File | null) => void) => {
        const inputElement = event.currentTarget;
        const file = inputElement.files?.[0] ?? null;

        if (file && file.size > 10 * 1024 * 1024) {
            inputElement.value = '';
            setFileWarnings((warnings) => ({ ...warnings, [field]: 'La imagen supera el límite de 10 MB. Elige un archivo más pequeño.' }));
            callback(null);
            return;
        }

        setFileWarnings((warnings) => ({ ...warnings, [field]: '' }));
        callback(file);
    };
    const updateArrayItem = (path: string[], index: number, property: string, value: string) => {
        const items = path.reduce<any>((item, part) => item?.[part], form.data.contenido) as RecordValue[];
        const next = [...items];
        next[index] = { ...next[index], [property]: value };
        form.setData('contenido', setAt(form.data.contenido, path, next as any));
    };

    return <PageShell title="Contenido del sitio web" description="Edita cada sección por separado y revisa cómo se verá antes de publicar.">
        <form onSubmit={(event) => { event.preventDefault(); form.transform((data) => ({ ...data, _method: 'put' })); form.post(route('sitio-web.update'), { preserveScroll: true, forceFormData: true }); }} className="space-y-5">
            <section className={`${panel} flex flex-wrap items-center justify-between gap-4`}><div><h2 className="font-semibold">Examen de becas</h2><p className="mt-1 text-sm text-slate-500">La convocatoria y sus postulantes se administran desde su propio módulo.</p></div><div className="flex flex-wrap gap-4"><Link href={route('becas.index')} className="font-semibold text-blue-700 dark:text-blue-300">Administrar convocatoria</Link><Link href={route('becas.inscripcion')} className="font-semibold text-blue-700 dark:text-blue-300">Ver inscripción pública</Link></div></section>

            <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(20rem,0.9fr)]">
                <div className="min-w-0 space-y-4">
                    <div role="group" aria-label="Secciones del sitio web" className="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        {sections.map(({ id, label, icon: Icon }) => <button key={id} type="button" aria-pressed={activeSection === id} aria-controls="site-section-panel" onClick={() => setActiveSection(id)} className={`inline-flex min-h-10 shrink-0 items-center gap-2 rounded-xl px-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 ${activeSection === id ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-800 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-200'}`}><Icon aria-hidden="true" className="size-4" />{label}</button>)}
                    </div>

                    <div id="site-section-panel" aria-label={sections.find((section) => section.id === activeSection)?.label}>
                        {activeSection === 'portada' && <Section title="Portada principal" description="Texto, botones, imagen y cifras que aparecen al inicio." icon={<PanelsTopLeft className="size-5" />}>
                            {textField('Nombre público de la academia', ['brand'])}{textField('Frase destacada', ['hero', 'eyebrow'])}{textField('Título', ['hero', 'title'], true)}{textField('Descripción', ['hero', 'description'], true)}{textField('Botón principal', ['hero', 'primary'])}{textField('Botón secundario', ['hero', 'secondary'])}{textField('Etiqueta de la imagen', ['hero', 'visualLabel'])}{textField('Texto bajo la imagen', ['hero', 'caption'], true)}
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Fotografía de portada<input className={`${input} file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-800`} type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => onFile(event, 'foto_portada', (file) => form.setData('foto_portada', file))} /><span className="mt-1 block text-xs text-slate-500">JPG, PNG o WebP · máximo 10 MB.</span>{fileWarnings.foto_portada && <p role="alert" className="mt-1 text-sm text-amber-700 dark:text-amber-300">{fileWarnings.foto_portada}</p>}<InputError message={form.errors.foto_portada} className="mt-1" /></label>
                            {form.data.contenido.hero.stats.map((stat: RecordValue, index: number) => <div key={index} className="grid grid-cols-[7rem_1fr] gap-3 sm:col-span-2"><label className="text-sm font-medium">Cifra {index + 1}<input className={input} value={stat.value} onChange={(event) => { const stats = [...form.data.contenido.hero.stats]; stats[index] = { ...stats[index], value: event.target.value }; form.setData('contenido', setAt(form.data.contenido, ['hero', 'stats'], stats as any)); }} /></label><label className="text-sm font-medium">Descripción<input className={input} value={stat.label} onChange={(event) => { const stats = [...form.data.contenido.hero.stats]; stats[index] = { ...stats[index], label: event.target.value }; form.setData('contenido', setAt(form.data.contenido, ['hero', 'stats'], stats as any)); }} /></label></div>)}
                        </Section>}

                        {activeSection === 'matricula' && <Section title="Fechas de matrícula" description="Publica la ventana informativa para recibir matrículas." icon={<CalendarDays className="size-5" />}>{textField('Apertura de matrícula', ['admisiones', 'fecha_apertura'], false, 'date')}{textField('Cierre de matrícula', ['admisiones', 'fecha_cierre'], false, 'date')}{textField('Mensaje para postulantes', ['admisiones', 'mensaje'], true)}<p className="self-end text-xs text-slate-500">Estas fechas se muestran en la portada; los ciclos mantienen sus fechas académicas propias.</p></Section>}

                        {activeSection === 'metodologia' && <Section title="Metodología" description="Presentación del método académico de la academia." icon={<BookOpen className="size-5" />}>{textField('Antetítulo', ['methodology', 'eyebrow'])}{textField('Título de metodología', ['methodology', 'title'], true)}{textField('Descripción de metodología', ['methodology', 'description'], true)}</Section>}

                        {activeSection === 'programas' && <Section title="Programas" description="Presentación y ciclos de referencia que aparecen en la web." icon={<BookOpen className="size-5" />}>{textField('Antetítulo', ['programs', 'eyebrow'])}{textField('Título de programas', ['programs', 'title'], true)}{textField('Descripción de programas', ['programs', 'description'], true)}{textField('Texto del enlace', ['programs', 'inquire'])}{form.data.contenido.programs.fallback.map((program: RecordValue, index: number) => <div key={index} className="grid gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700 sm:col-span-2 sm:grid-cols-2"><label className="text-sm font-medium">Programa de referencia<input className={input} value={program.name} onChange={(event) => updateArrayItem(['programs', 'fallback'], index, 'name', event.target.value)} /></label><label className="text-sm font-medium">Tipo<input className={input} value={program.type} onChange={(event) => updateArrayItem(['programs', 'fallback'], index, 'type', event.target.value)} /></label><label className="text-sm font-medium">Modalidad<input className={input} value={program.modality} onChange={(event) => updateArrayItem(['programs', 'fallback'], index, 'modality', event.target.value)} /></label><label className="text-sm font-medium">Fechas informativas<input className={input} value={program.dates} onChange={(event) => updateArrayItem(['programs', 'fallback'], index, 'dates', event.target.value)} /></label></div>)}</Section>}

                        {activeSection === 'horarios' && <Section title="Horarios y modalidades" description="Turnos informativos publicados en la página principal." icon={<Clock3 className="size-5" />}>{textField('Antetítulo', ['schedule', 'eyebrow'])}{textField('Título de horarios', ['schedule', 'title'], true)}{textField('Descripción de horarios', ['schedule', 'description'], true)}{form.data.contenido.schedule.options.map((option: RecordValue, index: number) => <div key={index} className="grid gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700 sm:col-span-2 sm:grid-cols-2"><label className="text-sm font-medium">Turno<input className={input} value={option.title} onChange={(event) => updateArrayItem(['schedule', 'options'], index, 'title', event.target.value)} /></label><label className="text-sm font-medium">Horario<input className={input} value={option.time} onChange={(event) => updateArrayItem(['schedule', 'options'], index, 'time', event.target.value)} /></label></div>)}</Section>}

                        {activeSection === 'resultados' && <Section title="Resultados" description="Cifras, título y aclaración visibles en la sección de resultados." icon={<BarChart3 className="size-5" />}>{textField('Antetítulo', ['results', 'eyebrow'])}{textField('Título de resultados', ['results', 'title'], true)}{textField('Descripción de resultados', ['results', 'description'], true)}{form.data.contenido.results.metrics.map((metric: RecordValue, index: number) => <div key={index} className="grid grid-cols-[7rem_1fr] gap-3 sm:col-span-2"><label className="text-sm font-medium">Resultado {index + 1}<input className={input} value={metric.value} onChange={(event) => { const metrics = [...form.data.contenido.results.metrics]; metrics[index] = { ...metrics[index], value: event.target.value }; form.setData('contenido', setAt(form.data.contenido, ['results', 'metrics'], metrics as any)); }} /></label><label className="text-sm font-medium">Descripción<input className={input} value={metric.label} onChange={(event) => { const metrics = [...form.data.contenido.results.metrics]; metrics[index] = { ...metrics[index], label: event.target.value }; form.setData('contenido', setAt(form.data.contenido, ['results', 'metrics'], metrics as any)); }} /></label></div>)}{textField('Nota de resultados', ['results', 'note'], true)}</Section>}

                        {activeSection === 'docentes' && <Section title="Docentes publicados" description="Elige perfiles activos y personaliza su presentación en la web." icon={<Users className="size-5" />}>{textField('Antetítulo', ['faculty', 'eyebrow'])}{textField('Título de la sección', ['faculty', 'title'], true)}{textField('Descripción de la sección', ['faculty', 'description'], true)}<div className="space-y-3 sm:col-span-2">{docentes.map((teacher) => { const profile = form.data.docentes.find((item) => item.id_profesor === teacher.id_profesor); return <article key={teacher.id_profesor} className="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><label className="flex cursor-pointer items-start gap-3"><input type="checkbox" checked={Boolean(profile)} onChange={(event) => toggleTeacher(teacher, event.target.checked)} className="mt-1 size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600" /><span className="font-semibold">{teacher.nombre}<span className="mt-1 block text-xs font-normal text-slate-500">{teacher.cursos.join(' · ') || 'Sin cursos asociados'}</span></span></label>{profile && <div className="mt-3 grid gap-3 sm:grid-cols-2"><label className="text-sm font-medium">Presentación<textarea rows={2} className={input} value={profile.bio} onChange={(event) => form.setData('docentes', form.data.docentes.map((item) => item.id_profesor === teacher.id_profesor ? { ...item, bio: event.target.value } : item))} /></label><label className="text-sm font-medium">Fotografía<input type="file" accept="image/jpeg,image/png,image/webp" className={input} onChange={(event) => onFile(event, `docente_${teacher.id_profesor}`, (file) => { if (file) form.setData('fotos_docentes', { ...form.data.fotos_docentes, [teacher.id_profesor]: file }); })} /><span className="text-xs text-slate-500">JPG, PNG o WebP · máximo 10 MB.</span>{fileWarnings[`docente_${teacher.id_profesor}`] && <p role="alert" className="mt-1 text-sm text-amber-700 dark:text-amber-300">{fileWarnings[`docente_${teacher.id_profesor}`]}</p>}<InputError message={form.errors[`fotos_docentes.${teacher.id_profesor}`]} className="mt-1" />{teacher.fotoUrl && <img src={teacher.fotoUrl} alt={`Foto actual de ${teacher.nombre}`} className="mt-2 size-16 rounded-xl object-cover" />}</label></div>}</article>; })}{docentes.length === 0 && <p className="text-sm text-slate-500">Registra docentes activos en el módulo Docentes para poder publicarlos aquí.</p>}</div></Section>}

                        {activeSection === 'testimonios' && <Section title="Testimonios y preguntas frecuentes" description="Edita las experiencias y respuestas que se publicarán en la web." icon={<MessageSquareQuote className="size-5" />}>{textField('Antetítulo de testimonios', ['testimonials', 'eyebrow'])}{textField('Título de testimonios', ['testimonials', 'title'], true)}{form.data.contenido.testimonials.items.map((item: RecordValue, index: number) => <div key={index} className="grid gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700 sm:col-span-2"><label className="text-sm font-medium">Testimonio<textarea rows={2} className={input} value={item.quote} onChange={(event) => updateArrayItem(['testimonials', 'items'], index, 'quote', event.target.value)} /></label><div className="grid gap-3 sm:grid-cols-2"><label className="text-sm font-medium">Nombre<input className={input} value={item.name} onChange={(event) => updateArrayItem(['testimonials', 'items'], index, 'name', event.target.value)} /></label><label className="text-sm font-medium">Detalle<input className={input} value={item.detail} onChange={(event) => updateArrayItem(['testimonials', 'items'], index, 'detail', event.target.value)} /></label></div></div>)}{textField('Antetítulo de preguntas', ['faq', 'eyebrow'])}{textField('Título de preguntas frecuentes', ['faq', 'title'], true)}{form.data.contenido.faq.items.map((item: RecordValue, index: number) => <div key={index} className="grid gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700 sm:col-span-2"><label className="text-sm font-medium">Pregunta<textarea rows={2} className={input} value={item.question} onChange={(event) => updateArrayItem(['faq', 'items'], index, 'question', event.target.value)} /></label><label className="text-sm font-medium">Respuesta<textarea rows={2} className={input} value={item.answer} onChange={(event) => updateArrayItem(['faq', 'items'], index, 'answer', event.target.value)} /></label></div>)}</Section>}

                        {activeSection === 'contacto' && <Section title="Contacto público" description="Datos que las familias verán y formulario de consultas." icon={<Globe2 className="size-5" />}>{textField('Antetítulo', ['contact', 'eyebrow'])}{textField('Título de contacto', ['contact', 'title'], true)}{textField('Descripción de contacto', ['contact', 'description'], true)}{textField('Dirección', ['contact', 'address'])}{textField('Horario de atención', ['contact', 'hours'])}{textField('Teléfono público', ['contact', 'publicPhone'])}{textField('Correo público', ['contact', 'publicEmail'])}{textField('Etiqueta del teléfono en el formulario', ['contact', 'phoneLabel'])}{textField('Etiqueta del correo en el formulario', ['contact', 'emailLabel'])}{textField('Texto del botón WhatsApp', ['contact', 'whatsapp'])}{textField('Enlace de WhatsApp', ['contact', 'whatsappUrl'])}{textField('Texto del botón enviar', ['contact', 'submit'])}</Section>}
                        {activeSection === 'pie' && <Section title="Pie de página" description="Información institucional y enlaces al final del sitio." icon={<PanelsTopLeft className="size-5" />}>{textField('Título de enlaces', ['footer', 'linksTitle'])}{textField('Descripción del pie de página', ['footer', 'description'], true)}{textField('Aviso legal o copyright', ['footer', 'copyright'])}</Section>}
                    </div>
                    {form.errors.docentes && <InputError message={form.errors.docentes} />}
                    <p className="text-xs text-slate-500">La vista previa es privada hasta que pulses «Guardar y publicar».</p>
                    <div className="flex justify-end"><PrimaryButton disabled={form.processing}>{form.processing ? 'Guardando…' : <><Save className="mr-2 size-4" />Guardar y publicar</>}</PrimaryButton></div>
                </div>

                <aside className="min-w-0 xl:sticky xl:top-20">
                    <Preview section={activeSection} content={form.data.contenido} docentes={docentes} seleccionados={form.data.docentes} coverUrl={uploadedCoverPreview ?? fotoPortadaUrl} teacherPhotoPreviews={uploadedTeacherPreviews} />
                </aside>
            </div>
        </form>
    </PageShell>;
}
