import { Head, Link, useForm } from '@inertiajs/react';
import { CalendarDays, Clock3 } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import ThemeToggle from '@/Components/ThemeToggle';
import { landingContent } from '@/data/landing';
import type { ConvocatoriaPublica } from '@/types/becas';

const input = 'mt-1 min-h-11 w-full rounded-xl border-slate-300 bg-white text-slate-900 focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-950 dark:text-white';
type ScholarshipExam = { nombre: string; fecha_examen: string; hora_inicio_examen: string; hora_fin_examen: string };

export default function Inscripcion({ convocatorias, examenBecas }: { convocatorias: ConvocatoriaPublica[]; examenBecas: ScholarshipExam | null }) {
    const text = landingContent.becas;
    const form = useForm({ id_convocatoria: convocatorias[0]?.id_convocatoria.toString() ?? '', dni: '', telefono: '', grupo: '' });
    const selected = convocatorias.find(c => c.id_convocatoria.toString() === form.data.id_convocatoria);
    const [examScheduleVisible, setExamScheduleVisible] = useState(Boolean(examenBecas));
    useEffect(() => {
        if (!examenBecas) {
            setExamScheduleVisible(false);
            return;
        }

        const closesAt = new Date(`${examenBecas.fecha_examen.slice(0, 10)}T${examenBecas.hora_fin_examen}:00-05:00`).getTime();
        setExamScheduleVisible(closesAt > Date.now());
        let timer: number;
        const hideAfterEnd = () => {
            const remaining = closesAt - Date.now();
            if (remaining <= 0) setExamScheduleVisible(false);
            else timer = window.setTimeout(hideAfterEnd, Math.min(remaining, 2_147_483_647));
        };
        hideAfterEnd();

        return () => window.clearTimeout(timer);
    }, [examenBecas]);
    return <main className="min-h-screen bg-slate-50 px-4 py-8 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <Head title={text.title} />
        <div className="mx-auto max-w-xl space-y-6">
            <div className="flex items-center justify-between"><Link href="/" className="font-semibold text-blue-700 dark:text-blue-300">{text.back}</Link><ThemeToggle /></div>
            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                <h1 className="text-2xl font-bold">{text.title}</h1><p className="mt-2 text-sm text-slate-500">{text.description}</p>
                {examScheduleVisible && examenBecas && <section className="mt-5 rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 via-white to-amber-50 p-4 dark:border-blue-900 dark:from-blue-950/50 dark:via-slate-900 dark:to-amber-950/20"><h2 className="font-semibold">{examenBecas.nombre}</h2><div className="mt-3 grid gap-2 text-sm sm:grid-cols-2"><p className="flex items-center gap-2"><CalendarDays className="size-4 text-blue-600 dark:text-blue-300" />{formatExamDate(examenBecas.fecha_examen)}</p><p className="flex items-center gap-2"><Clock3 className="size-4 text-blue-600 dark:text-blue-300" />{examenBecas.hora_inicio_examen} – {examenBecas.hora_fin_examen}</p></div></section>}
                {!convocatorias.length ? <div className="mt-6 space-y-4"><p>{text.empty}</p><Link href="/" className="font-semibold text-blue-700 dark:text-blue-300">{text.back}</Link></div> : <form className="mt-6 space-y-4" onSubmit={event => { event.preventDefault(); form.post(route('becas.inscripcion.store'), { onSuccess: () => form.reset('dni', 'telefono', 'grupo') }); }}>
                    <label className="block text-sm font-medium">Convocatoria<select className={input} value={form.data.id_convocatoria} onChange={e => form.setData('id_convocatoria', e.target.value)}>{convocatorias.map(c => <option key={c.id_convocatoria} value={c.id_convocatoria}>{c.nombre}</option>)}</select><InputError message={form.errors.id_convocatoria} /></label>
                    {selected?.descripcion && <p className="text-sm text-slate-500">{selected.descripcion}</p>}
                    <label className="block text-sm font-medium">{text.dni}<input className={input} inputMode="numeric" autoComplete="off" maxLength={8} pattern="[0-9]{8}" required value={form.data.dni} onChange={e => form.setData('dni', e.target.value.replace(/\D/g, ''))} /><InputError message={form.errors.dni} /></label>
                    <label className="block text-sm font-medium">{text.phone}<input className={input} type="tel" autoComplete="tel" inputMode="numeric" maxLength={9} pattern="9[0-9]{8}" required value={form.data.telefono} onChange={e => form.setData('telefono', e.target.value.replace(/\D/g, ''))} /><InputError message={form.errors.telefono} /></label>
                    <label className="block text-sm font-medium">{text.group}<select className={input} required value={form.data.grupo} onChange={e => form.setData('grupo', e.target.value)}><option value="">{text.chooseGroup}</option>{['A', 'B', 'C', 'D'].map(group => <option key={group} value={group}>Grupo {group}</option>)}</select><InputError message={form.errors.grupo} /></label>
                    <p className="text-sm text-slate-500">{text.pairs}</p><p className="text-xs text-slate-500">{text.retention}</p>
                    {form.recentlySuccessful && <p role="status" className="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{text.success}</p>}
                    {form.hasErrors && <p role="alert" className="text-sm text-red-600">{text.error}</p>}
                    <PrimaryButton disabled={form.processing} className="w-full">{form.processing ? text.sending : text.submit}</PrimaryButton>
                </form>}
            </section>
        </div>
    </main>;
}

function formatExamDate(date: string): string {
    return new Intl.DateTimeFormat('es-PE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'America/Lima' }).format(new Date(`${date.slice(0, 10)}T12:00:00-05:00`));
}
