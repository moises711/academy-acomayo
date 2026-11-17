import OmrCamera from '@/Components/OmrCamera';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { readOmrFile, type OmrLayout, type OmrReading } from '@/lib/omr-reader';
import PrimaryButton from '@/Components/PrimaryButton';

export default function OmrUpload({ simulacro, plantillas }: { simulacro: number; plantillas: { id_plantilla: number; nombre: string; config: unknown }[] }) {
    const form = useForm<{ id_simulacro: number; id_plantilla: string; archivos: File[]; lecturas: (OmrReading | null)[] }>({ id_simulacro: simulacro, id_plantilla: '', archivos: [], lecturas: [] });
    const [analyzing, setAnalyzing] = useState(false);
    const [messages, setMessages] = useState<string[]>([]);
    const input = 'w-full rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white';
    async function analyze(files: File[]) {
        if (!files.length) return;
        if (files.length > 20 || files.some(file => file.size > 12 * 1024 * 1024)) { setMessages(['Elige hasta 20 imágenes, de 12 MB como máximo cada una.']); return; }
        setAnalyzing(true); setMessages([]);
        const template = plantillas.find(p => String(p.id_plantilla) === form.data.id_plantilla);
        const readings: (OmrReading | null)[] = [], notices: string[] = [];
        for (const file of files) {
            try {
                const result = await readOmrFile(file, template?.config as OmrLayout);
                readings.push(result);
                const doubts = result.respuestas.filter(a => a.estado === 'dudosa' || a.estado === 'multiple').length;
                notices.push(`${file.name}: DNI ${result.dni ?? 'sin reconocer'}, grupo ${result.grupo ?? 'sin reconocer'}, ${doubts} respuestas para revisar.`);
            } catch (error) {
                readings.push(null); notices.push(`${file.name}: ${error instanceof Error ? error.message : 'No se pudo leer la imagen.'} Se enviará para revisión manual.`);
            }
        }
        form.setData(data => ({ ...data, archivos: files, lecturas: readings })); setMessages(notices); setAnalyzing(false);
    }
    return <form className="mt-3 grid gap-3" onSubmit={e => { e.preventDefault(); form.post(route('omr.hojas.subir'), { forceFormData: true, onSuccess: () => { form.setData(data => ({ ...data, archivos: [], lecturas: [] })); setMessages([]); } }); }}>
        <label className="text-sm font-medium">Plantilla de la hoja<select required className={input} value={form.data.id_plantilla} onChange={e => { form.setData(data => ({ ...data, id_plantilla: e.target.value, archivos: [], lecturas: [] })); setMessages([]); }}><option value="">Selecciona la cartilla impresa</option>{plantillas.map(p => <option key={p.id_plantilla} value={p.id_plantilla}>{p.nombre}</option>)}</select></label>
        <p className="text-sm text-slate-600 dark:text-slate-300">Rellena completamente las burbujas del DNI, el grupo y las respuestas con tinta negra. Coloca la hoja horizontal, completa y sin sombras dentro del escáner, incluyendo los cuatro cuadrados negros.</p>
        <OmrCamera layout={plantillas.find(p => String(p.id_plantilla) === form.data.id_plantilla)?.config as OmrLayout | undefined} disabled={!form.data.id_plantilla || analyzing || form.processing} onRead={(file, result) => { form.setData(data => ({ ...data, archivos: [file], lecturas: [result] })); setMessages([result ? `DNI ${result.dni ?? 'sin reconocer'}, grupo ${result.grupo ?? 'sin reconocer'}, ${result.respuestas.length} respuestas leídas. Guarda la lectura para calificar o revisar.` : 'No se reconocieron las marcas. Guarda la hoja para revisión manual.']); }} />
        <details><summary className="cursor-pointer text-sm font-medium">Carga alternativa de imágenes escaneadas</summary>
            <label className="mt-2 block text-sm font-medium">Imágenes existentes<input disabled={!form.data.id_plantilla || analyzing || form.processing} className={input} type="file" accept="image/jpeg,image/png" multiple onChange={e => void analyze(Array.from(e.target.files ?? []))} /></label>
        </details>
        {analyzing && <p role="status">Leyendo DNI y respuestas…</p>}
        {messages.map((message, i) => <p key={i} className="break-words text-sm" role="status">{message}</p>)}
        {Object.entries(form.errors).map(([field, message]) => <p key={field} className="text-sm text-red-600 dark:text-red-400" role="alert">{message}</p>)}
        <PrimaryButton disabled={!form.data.archivos.length || analyzing || form.processing}>{form.processing ? 'Guardando y calificando…' : 'Guardar lectura y calcular nota'}</PrimaryButton>
        <p className="text-xs text-slate-500">El servidor compara las respuestas con la clave guardada. Si el DNI o el grupo no coinciden con un registro, o existen marcas ambiguas, la hoja queda para revisión.</p>
    </form>;
}
