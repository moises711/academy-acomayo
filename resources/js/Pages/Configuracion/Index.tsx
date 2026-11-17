import { useForm } from '@inertiajs/react';
import { Clock3, Save, Wallet } from 'lucide-react';
import InputError from '@/Components/InputError';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';

type Props = { configuracion: { tolerancia_minutos: number; bloquear_si_deuda: boolean } };

export default function ConfiguracionIndex({ configuracion }: Props) {
    const form = useForm(configuracion);
    return (
        <PageShell title="Configuración" description="Define cómo se registra el ingreso y la tardanza en la academia.">
            <form onSubmit={(event) => { event.preventDefault(); form.put(route('configuracion.update'), { preserveScroll: true }); }} className="max-w-3xl space-y-5">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div className="mb-5 flex items-center gap-3"><div className="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300"><Clock3 className="size-5" /></div><div><h2 className="font-semibold text-slate-900 dark:text-white">Tolerancia de tardanza</h2><p className="text-sm text-slate-500">Tiempo permitido desde el inicio de clase antes de marcar tardanza.</p></div></div>
                    <label htmlFor="tolerancia_minutos" className="text-sm font-medium text-slate-700 dark:text-slate-200">Minutos de tolerancia</label>
                    <div className="mt-1 flex items-center gap-3"><input id="tolerancia_minutos" type="number" min="0" max="60" value={form.data.tolerancia_minutos} onChange={(event) => form.setData('tolerancia_minutos', Number(event.target.value))} className="min-h-11 w-32 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-950" /><span className="text-sm text-slate-500">minutos (0 a 60)</span></div><InputError message={form.errors.tolerancia_minutos} className="mt-1" />
                </section>
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div className="mb-5 flex items-center gap-3"><div className="grid size-10 place-items-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300"><Wallet className="size-5" /></div><div><h2 className="font-semibold text-slate-900 dark:text-white">Deuda vencida</h2><p className="text-sm text-slate-500">El sistema puede avisar o bloquear el registro de ingreso al estudiante.</p></div></div>
                    <label className="flex cursor-pointer items-start gap-3"><input type="checkbox" checked={form.data.bloquear_si_deuda} onChange={(event) => form.setData('bloquear_si_deuda', event.target.checked)} className="mt-1 size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600" /><span><span className="block text-sm font-medium text-slate-800 dark:text-slate-100">Bloquear el ingreso si tiene cuotas vencidas</span><span className="mt-1 block text-sm text-slate-500">Si está desactivado, secretaría verá una alerta y registrará el ingreso normalmente.</span></span></label><InputError message={form.errors.bloquear_si_deuda} className="mt-1" />
                </section>
                <div className="flex justify-end"><PrimaryButton disabled={form.processing}>{form.processing ? 'Guardando…' : <><Save className="mr-2 size-4" />Guardar configuración</>}</PrimaryButton></div>
            </form>
        </PageShell>
    );
}
