import { router, useForm } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { CalendarDays, Camera, CameraOff, CheckCircle2, ScanLine, Users } from 'lucide-react';
import { FormEvent, useEffect, useMemo, useRef, useState } from 'react';
import InputError from '@/Components/InputError';
import PageShell from '@/Components/PageShell';
import { Horario } from '@/types/academia';

type Enrollment = {
    id_matricula: number;
    fecha_matricula: string;
    fecha_retiro: string | null;
    estudiante: { nombre: string; apellido: string; dni: string };
    grupo: { id_grupo: number; nombre: string };
};

type DailyEntry = { id_matricula: number; fecha: string };

type AttendanceMark = {
    id_asistencia: number;
    id_matricula: number;
    fecha: string;
    estado: 'Presente' | 'Tarde' | 'Falta' | 'Justificada';
    hora_registro: string | null;
    matricula: { estudiante: { nombre: string; apellido: string } };
    horario: { curso: { nombre_curso: string } };
    usuario: { name: string } | null;
};

type Props = {
    horarios: Horario[];
    padron: Enrollment[];
    ingresosPorDia: DailyEntry[];
    asistencias: AttendanceMark[];
    desde: string;
    hasta: string;
    canScan: boolean;
    canCorrect: boolean;
    stats: { ingresos: number; asistencias: number };
};

type DetectedBarcode = { rawValue: string };
type BarcodeDetectorInstance = { detect: (source: HTMLVideoElement) => Promise<DetectedBarcode[]> };
type BarcodeDetectorConstructor = new (options: { formats: string[] }) => BarcodeDetectorInstance;

const formatDate = (value: string) => new Date(value.slice(0, 10) + 'T12:00:00').toLocaleDateString('es-PE', { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' });

const dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

export default function AsistenciasIndex({ horarios, padron, ingresosPorDia, asistencias, desde, hasta, canScan, canCorrect, stats }: Props) {
    const [loading, setLoading] = useState(false);
    const [queryError, setQueryError] = useState('');
    const [cameraError, setCameraError] = useState('');
    const [cameraOpen, setCameraOpen] = useState(false);
    const formRef = useRef<HTMLFormElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const frameRef = useRef<number | null>(null);
    const detectorRef = useRef<BarcodeDetectorInstance | null>(null);
    const { data, setData, post, processing, errors, reset } = useForm({ codigo_qr: '' });

    const stopCamera = () => {
        if (frameRef.current !== null) window.cancelAnimationFrame(frameRef.current);
        frameRef.current = null;
        streamRef.current?.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
        setCameraOpen(false);
    };

    useEffect(() => {
        inputRef.current?.focus();
        return () => {
            if (frameRef.current !== null) window.cancelAnimationFrame(frameRef.current);
            streamRef.current?.getTracks().forEach((track) => track.stop());
        };
    }, []);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(route('asistencias.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onFinish: () => inputRef.current?.focus(),
        });
    };

    const startCamera = async () => {
        setCameraError('');
        const Detector = (window as Window & { BarcodeDetector?: BarcodeDetectorConstructor }).BarcodeDetector;

        if (!Detector) {
            setCameraError('Este navegador no admite lectura QR por cámara. Puedes usar un lector USB en el campo de captura.');
            inputRef.current?.focus();
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
            streamRef.current = stream;
            detectorRef.current = new Detector({ formats: ['qr_code'] });
            setCameraOpen(true);

            window.setTimeout(async () => {
                if (videoRef.current) {
                    videoRef.current.srcObject = stream;
                    await videoRef.current.play();
                }

                let detecting = false;
                const scanFrame = async () => {
                    if (!videoRef.current || !detectorRef.current || !streamRef.current) return;
                    if (!detecting && videoRef.current.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
                        detecting = true;
                        try {
                            const code = (await detectorRef.current.detect(videoRef.current))[0]?.rawValue;
                            if (code) {
                                stopCamera();
                                setData('codigo_qr', code.trim());
                                navigator.vibrate?.(100);
                                window.setTimeout(() => formRef.current?.requestSubmit(), 100);
                                return;
                            }
                        } catch {
                            setCameraError('No se pudo leer el QR. Acerca y enfoca el código dentro del recuadro.');
                        } finally {
                            detecting = false;
                        }
                    }
                    frameRef.current = window.requestAnimationFrame(scanFrame);
                };

                frameRef.current = window.requestAnimationFrame(scanFrame);
            }, 0);
        } catch {
            setCameraError('No se pudo abrir la cámara. Revisa el permiso del navegador e inténtalo de nuevo.');
            stopCamera();
        }
    };

    return (
        <PageShell title="Control de asistencia" description="Escanea el QR del carnet para registrar el ingreso y marcar las clases de hoy.">
            <div className="grid gap-4 sm:grid-cols-2">
                <Stat icon={Users} label="Ingresos registrados" value={stats.ingresos} />
                <Stat icon={CalendarDays} label="Marcas de clase" value={stats.asistencias} />
            </div>

            <div className="grid min-w-0 gap-6">
                {canScan && (
                <section className="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div className="mb-5 flex items-center gap-3">
                        <div className="grid size-10 place-items-center rounded-xl bg-indigo-50 text-indigo-700"><ScanLine className="size-5" /></div>
                        <div><h2 className="font-semibold text-slate-900 dark:text-white">Escanear carnet</h2><p className="text-sm text-slate-500">Usa la cámara o un lector QR conectado.</p></div>
                    </div>

                    <form ref={formRef} onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <label htmlFor="codigo_qr" className="text-sm font-medium text-slate-700 dark:text-slate-200">Código QR</label>
                            <div className="flex gap-2">
                                <input ref={inputRef} id="codigo_qr" autoComplete="off" autoFocus value={data.codigo_qr} onChange={(event) => setData('codigo_qr', event.target.value)} className="min-w-0 flex-1 rounded-xl border-slate-200 font-mono text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950" placeholder="El lector envía el código al presionar Enter" />
                                <button type="button" onClick={cameraOpen ? stopCamera : startCamera} className="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200" aria-label={cameraOpen ? 'Cerrar cámara' : 'Abrir cámara'}>
                                    {cameraOpen ? <CameraOff className="size-4" /> : <Camera className="size-4" />}
                                    <span className="hidden sm:inline">{cameraOpen ? 'Cerrar' : 'Cámara'}</span>
                                </button>
                            </div>
                            <InputError message={errors.codigo_qr} />
                        </div>

                        <div className={`${cameraOpen ? 'relative' : 'hidden'} overflow-hidden rounded-2xl bg-slate-950`}>
                            <video ref={videoRef} playsInline muted className="aspect-video w-full object-cover" />
                            <div className="pointer-events-none absolute inset-0 grid place-items-center"><div className="size-44 rounded-2xl border-2 border-white/90 shadow-[0_0_0_999px_rgba(15,23,42,0.25)]" /></div>
                            <span className="absolute inset-x-0 bottom-3 text-center text-xs font-medium text-white">Centra el QR dentro del marco</span>
                        </div>
                        {cameraError && <p role="alert" className="rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">{cameraError}</p>}

                        <button type="submit" disabled={processing || !data.codigo_qr} className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <CheckCircle2 className="size-4" />{processing ? 'Validando…' : 'Registrar ingreso'}
                        </button>
                        {horarios.length === 0 && <p className="text-sm text-slate-500">Hoy no hay clases programadas; el ingreso quedará registrado igualmente.</p>}
                    </form>
                </section>
                )}

                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="flex flex-col gap-4 border-b border-slate-100 p-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                        <div><h2 className="font-semibold text-slate-900 dark:text-white">Registro de asistencia por bloque</h2><p className="text-sm text-slate-500">Una fila por alumno y una columna por fecha</p></div>
                        <Form action={route('asistencias.index')} method="get" onStart={() => { setLoading(true); setQueryError(''); }} onFinish={() => setLoading(false)} onError={() => setQueryError('No se pudo consultar el período. Revisa las fechas e inténtalo nuevamente.')} className="flex flex-wrap items-end gap-2">
                            <label className="grid gap-1 text-xs font-medium text-slate-600 dark:text-slate-300">Desde<input type="date" name="desde" defaultValue={desde} required className="rounded-lg border-slate-200 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950" /></label>
                            <label className="grid gap-1 text-xs font-medium text-slate-600 dark:text-slate-300">Hasta<input type="date" name="hasta" defaultValue={hasta} required className="rounded-lg border-slate-200 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-950" /></label>
                            <button className="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">Consultar</button>
                            <a href={route('asistencias.exportar', { desde, hasta })} className="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 dark:border-slate-700 dark:text-blue-300 dark:hover:bg-slate-800">Descargar CSV</a>
                        </Form>
                    </div>
                    {queryError && <div role="alert" className="flex flex-wrap items-center gap-3 p-4 text-sm text-red-700 dark:text-red-300">{queryError}<button type="button" className="underline focus-visible:ring-2" onClick={() => router.reload({ onStart: () => setLoading(true), onFinish: () => setLoading(false), onSuccess: () => setQueryError('') })}>Reintentar</button></div>}
                    <AttendanceSheet padron={padron} asistencias={asistencias} ingresos={ingresosPorDia} desde={desde} hasta={hasta} canCorrect={canCorrect} loading={loading} />
                </section>
            </div>

            {horarios.length > 0 && <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 className="mb-4 font-semibold text-slate-900 dark:text-white">Clases del {desde} · {dayNames[(new Date(`${desde}T12:00:00`).getDay() + 6) % 7]}</h2>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{horarios.map((horario) => <article key={horario.id_horario} className="rounded-xl border border-slate-100 p-4 dark:border-slate-800"><p className="font-medium text-slate-900 dark:text-white">{horario.curso?.nombre_curso}</p><p className="mt-1 text-sm text-slate-500">{horario.grupo?.nombre} · {horario.grupo?.ciclo?.nombre_ciclo}</p><p className="mt-2 text-xs text-slate-500">{horario.hora_inicio}–{horario.hora_fin} · {horario.aula?.nombre}</p></article>)}</div>
            </section>}
        </PageShell>
    );
}

function AttendanceSheet({ padron, asistencias, ingresos, desde, hasta, canCorrect, loading }: {
    padron: Enrollment[]; asistencias: AttendanceMark[]; ingresos: DailyEntry[];
    desde: string; hasta: string; canCorrect: boolean; loading: boolean;
}) {
    const [group, setGroup] = useState('');
    const [search, setSearch] = useState('');
    const [descending, setDescending] = useState(false);
    const [page, setPage] = useState(1);
    const [selected, setSelected] = useState<{ id: number; date: string } | null>(null);
    const groups = useMemo(() => Array.from(new Map(padron.map((row) => [String(row.grupo.id_grupo), row.grupo.nombre])).entries()).sort((a, b) => a[1].localeCompare(b[1], 'es')), [padron]);
    const activeGroup = groups.some(([id]) => id === group) ? group : groups[0]?.[0] ?? '';
    const dates = useMemo(() => {
        const result: string[] = [];
        const current = new Date(`${desde}T12:00:00Z`);
        const end = new Date(`${hasta}T12:00:00Z`);
        while (current <= end) {
            result.push(current.toISOString().slice(0, 10));
            current.setUTCDate(current.getUTCDate() + 1);
        }
        return result;
    }, [desde, hasta]);
    const marks = useMemo(() => {
        const result = new Map<string, AttendanceMark[]>();
        asistencias.forEach((mark) => {
            const key = `${mark.id_matricula}|${mark.fecha.slice(0, 10)}`;
            result.set(key, [...(result.get(key) ?? []), mark]);
        });
        return result;
    }, [asistencias]);
    const entries = useMemo(() => new Set(ingresos.map((entry) => `${entry.id_matricula}|${entry.fecha}`)), [ingresos]);
    const rows = padron.filter((row) => String(row.grupo.id_grupo) === activeGroup
        && `${row.estudiante.apellido} ${row.estudiante.nombre} ${row.estudiante.dni}`.toLocaleLowerCase('es').includes(search.toLocaleLowerCase('es')))
        .sort((a, b) => (descending ? -1 : 1) * `${a.estudiante.apellido} ${a.estudiante.nombre}`.localeCompare(`${b.estudiante.apellido} ${b.estudiante.nombre}`, 'es'));
    const pages = Math.max(1, Math.ceil(rows.length / 25));
    const currentPage = Math.min(page, pages);
    const visibleRows = rows.slice((currentPage - 1) * 25, currentPage * 25);
    const selectedStudent = selected ? rows.find((row) => row.id_matricula === selected.id) : undefined;
    const selectedMarks = selected ? marks.get(`${selected.id}|${selected.date}`) ?? [] : [];

    return <div aria-busy={loading}>
        <div className="flex flex-wrap items-end gap-4 border-b border-slate-200 p-4 dark:border-slate-800">
            <label className="grid gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">Bloque / grupo
                <select value={activeGroup} onChange={(event) => { setGroup(event.target.value); setPage(1); setSelected(null); }} className="rounded-lg border-slate-200 text-base dark:border-slate-700 dark:bg-slate-950">
                    {groups.length === 0 && <option value="">Sin grupos</option>}
                    {groups.map(([id, name]) => <option key={id} value={id}>{name}</option>)}
                </select>
            </label>
            <label className="grid min-w-0 flex-1 gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">Buscar alumno
                <input type="search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); setSelected(null); }} placeholder="Apellidos, nombres o DNI" className="w-full rounded-lg border-slate-200 text-base dark:border-slate-700 dark:bg-slate-950" />
            </label>
        </div>
        <p className="border-b border-slate-200 p-4 text-xs text-slate-600 dark:border-slate-800 dark:text-slate-300">✓ Asistió · ✓ T Llegó tarde · ✕ Falta · J Justificada · — Sin registro · * Hay diferencias entre clases. Selecciona una celda para ver el detalle.</p>
        {loading ? <div role="status" className="grid gap-3 p-4"><span className="sr-only">Cargando asistencias…</span>{[1, 2, 3, 4, 5].map((row) => <div key={row} className="h-8 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />)}</div>
            : visibleRows.length === 0 ? <div className="grid gap-3 p-8 text-center"><p className="text-sm text-slate-600 dark:text-slate-300">No hay alumnos para este bloque y período.</p><button type="button" onClick={() => { setSearch(''); setGroup(''); }} className="text-sm font-medium text-blue-700 underline dark:text-blue-300">Limpiar filtros</button></div>
                : <div className="attendance-sheet max-h-96 max-w-full overflow-auto focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600" tabIndex={0} role="region" aria-label="Hoja de asistencia por alumno y fecha">
                    <table className="w-full text-left text-sm">
                        <caption className="sr-only">Asistencia de {groups.find(([id]) => id === activeGroup)?.[1]}, del {formatDate(desde)} al {formatDate(hasta)}</caption>
                        <thead className="text-xs text-slate-600 dark:text-slate-300"><tr>
                            <th scope="col" aria-sort={descending ? 'descending' : 'ascending'}><button type="button" onClick={() => setDescending(!descending)} className="inline-flex w-full items-center gap-2 py-2 font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600">Nombres y apellidos <span aria-hidden="true">{descending ? '↓' : '↑'}</span></button></th>
                            {dates.map((date) => <th key={date} scope="col" className="text-center"><span className="block capitalize">{new Date(`${date}T12:00:00`).toLocaleDateString('es-PE', { weekday: 'short' })}</span><span className="block font-semibold">{date.slice(8, 10)}/{date.slice(5, 7)}/{date.slice(0, 4)}</span></th>)}
                        </tr></thead>
                        <tbody>{visibleRows.map((row) => <tr key={row.id_matricula}>
                            <th scope="row" className="text-left"><span className="block text-left font-medium text-slate-900 dark:text-white">{row.estudiante.apellido}, {row.estudiante.nombre}</span><span className="block text-left text-xs text-slate-500">DNI {row.estudiante.dni}</span></th>
                            {dates.map((date) => {
                                const key = `${row.id_matricula}|${date}`;
                                const dayMarks = marks.get(key) ?? [];
                                const came = entries.has(key) || dayMarks.some((mark) => mark.estado === 'Presente' || mark.estado === 'Tarde');
                                const late = dayMarks.some((mark) => mark.estado === 'Tarde');
                                const absent = dayMarks.some((mark) => mark.estado === 'Falta');
                                const justified = dayMarks.some((mark) => mark.estado === 'Justificada');
                                const mixed = new Set(dayMarks.map((mark) => mark.estado)).size > 1;
                                const outside = date < row.fecha_matricula.slice(0, 10) || (row.fecha_retiro !== null && date > row.fecha_retiro.slice(0, 10));
                                const label = outside ? 'Fuera de matrícula' : came ? (late ? 'Llegó tarde' : 'Asistió') : absent ? 'Falta' : justified ? 'Justificada' : 'Sin registro';
                                const symbol = outside ? '—' : came ? (late ? '✓ T' : '✓') : absent ? '✕' : justified ? 'J' : '—';
                                return <td key={date} className="text-center"><button type="button" disabled={outside} aria-label={`${row.estudiante.nombre} ${row.estudiante.apellido}, ${formatDate(date)}: ${label}${mixed ? ', hay diferencias entre clases' : ''}`} aria-pressed={selected?.id === row.id_matricula && selected.date === date} title={`${label}${mixed ? '. Revisar detalle por clase' : ''}`} onClick={() => setSelected({ id: row.id_matricula, date })} className={`min-h-10 w-full min-w-12 rounded px-3 py-2 text-base font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600 aria-pressed:bg-blue-50 dark:aria-pressed:bg-slate-800 ${outside ? 'text-slate-400' : came ? 'text-green-700 dark:text-green-300' : absent ? 'text-red-700 dark:text-red-300' : justified ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500'}`}>{symbol}{mixed && !outside ? '*' : ''}</button></td>;
                            })}
                        </tr>)}</tbody>
                    </table>
                </div>}
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 p-4 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-300">
            <span>{rows.length} alumnos · Página {currentPage} de {pages}</span>
            <div className="flex gap-3"><button type="button" disabled={currentPage === 1} onClick={() => setPage(currentPage - 1)} className="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-50 dark:border-slate-700">Anterior</button><button type="button" disabled={currentPage === pages} onClick={() => setPage(currentPage + 1)} className="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-50 dark:border-slate-700">Siguiente</button></div>
        </div>
        {selectedStudent && selected && <div className="grid gap-4 border-t border-slate-200 p-4 dark:border-slate-800" aria-live="polite">
            <div className="flex flex-wrap items-center justify-between gap-3"><h3 className="font-semibold text-slate-900 dark:text-white">{selectedStudent.estudiante.apellido}, {selectedStudent.estudiante.nombre} · {formatDate(selected.date)}</h3><button type="button" onClick={() => setSelected(null)} className="text-sm text-blue-700 underline dark:text-blue-300">Cerrar detalle</button></div>
            {selectedMarks.length === 0 ? <p className="text-sm text-slate-500">{entries.has(`${selected.id}|${selected.date}`) ? 'Ingreso registrado, sin marcas de clase.' : 'Sin registro de ingreso ni marcas de clase. No se considera falta hasta que se registre.'}</p>
                : selectedMarks.map((mark) => <div key={mark.id_asistencia} className="grid gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700"><p className="text-sm text-slate-700 dark:text-slate-200">{mark.horario.curso.nombre_curso} · {mark.estado} · {mark.usuario?.name ?? 'Sistema'}</p>{canCorrect && <div className="overflow-x-auto"><CorrectionForm key={`${mark.id_asistencia}-${mark.estado}`} attendance={mark} /></div>}</div>)}
        </div>}
    </div>;
}

function CorrectionForm({ attendance }: { attendance: AttendanceMark }) {
    const { data, setData, patch, processing, errors } = useForm({ estado: attendance.estado, motivo: '' });
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        patch(route('asistencias.update', attendance.id_asistencia), { preserveScroll: true, onSuccess: () => setData('motivo', '') });
    };

    return <form onSubmit={submit} className="flex min-w-[440px] items-start gap-2">
        <select aria-label="Nuevo estado de asistencia" value={data.estado} onChange={(event) => setData('estado', event.target.value as AttendanceMark['estado'])} className="rounded-lg border-slate-200 text-xs dark:border-slate-700 dark:bg-slate-950">
            {['Presente', 'Tarde', 'Falta', 'Justificada'].map((estado) => <option key={estado} value={estado}>{estado}</option>)}
        </select>
        <div><input aria-label="Motivo de corrección" value={data.motivo} onChange={(event) => setData('motivo', event.target.value)} placeholder="Motivo obligatorio" className="w-40 rounded-lg border-slate-200 text-xs dark:border-slate-700 dark:bg-slate-950" /><InputError message={errors.motivo} /></div>
        <button disabled={processing || !data.motivo.trim()} className="rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium text-white disabled:opacity-50">{processing ? 'Guardando…' : 'Corregir'}</button>
    </form>;
}

function Stat({ icon: Icon, label, value }: { icon: typeof Users; label: string; value: number }) {
    return <div data-info-card className="flex items-center gap-4 rounded-2xl border border-blue-200/80 bg-white p-5 shadow-sm dark:border-blue-300/15 dark:bg-slate-900"><div className="grid size-12 place-items-center rounded-2xl bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200"><Icon className="size-6" /></div><div><p className="text-sm text-slate-500">{label}</p><p className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{value}</p></div></div>;
}
