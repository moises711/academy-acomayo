import { useEffect, useRef, useState } from 'react';
import { readOmr, type OmrLayout, type OmrReading } from '@/lib/omr-reader';
import SecondaryButton from '@/Components/SecondaryButton';

export default function OmrCamera({ layout, disabled, onRead }: { layout?: OmrLayout; disabled: boolean; onRead: (file: File, result: OmrReading | null) => void }) {
    const video = useRef<HTMLVideoElement>(null);
    const camera = useRef<MediaStream | null>(null);
    const active = useRef(false);
    const [open, setOpen] = useState(false);
    const [stream, setStream] = useState<MediaStream | null>(null);
    const [message, setMessage] = useState('');
    function stop() { active.current = false; camera.current?.getTracks().forEach(track => track.stop()); camera.current = null; setStream(null); setOpen(false); }
    useEffect(() => () => { active.current = false; camera.current?.getTracks().forEach(track => track.stop()); }, []);
    async function start() {
        if (layout?.diseno !== 'compacto' || !layout.grupo) { setMessage('Selecciona una cartilla compacta nueva con DNI y grupo para usar el escáner.'); return; }
        if (!navigator.mediaDevices?.getUserMedia) { setMessage('La cámara en vivo requiere acceso HTTPS desde el teléfono, o localhost en este equipo.'); return; }
        setOpen(true); active.current = true; setMessage('Abriendo cámara…');
        try {
            const media = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1600 }, height: { ideal: 1200 } }, audio: false });
            if (!active.current) { media.getTracks().forEach(track => track.stop()); return; }
            camera.current = media; setStream(media);
        } catch { stop(); setMessage('No se pudo abrir la cámara. Revisa el permiso de cámara del navegador y vuelve a intentarlo.'); }
    }
    function captureForReview() {
        const view = video.current;
        if (!view?.videoWidth || !layout) return;
        const canvas = document.createElement('canvas');
        const scale = Math.min(1, 1600 / Math.max(view.videoWidth, view.videoHeight));
        canvas.width = Math.round(view.videoWidth * scale); canvas.height = Math.round(view.videoHeight * scale);
        const context = canvas.getContext('2d', { willReadFrequently: true }); if (!context) return;
        context.drawImage(view, 0, 0, canvas.width, canvas.height);
        let result: OmrReading | null = null;
        try { result = readOmr(context.getImageData(0, 0, canvas.width, canvas.height), layout); } catch { /* Se conserva la hoja para revisión sin adivinar datos. */ }
        canvas.toBlob(blob => {
            if (!blob || !active.current) return;
            onRead(new File([blob], `revision-${Date.now()}.png`, { type: 'image/png' }), result);
            stop(); setMessage('Lectura capturada para revisión. Guarda la hoja y confirma las marcas dudosas.');
        }, 'image/png');
    }
    useEffect(() => {
        if (!stream || !video.current || !layout) return;
        const view = video.current; view.srcObject = stream; void view.play().catch(() => setMessage('Pulsa el video para iniciar la cámara.'));
        let last = '', stable = 0, busy = false;
        const timer = window.setInterval(() => {
            if (busy || !active.current || !view.videoWidth) return;
            const scale = Math.min(1, 1600 / Math.max(view.videoWidth, view.videoHeight));
            const canvas = document.createElement('canvas'); canvas.width = Math.round(view.videoWidth * scale); canvas.height = Math.round(view.videoHeight * scale);
            const context = canvas.getContext('2d', { willReadFrequently: true }); if (!context) return;
            context.drawImage(view, 0, 0, canvas.width, canvas.height);
            try {
                const result = readOmr(context.getImageData(0, 0, canvas.width, canvas.height), layout);
                if (!result.dni || !result.grupo || result.respuestas.some(a => a.estado === 'dudosa' || a.estado === 'multiple')) {
                    stable = 0; setMessage('Hoja detectada. Acerca la cámara y revisa las burbujas del DNI, grupo y respuestas.'); return;
                }
                const signature = JSON.stringify([result.dni, result.grupo, result.respuestas]); stable = signature === last ? stable + 1 : 1; last = signature;
                setMessage(`DNI ${result.dni} · Grupo ${result.grupo}. Mantén la hoja quieta…`);
                if (stable < 2) return;
                busy = true;
                canvas.toBlob(blob => {
                    if (!blob || !active.current) { busy = false; return; }
                    onRead(new File([blob], `escaneo-${Date.now()}.png`, { type: 'image/png' }), result);
                    stop(); setMessage('Hoja escaneada. Guarda la lectura para identificar al usuario y calcular su nota.');
                }, 'image/png');
            } catch (error) { stable = 0; setMessage(error instanceof Error ? error.message : 'Centra la hoja completa dentro de la cámara.'); }
        }, 1000);
        return () => window.clearInterval(timer);
    }, [stream, layout]);
    return <div className="grid gap-3">
        <SecondaryButton type="button" disabled={disabled || open} onClick={() => void start()}>Abrir escáner en vivo</SecondaryButton>
        {open && <div className="rounded-lg border border-slate-300 p-3 dark:border-slate-700">
            <video ref={video} autoPlay muted playsInline controls aria-label="Vista del escáner de cartillas" className="max-h-96 w-full rounded-lg bg-black" />
            <p className="my-2 text-sm">Coloca la hoja horizontal y completa dentro del visor. El escáner la lee automáticamente cuando distingue las cuatro esquinas y las burbujas.</p>
            <div className="flex flex-wrap gap-2"><SecondaryButton type="button" disabled={!stream} onClick={captureForReview}>Enviar lectura dudosa a revisión</SecondaryButton><SecondaryButton type="button" onClick={stop}>Cerrar cámara</SecondaryButton></div>
        </div>}
        {message && <p role="status" className="text-sm">{message}</p>}
    </div>;
}
