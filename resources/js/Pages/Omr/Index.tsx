import OmrUpload from '@/Components/OmrUpload';
import { router, useForm, usePoll, usePage } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import OmrCartilla, { compactQuestions, type CartillaConfig } from '@/Components/OmrCartilla';
import { AlertCircle, CheckCircle2, Download, FileUp, Plus, RefreshCw, ScanLine } from 'lucide-react';
import { FormEvent, useMemo, useState, useEffect } from 'react';
import EmptyState from '@/Components/EmptyState';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatCard from '@/Components/StatCard';
import type { PostulanteBeca, ConvocatoriaBeca } from '@/types/becas';
import { Paginated } from '@/types/academia';

type Simulacro = { id_simulacro: number; titulo: string; id_ciclo: number; ciclo?: { nombre_ciclo: string } };
type Curso = { id_curso: number; nombre_curso: string };
type Clave = { id_clave: number; numero_pregunta: number; id_curso: number; respuesta_correcta: string; puntos_correcta: string; puntos_incorrecta: string; curso?: Curso };
type Plantilla = { id_plantilla: number; nombre: string; num_preguntas: number; opciones_por_pregunta?: number; config: CartillaConfig };
type Matricula = { id_matricula: number; estudiante: { nombre: string; apellido: string; dni: string }; grupo?: { nombre: string } | null };
type Respuesta = { numero_pregunta: number; marcada: string | null; estado: 'valida' | 'blanco' | 'multiple' | 'dudosa' };
type Hoja = {
    id_hoja: number; id_matricula: number | null; codigo_hoja: string; estado: string;
    resumen_lectura: { correctas: number; incorrectas: number; en_blanco: number; por_revisar: number }; nota_omr: string | null; confianza: string | null; archivo_path: string | null; created_at: string;
    postulante?: Omit<PostulanteBeca, 'telefono'> | null;
    matricula?: Matricula | null; respuestas: Respuesta[]; plantillahoja: Plantilla;
};
type Props = {
    simulacros: Simulacro[]; simulacroSeleccionado: Simulacro | null; claves: Clave[]; cursos: Curso[];
    convocatoriaBeca: Pick<ConvocatoriaBeca, 'id_convocatoria' | 'nombre' | 'estado' | 'id_simulacro_ab' | 'id_simulacro_cd'> | null;
    plantillas: Plantilla[];
    matriculas: Matricula[]; hojas: Paginated<Hoja>;
    filters: { id_simulacro?: number; estado?: string };
    omr: { available: boolean; message: string };
    stats: { total: number; pendientes: number; revision: number; errores: number; procesadas: number };
};

const inputClass = 'min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100';
const cardClass = 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5';

function BubbleKey({ rows, count, onMark }: { rows: Clave[]; count: number; onMark: (index: number, answer: string) => void }) {
    if (rows.length <= 80) return <div className="space-y-3"><h3 className="font-semibold">Marca la clave en la cartilla</h3><p className="text-sm text-slate-500">Pulsa las burbujas de respuestas. Se rellenan de negro; la zona del DNI queda vacía para el postulante.</p><div className="overflow-x-auto rounded-lg border border-slate-200 bg-white" role="region" aria-label="Cartilla horizontal de claves" tabIndex={0}><OmrCartilla config={{ titulo: 'CLAVE DE RESPUESTAS', questions: compactQuestions(rows.map(row => row.numero_pregunta), count) }} selected={Object.fromEntries(rows.map(row => [row.numero_pregunta, row.respuesta_correcta]))} onMark={(number, answer) => onMark(rows.findIndex(row => row.numero_pregunta === number), answer)} /></div><p className="text-sm text-slate-500">{rows.filter(row => row.respuesta_correcta).length} de {rows.length} respuestas marcadas. En móvil, desliza la cartilla.</p></div>;
    const blocks = Array.from({ length: Math.ceil(rows.length / 20) }, (_, block) => rows.slice(block * 20, block * 20 + 20));
    return <div className="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
        <h3 className="font-semibold">Marca la clave en la cartilla</h3>
        <p className="mt-1 text-sm text-slate-500">Pulsa una burbuja por pregunta. El punto negro indica la respuesta correcta. Puedes cambiarla pulsando otra.</p>
        <div className="mt-4 overflow-x-auto" role="region" aria-label="Cartilla horizontal de claves" tabIndex={0}>
            <div className="flex w-max gap-6 pb-3">
                {blocks.map((block, blockIndex) => <fieldset key={blockIndex} className="space-y-2">
                    <legend className="mb-3 text-sm font-semibold">Preguntas {block[0].numero_pregunta}–{block[block.length - 1].numero_pregunta}</legend>
                    {block.map((row, offset) => <div key={row.numero_pregunta} className="flex items-center gap-2">
                        <span className="w-8 text-sm font-medium">{row.numero_pregunta}.</span>
                        {['A', 'B', 'C', 'D', 'E'].slice(0, count).map(answer => <button key={answer} type="button" aria-label={`Pregunta ${row.numero_pregunta}, alternativa ${answer}`} aria-pressed={row.respuesta_correcta === answer} onClick={() => onMark(blockIndex * 20 + offset, answer)} className={`flex size-10 items-center justify-center rounded-full border-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 ${row.respuesta_correcta === answer ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black' : 'border-slate-400 bg-white text-slate-700 hover:border-blue-500 dark:bg-slate-900 dark:text-slate-200'}`}>{answer}</button>)}
                    </div>)}
                </fieldset>)}
            </div>
        </div>
        <p className="mt-2 text-sm text-slate-500">{rows.filter(row => row.respuesta_correcta).length} de {rows.length} respuestas marcadas. En móvil, desliza la cartilla horizontalmente.</p>
    </div>;
}

function CartillaPreview({ plantilla }: { plantilla: Plantilla }) {
    const [open, setOpen] = useState(false);
    const id = `cartilla-${plantilla.id_plantilla}`;
    function download() {
        const svg = document.getElementById(id);
        if (!svg) return;
        const url = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml;charset=utf-8' }));
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = `cartilla-${plantilla.id_plantilla}.svg`;
        anchor.click();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }
    const instructions = plantilla.config.instrucciones ?? 'Rellena una sola alternativa por pregunta.';
    const lines = instructions.match(/.{1,75}(?:\s|$)|.{1,75}/g) ?? [];
    return <article className="mt-4 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
        <div className="flex flex-wrap items-center justify-between gap-2">
            <p className="text-sm font-semibold">{plantilla.nombre} · {plantilla.num_preguntas} preguntas</p>
            <SecondaryButton type="button" onClick={() => setOpen(!open)}>{open ? 'Ocultar cartilla' : 'Ver cartilla'}</SecondaryButton>
        </div>
        <div className={open ? 'mt-3' : 'hidden'}>
            {plantilla.config.diseno === 'compacto' ? <OmrCartilla id={id} config={plantilla.config} /> : <svg id={id} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1654 2339" width="210mm" height="297mm" className="h-auto w-full rounded-lg border border-slate-200 bg-white" role="img" aria-label={`Cartilla de ${plantilla.nombre}`}>
                <rect width="1654" height="2339" fill="white" />
                {[[50, 50], [1572, 50], [50, 2257], [1572, 2257]].map(([x, y]) => <rect key={`${x}-${y}`} x={x} y={y} width="32" height="32" fill="black" />)}
                <g fill="black" fontFamily="Arial, sans-serif">
                    <text x="160" y="140" fontSize="30" fontWeight="bold">{plantilla.config.titulo ?? plantilla.nombre}</text>
                    <text x="160" y="205" fontSize="24">DNI: ___________________    Nombres: __________________________________</text>
                    <text x="160" y="255" fontSize="24">Fecha: __________________    Código de hoja: ____________________________</text>
                    {lines.map((line, i) => <text key={i} x="160" y={300 + i * 25} fontSize="20">{line.trim()}</text>)}
                    {plantilla.config.questions.map((question, i) => {
                        const x = question.label_x ?? (i < 60 ? 160 : 850);
                        const y = question.y ?? (400 + (i % 60) * 30);
                        const options = question.opciones ?? ['A', 'B', 'C', 'D', 'E'].map((label, j) => ({ label, x: x + 100 + j * 42, y }));
                        return <g key={question.numero_pregunta}>
                            <text x={x} y={y + 6} fontSize="20">{question.numero_pregunta}.</text>
                            {options.map(option => <g key={option.label}><circle cx={option.x} cy={option.y} r="10" fill="white" stroke="black" strokeWidth="1.5" /><text x={option.x} y={option.y + 4} textAnchor="middle" fontSize="12">{option.label}</text></g>)}
                        </g>;
                    })}
                </g>
            </svg>}
            <SecondaryButton type="button" className="mt-3" onClick={download}><Download className="mr-2 size-4" />Descargar cartilla SVG · A4</SecondaryButton>
            <p className="mt-2 text-xs text-slate-500">Abre el archivo en el navegador e imprime en A4 al 100 %. Las claves correctas no se incluyen.</p>
        </div>
    </article>;
}

function ReviewSheet({ hoja, matriculas }: { hoja: Hoja; matriculas: Matricula[] }) {
    const seeded = useMemo(() => {
        const current = new Map(hoja.respuestas.map((answer) => [answer.numero_pregunta, answer]));
        return hoja.plantillahoja.config.questions.map((question) => current.get(question.numero_pregunta) ?? {
            numero_pregunta: question.numero_pregunta, marcada: null, estado: 'dudosa' as const,
        });
    }, [hoja]);
    const form = useForm({
        id_matricula: hoja.id_matricula ? String(hoja.id_matricula) : '',
        dni_postulante: hoja.postulante?.dni ?? '',
        motivo: '',
        respuestas: seeded,
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.patch(route('omr.hojas.update', hoja.id_hoja), { preserveScroll: true });
    }

    const setAnswer = (index: number, field: 'marcada' | 'estado', value: string) => {
        form.setData('respuestas', form.data.respuestas.map((answer, answerIndex) => answerIndex === index
            ? { ...answer, [field]: value, ...(field === 'marcada' ? { estado: value ? 'valida' as const : 'blanco' as const, marcada: value || null } : {}), ...(field === 'estado' && value !== 'valida' ? { marcada: null } : {}) }
            : answer));
    };

    return (
        <details className={cardClass}>
            <summary className="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="font-semibold text-slate-900 dark:text-white">{hoja.codigo_hoja}</p>
                    <p className="text-sm text-slate-500">{hoja.postulante ? `DNI ${hoja.postulante.dni} · Grupo ${hoja.postulante.grupo} · Nota ${hoja.postulante.nota ?? 'Pendiente'}` : hoja.matricula ? `${hoja.matricula.estudiante.nombre} ${hoja.matricula.estudiante.apellido} · DNI ${hoja.matricula.estudiante.dni} · Nota ${hoja.nota_omr ?? 'Pendiente'}` : 'Sin estudiante asignado'} · Confianza {hoja.confianza ?? '—'}%</p>
                </div>
                <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-200">Requiere revisión</span>
            </summary>
            <form onSubmit={submit}>
            <div className="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 p-2 dark:border-slate-700 dark:bg-slate-950">
                <p className="mb-2 text-xs font-medium text-slate-500">Imagen original para revisar las marcas de respuestas.</p>
                <img src={route('omr.hojas.preview', hoja.id_hoja)} alt={`Hoja ${hoja.codigo_hoja} con respuestas dudosas resaltadas`} className="mx-auto max-h-[28rem] w-full object-contain" onError={(event) => { event.currentTarget.parentElement?.classList.add('hidden'); }} />
            </div>
            <div className="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
                <div className="space-y-3">
                    {hoja.archivo_path && <a className="inline-flex items-center gap-2 text-sm font-medium text-blue-700 hover:underline dark:text-blue-300" href={route('omr.hojas.archivo', hoja.id_hoja)}><Download className="size-4" />Descargar imagen escaneada</a>}
                    <label className="block text-sm font-medium">DNI del postulante a beca<input className={inputClass} inputMode="numeric" maxLength={8} value={form.data.dni_postulante} onChange={e => { form.setData('dni_postulante', e.target.value.replace(/\D/g, '')); if (e.target.value) form.setData('id_matricula', ''); }} /><span className="text-xs text-slate-500">Se buscará en la convocatoria y agrupación de este examen al guardar.</span>{form.errors.dni_postulante && <p role="alert" className="text-sm text-red-600">{form.errors.dni_postulante}</p>}</label>
                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Estudiante
                        <select className={`${inputClass} mt-1`} disabled={!!form.data.dni_postulante} value={form.data.id_matricula} onChange={(event) => form.setData('id_matricula', event.target.value)}>
                            <option value="">Asignar manualmente</option>
                            {matriculas.map((matricula) => <option key={matricula.id_matricula} value={matricula.id_matricula}>{matricula.estudiante.apellido}, {matricula.estudiante.nombre} · {matricula.grupo?.nombre ?? 'Sin grupo'}</option>)}
                        </select>
                    </label>
                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Motivo de revisión (obligatorio)
                        <input className={`${inputClass} mt-1`} value={form.data.motivo} onChange={(event) => form.setData('motivo', event.target.value)} maxLength={200} required placeholder="Ej. Asignación de estudiante y validación de marcas" />
                    </label>
                </div>
                <div className="max-h-80 space-y-2 overflow-auto rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                    {form.data.respuestas.map((answer, index) => (
                        <div key={answer.numero_pregunta} className="grid grid-cols-[3rem_minmax(0,1fr)_minmax(0,1fr)] items-center gap-2 text-sm">
                            <span className="font-semibold text-slate-600 dark:text-slate-300">{answer.numero_pregunta}</span>
                            <select aria-label={`Alternativa de pregunta ${answer.numero_pregunta}`} className={inputClass} value={answer.marcada ?? ''} onChange={(event) => setAnswer(index, 'marcada', event.target.value)}>
                                <option value="">Sin marcar</option>{['A', 'B', 'C', 'D', 'E'].slice(0, hoja.plantillahoja.opciones_por_pregunta ?? 5).map((option) => <option key={option}>{option}</option>)}
                            </select>
                            <select aria-label={`Estado de pregunta ${answer.numero_pregunta}`} className={inputClass} value={answer.estado} onChange={(event) => setAnswer(index, 'estado', event.target.value)}>
                                <option value="valida">Válida</option><option value="blanco">En blanco</option><option value="multiple">Múltiple</option><option value="dudosa">Dudosa</option>
                            </select>
                        </div>
                    ))}
                </div>
            </div>
            {form.errors.respuestas && <p role="alert" className="text-sm text-red-600">{form.errors.respuestas}</p>}
            {form.errors.id_matricula && <p role="alert" className="text-sm text-red-600">{form.errors.id_matricula}</p>}
            {form.errors.motivo && <p className="mt-2 text-sm text-red-600">{form.errors.motivo}</p>}
            <div className="mt-4 flex justify-end"><PrimaryButton disabled={form.processing}>{form.processing ? 'Guardando…' : 'Guardar revisión y calificar'}</PrimaryButton></div>
            </form>
        </details>
    );
}

export default function OmrIndex({ simulacros, simulacroSeleccionado, claves, cursos: availableCourses, plantillas, matriculas, hojas, filters, omr, stats, convocatoriaBeca }: Props) {
    const canConfigure = usePage().props.auth.permissions.includes('usuarios.gestionar');
    const [keyRows, setKeyRows] = useState<Clave[]>(claves);
    const [optionCount, setOptionCount] = useState(claves.some(row => row.respuesta_correcta === 'E') ? 5 : 4);
    const [quantity, setQuantity] = useState(claves.length || 80);
    const [defaultCorrect, setDefaultCorrect] = useState('1.00');
    const [defaultIncorrect, setDefaultIncorrect] = useState('-0.25');
    const [defaultCourse, setDefaultCourse] = useState(availableCourses[0]?.id_curso ?? 0);
    const keyForm = useForm<{ claves: Clave[] }>({ claves });
    useEffect(() => { setKeyRows(claves); }, [claves]);
    const [keyImportError, setKeyImportError] = useState<string | null>(null);
    usePoll(5000, { only: ['hojas', 'stats'] });
    const cursos = availableCourses;
    const selectedId = simulacroSeleccionado?.id_simulacro;

    function saveKeys(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        keyForm.transform(() => ({ claves: keyRows }));
        keyForm.post(route('omr.claves.store', selectedId), { preserveScroll: true });
    }

    async function importKeys(file?: File) {
        if (!file) return;
        try {
            const lines = (await file.text()).split(/\r?\n/).filter((line) => line.trim() !== '');
            const parseRow = (line: string) => line.split(/,(?=(?:[^"]*"[^"]*")*[^"]*$)/).map((value) => value.trim().replace(/^"|"$/g, '').replace(/""/g, '"'));
            const headers = parseRow(lines[0]).map((header) => header.toLowerCase());
            const required = ['numero_pregunta', 'id_curso', 'respuesta_correcta', 'puntos_correcta', 'puntos_incorrecta'];
            if (required.some((header) => !headers.includes(header))) {
                throw new Error(`El CSV debe incluir: ${required.join(', ')}.`);
            }
            const imported = lines.slice(1).map((line) => {
                const values = parseRow(line);
                const value = (header: string) => values[headers.indexOf(header)] ?? '';
                return {
                    id_clave: 0,
                    numero_pregunta: Number(value('numero_pregunta')),
                    id_curso: Number(value('id_curso')),
                    respuesta_correcta: value('respuesta_correcta').toUpperCase(),
                    puntos_correcta: value('puntos_correcta'),
                    puntos_incorrecta: value('puntos_incorrecta'),
                };
            });
            if (imported.length === 0 || imported.some((row) => !Number.isInteger(row.numero_pregunta) || !Number.isInteger(row.id_curso) || !['A', 'B', 'C', 'D', 'E'].includes(row.respuesta_correcta))) {
                throw new Error('El archivo no contiene filas válidas. Revisa números de pregunta, IDs de curso y alternativas A–E.');
            }
            setKeyRows(imported);
            if (imported.some(row => row.respuesta_correcta === 'E')) setOptionCount(5);
            setKeyImportError(null);
        } catch (error) {
            setKeyImportError(error instanceof Error ? error.message : 'No se pudo leer el archivo CSV.');
        }
    }

    return (
        <PageShell title="Lectura de hojas OMR" description="Genera hojas, procesa escaneos y revisa respuestas antes de publicar resultados.">
            <div className="space-y-5">
                <section className={cardClass}>
                    <label className="block max-w-2xl text-sm font-medium text-slate-700 dark:text-slate-300">Simulacro
                        <select className={`${inputClass} mt-1`} value={selectedId ?? ''} onChange={(event) => router.get(route('omr.index'), { id_simulacro: event.target.value }, { preserveState: false })}>
                            {simulacros.map((simulacro) => <option key={simulacro.id_simulacro} value={simulacro.id_simulacro}>{simulacro.titulo} · {simulacro.ciclo?.nombre_ciclo ?? 'Ciclo'}</option>)}
                        </select>
                    </label>
                </section>
                {convocatoriaBeca && <nav aria-label="Claves por agrupación" className="flex flex-wrap gap-3">
                    {(['ab', 'cd'] as const).filter(pair => convocatoriaBeca.id_simulacro_ab !== convocatoriaBeca.id_simulacro_cd || pair === 'ab').map(pair => { const id = pair === 'ab' ? convocatoriaBeca.id_simulacro_ab : convocatoriaBeca.id_simulacro_cd; return id && <a key={pair} href={route('omr.index', { id_simulacro: id })} aria-current={id === selectedId ? 'page' : undefined} className={id === selectedId ? 'rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white' : 'rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-700'}>Clave y cartilla {convocatoriaBeca.id_simulacro_ab === convocatoriaBeca.id_simulacro_cd ? 'unificada A–B–C–D' : pair === 'ab' ? 'A–B' : 'C–D'}</a>; })}
                    {canConfigure && <a href={route('becas.index', { id_convocatoria: convocatoriaBeca.id_convocatoria })} className="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-700">Configurar modalidad de clave</a>}
                </nav>}
                {convocatoriaBeca && <section className={cardClass}><h2 className="font-semibold">{convocatoriaBeca.nombre} · Grupos {convocatoriaBeca.id_simulacro_ab === convocatoriaBeca.id_simulacro_cd ? 'A–B–C–D · Clave unificada' : convocatoriaBeca.id_simulacro_ab === selectedId ? 'A–B' : 'C–D'}</h2><p className="mt-1 text-sm text-slate-500">La cámara lee el DNI y el grupo de las burbujas. La nota se guarda en el registro del postulante; puedes corregir las lecturas dudosas.</p></section>}
                {!simulacroSeleccionado ? <EmptyState title="No hay simulacros disponibles" description="Registra un simulacro antes de preparar hojas de respuestas." /> : <>
                    <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
                        <StatCard title="Hojas" value={stats.total} icon={ScanLine} />
                        <StatCard title="Pendientes" value={stats.pendientes} icon={RefreshCw} />
                        <StatCard title="Por revisar" value={stats.revision} icon={AlertCircle} />
                        <StatCard title="Procesadas" value={stats.procesadas} icon={CheckCircle2} />
                        <StatCard title="Errores" value={stats.errores} icon={AlertCircle} />
                    </div>

                    <div className={`rounded-xl border p-4 text-sm ${omr.available ? 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100' : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100'}`} role="status">
                        <p className="font-semibold">{omr.available ? 'Procesador disponible' : 'Carga manual disponible'}</p><p>{omr.message}</p>
                    </div>

                    <div className="grid gap-5 xl:grid-cols-2">
                        <section className={cardClass}>
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Clave de respuestas</h2>
                            <p className="mt-1 text-sm text-slate-500">Una pregunta por fila. Guardar recalifica las hojas procesadas.</p>
                            <label className="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                <FileUp className="size-4" /> Importar clave CSV
                                <input className="sr-only" type="file" accept=".csv,text/csv" onChange={(event) => { void importKeys(event.target.files?.[0]); event.currentTarget.value = ''; }} />
                            </label>
                            <p className="mt-2 text-xs text-slate-500">Columnas obligatorias: numero_pregunta, id_curso, respuesta_correcta, puntos_correcta, puntos_incorrecta.</p>
                            {keyImportError && <p className="mt-2 text-sm text-red-600" role="alert">{keyImportError}</p>}
                            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                <label className="text-sm font-medium">Alternativas<select className={inputClass} value={optionCount} onChange={e => { const count = Number(e.target.value); if (count === 4 && keyRows.some(row => row.respuesta_correcta === 'E')) { if (!window.confirm('Las preguntas marcadas con E quedarán sin respuesta. ¿Cambiar a cuatro alternativas?')) return; setKeyRows(rows => rows.map(row => row.respuesta_correcta === 'E' ? { ...row, respuesta_correcta: '' } : row)); } setOptionCount(count); }}><option value="4">A–D · como la referencia</option><option value="5">A–E · cinco alternativas</option></select></label>
                                <label className="text-sm font-medium">Cantidad de preguntas (1–120)<input className={inputClass} type="number" min="1" max="120" value={quantity} onChange={e => setQuantity(Number(e.target.value))} /></label>
                                <label className="text-sm font-medium">Curso inicial<select className={inputClass} value={defaultCourse} onChange={e => setDefaultCourse(Number(e.target.value))}>{cursos.map(c => <option key={c.id_curso} value={c.id_curso}>{c.nombre_curso}</option>)}</select></label>
                                <label className="text-sm font-medium">Puntos por respuesta correcta<input className={inputClass} type="number" min="0" step="0.01" value={defaultCorrect} onChange={e => setDefaultCorrect(e.target.value)} /></label>
                                <label className="text-sm font-medium">Puntos por respuesta incorrecta<input className={inputClass} type="number" min="-100" max="0" step="0.01" value={defaultIncorrect} onChange={e => setDefaultIncorrect(e.target.value)} /></label>
                                <SecondaryButton type="button" disabled={!defaultCourse || !Number.isInteger(quantity) || quantity < 1 || quantity > 120} onClick={() => {
                                    if (quantity < keyRows.length && !window.confirm('Se quitarán las últimas preguntas de la clave en edición. ¿Continuar?')) return;
                                    setKeyRows(rows => Array.from({ length: quantity }, (_, i) => rows[i] ? { ...rows[i], numero_pregunta: i + 1 } : { id_clave: 0, numero_pregunta: i + 1, id_curso: defaultCourse, respuesta_correcta: '', puntos_correcta: defaultCorrect, puntos_incorrecta: defaultIncorrect }));
                                }}>Generar cantidad de preguntas</SecondaryButton>
                                <SecondaryButton type="button" disabled={!keyRows.length} onClick={() => {
                                    if (window.confirm('¿Aplicar estos puntajes a todas las preguntas de la clave en edición?')) setKeyRows(rows => rows.map(row => ({ ...row, puntos_correcta: defaultCorrect, puntos_incorrecta: defaultIncorrect })));
                                }}>Aplicar puntajes a todas</SecondaryButton>
                            </div>
                            <p className="mt-3 text-sm text-slate-500">Las preguntas nuevas quedan sin marcar: selecciona la respuesta correcta en la cartilla. En blanco: 0 puntos. Máximo de la clave: {keyRows.reduce((sum, row) => sum + (Number(row.puntos_correcta) || 0), 0).toFixed(2)} puntos.</p>
                            <form onSubmit={saveKeys} className="mt-4 space-y-3">
                                <div className="flex justify-end"><SecondaryButton type="button" disabled={cursos.length === 0 || keyRows.length >= 120} onClick={() => setKeyRows((rows) => [...rows, { id_clave: 0, numero_pregunta: Math.max(0, ...rows.map((row) => row.numero_pregunta)) + 1, id_curso: cursos[0].id_curso, respuesta_correcta: '', puntos_correcta: '1.00', puntos_incorrecta: '0.00' }])}><Plus className="mr-2 size-4" />Agregar pregunta</SecondaryButton></div>
                                {keyRows.length > 0 && <BubbleKey rows={keyRows} count={optionCount} onMark={(index, answer) => setKeyRows(rows => rows.map((row, i) => i === index ? { ...row, respuesta_correcta: answer } : row))} />}
                                <details><summary className="cursor-pointer text-sm font-semibold">Configuración por pregunta: curso y puntajes</summary>
                                <div className="max-h-96 space-y-2 overflow-auto pr-1">
                                    {keyRows.map((key, index) => <div key={key.numero_pregunta} className="grid grid-cols-2 items-center gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700 sm:grid-cols-[3.5rem_minmax(0,1fr)_4rem_6rem_6rem]">
                                        <span className="text-sm font-semibold">N.º {key.numero_pregunta}</span>
                                        <select className={inputClass} value={key.id_curso} aria-label={`Curso de pregunta ${key.numero_pregunta}`} onChange={(event) => setKeyRows((rows) => rows.map((row, rowIndex) => rowIndex === index ? { ...row, id_curso: Number(event.target.value) } : row))}>
                                            {cursos.map((curso) => <option key={curso.id_curso} value={curso.id_curso}>{curso.nombre_curso}</option>)}
                                        </select>
                                        <select className={inputClass} value={key.respuesta_correcta} aria-label={`Clave de pregunta ${key.numero_pregunta}`} onChange={(event) => setKeyRows((rows) => rows.map((row, rowIndex) => rowIndex === index ? { ...row, respuesta_correcta: event.target.value } : row))}><option value="">Sin marcar</option>{['A', 'B', 'C', 'D', 'E'].slice(0, optionCount).map((option) => <option key={option}>{option}</option>)}</select>
                                        <label className="text-xs text-slate-500">Acierto<input className={inputClass} aria-label={`Puntos por acierto ${key.numero_pregunta}`} type="number" min="0" step="0.01" value={key.puntos_correcta} onChange={(event) => setKeyRows((rows) => rows.map((row, rowIndex) => rowIndex === index ? { ...row, puntos_correcta: event.target.value } : row))} /></label>
                                        <label className="text-xs text-slate-500">Error<input className={inputClass} aria-label={`Puntos por error ${key.numero_pregunta}`} type="number" min="-100" max="0" step="0.01" value={key.puntos_incorrecta} onChange={(event) => setKeyRows((rows) => rows.map((row, rowIndex) => rowIndex === index ? { ...row, puntos_incorrecta: event.target.value } : row))} /></label>
                                    </div>)}
                                    {keyRows.length === 0 && <p className="text-sm text-slate-500">Agrega las preguntas y asigna cada una a su curso.</p>}
                                </div>
                                </details>
                                {Object.entries(keyForm.errors).map(([field, message]) => <p key={field} className="text-sm text-red-600" role="alert">{field}: {message}</p>)}
                                <PrimaryButton disabled={keyRows.length === 0 || keyRows.some(row => !row.respuesta_correcta) || keyForm.processing}>{keyForm.processing ? 'Guardando…' : 'Guardar clave y recalificar'}</PrimaryButton>
                            </form>
                            {keyRows.length > 0 && <CartillaPreview plantilla={{
                                id_plantilla: 0, nombre: 'Vista previa de preguntas en edición', num_preguntas: keyRows.length,
                                config: keyRows.length <= 80 ? { diseno: 'compacto', titulo: simulacroSeleccionado.titulo, questions: compactQuestions(keyRows.map(row => row.numero_pregunta), optionCount) } : { titulo: simulacroSeleccionado.titulo, questions: keyRows.map((row, i) => ({ numero_pregunta: row.numero_pregunta, label_x: i < 60 ? 160 : 850, y: 400 + (i % 60) * 30 })) },
                            }} />}
                        </section>

                        <div className="space-y-5">
                            <section className={cardClass}>
                                <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Diseño de cartillas de respuestas</h2>
                                <Form action={route('omr.plantillas.store', selectedId)} method="post" className="mt-3 space-y-3">
                                    {({ errors, processing }) => <>
                                        <label className="block text-sm font-medium">Nombre de plantilla<input name="nombre" className={inputClass} placeholder="Ej. Examen de becas · 60 preguntas" required maxLength={100} /></label>
                                        <label className="block text-sm font-medium">Título en la cartilla<input name="titulo" className={inputClass} defaultValue={simulacroSeleccionado.titulo} maxLength={100} /></label>
                                        <label className="block text-sm font-medium">Instrucciones<textarea name="instrucciones" className={inputClass} maxLength={180} defaultValue="Rellena el DNI, el grupo y una alternativa por pregunta con tinta negra. Evita tachaduras." /></label>
                                        <input name="opciones_por_pregunta" type="hidden" value={optionCount} />
                                        <label className="block text-sm font-medium">Formato de cartilla<select name="diseno" className={inputClass} defaultValue={claves.length <= 80 ? 'compacto' : 'clasico'}><option value="compacto">Como la referencia · DNI y 4 bloques · hasta 80 preguntas</option><option value="clasico">Clásico · hasta 120 preguntas</option></select></label>
                                        <label className="block text-sm font-medium">Columnas del diseño clásico<select name="columnas" className={inputClass} defaultValue="2"><option value="1">Una columna · hasta 60 preguntas</option><option value="2">Dos columnas · hasta 120 preguntas</option></select></label>
                                        <p className="text-sm text-slate-500">La cartilla utiliza la clave guardada ({claves.length} preguntas), con {optionCount === 4 ? 'alternativas A–D' : 'alternativas A–E'}. Guarda primero los cambios de preguntas.</p>
                                        {Object.entries(errors).map(([field, message]) => <p key={field} className="text-sm text-red-600" role="alert">{message}</p>)}
                                        <PrimaryButton disabled={!claves.length || processing}>{processing ? 'Creando…' : 'Crear cartilla'}</PrimaryButton>
                                    </>}
                                </Form>
                                {plantillas.map(plantilla => <CartillaPreview key={plantilla.id_plantilla} plantilla={plantilla} />)}
                                {plantillas.length === 0 && <p className="mt-3 text-sm text-amber-700 dark:text-amber-300">Guarda una clave de respuestas para crear la plantilla.</p>}
                            </section>
                            <section className={cardClass}>
                                <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Escáner de cartillas</h2>
                                <OmrUpload simulacro={selectedId!} plantillas={plantillas} />
                            </section>
                        </div>
                    </div>

                    <section className="space-y-3">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Hojas del simulacro</h2>
                            <div className="flex gap-2">
                                <select aria-label="Filtrar hojas por estado" className={inputClass} value={filters.estado ?? ''} onChange={(event) => router.get(route('omr.index'), { id_simulacro: selectedId, estado: event.target.value || undefined }, { preserveState: false })}>
                                    <option value="">Todos los estados</option><option value="pendiente">Pendiente</option><option value="procesada">Procesada</option><option value="revision">Revisión</option><option value="error">Error</option>
                                </select>
                                <SecondaryButton type="button" onClick={() => router.reload({ only: ['hojas', 'stats', 'omr'] })}><RefreshCw className="mr-2 size-4" />Actualizar</SecondaryButton>
                            </div>
                        </div>
                        {hojas.data.length === 0 ? <EmptyState title="Aún no hay hojas" description="Abre el escáner en vivo o carga imágenes para identificar y calificar sus respuestas." /> : <>
                            {hojas.data.map((hoja) => ['pendiente', 'revision', 'error'].includes(hoja.estado) ? <ReviewSheet key={hoja.id_hoja} hoja={hoja} matriculas={matriculas} /> : <article key={hoja.id_hoja} className={`${cardClass} flex flex-wrap items-center justify-between gap-3`}>
                                <div><p className="font-semibold text-slate-900 dark:text-white">{hoja.codigo_hoja}</p><p className="text-sm text-slate-500">{hoja.postulante ? `DNI ${hoja.postulante.dni} · Grupo ${hoja.postulante.grupo} · Nota ${hoja.postulante.nota ?? 'Pendiente'}` : hoja.matricula ? `${hoja.matricula.estudiante.nombre} ${hoja.matricula.estudiante.apellido} · DNI ${hoja.matricula.estudiante.dni} · Nota ${hoja.nota_omr ?? 'Pendiente'}` : 'Sin identificar'} · {hoja.confianza ? `Confianza ${hoja.confianza}%` : 'Lectura guardada'}</p><p className="mt-1 text-sm text-slate-600 dark:text-slate-300">Correctas: {hoja.resumen_lectura.correctas} · Incorrectas: {hoja.resumen_lectura.incorrectas} · En blanco: {hoja.resumen_lectura.en_blanco}</p></div>
                                <div className="flex items-center gap-3"><span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700 dark:bg-slate-800 dark:text-slate-200">{hoja.estado}</span>{hoja.archivo_path && <a className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-300" href={route('omr.hojas.archivo', hoja.id_hoja)}>Ver archivo</a>}</div>
                            </article>)}
                            <div className="flex justify-center gap-2">{hojas.links.prev && <a href={hojas.links.prev} className="rounded-lg border px-3 py-2 text-sm">Anterior</a>}<span className="px-3 py-2 text-sm text-slate-500">Página {hojas.meta.current_page} de {hojas.meta.last_page}</span>{hojas.links.next && <a href={hojas.links.next} className="rounded-lg border px-3 py-2 text-sm">Siguiente</a>}</div>
                        </>}
                    </section>
                </>}
            </div>
        </PageShell>
    );
}
