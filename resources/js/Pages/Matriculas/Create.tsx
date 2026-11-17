import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, UserRound, BookOpen, Wallet, ClipboardCheck } from 'lucide-react';
import { FormEvent, useMemo, useRef, useState } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageShell from '@/Components/PageShell';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { formatMoney, fullName } from '@/lib/format';
import { AreaAcademica, Beca, Carrera, Ciclo, Estudiante, Grupo } from '@/types/academia';

type Props = {
    estudiantes: Estudiante[];
    ciclos: Ciclo[];
    grupos: (Grupo & { ciclo: Ciclo })[];
    becas: Pick<Beca, 'id_beca' | 'nombre' | 'porcentaje_descuento'>[];
    carreras: (Carrera & { universidad: { nombre: string }; area: AreaAcademica })[];
};

type FormData = {
    id_estudiante: string;
    id_ciclo: string;
    id_grupo: string;
    id_carrera_objetivo: string;
    id_beca: string;
    fecha_matricula: string;
    plan: { monto_total: string; numero_cuotas: string; fecha_primera_cuota: string };
};

const steps = [
    { title: 'Estudiante', icon: UserRound },
    { title: 'Ciclo y grupo', icon: BookOpen },
    { title: 'Beca y cuotas', icon: Wallet },
    { title: 'Confirmación', icon: ClipboardCheck },
];

export default function MatriculasCreate({ estudiantes, ciclos, grupos, becas, carreras }: Props) {
    const [step, setStep] = useState(0);
    const [stepError, setStepError] = useState('');
    const formRef = useRef<HTMLFormElement>(null);
    const form = useForm<FormData>({
        id_estudiante: '', id_ciclo: '', id_grupo: '', id_carrera_objetivo: '', id_beca: '',
        fecha_matricula: new Date().toLocaleDateString('en-CA'),
        plan: { monto_total: '', numero_cuotas: '1', fecha_primera_cuota: new Date().toLocaleDateString('en-CA') },
    });
    const selectedStudent = estudiantes.find((student) => String(student.id_estudiante) === form.data.id_estudiante);
    const selectedCycle = ciclos.find((cycle) => String(cycle.id_ciclo) === form.data.id_ciclo);
    const selectedGroup = grupos.find((group) => String(group.id_grupo) === form.data.id_grupo);
    const selectedScholarship = becas.find((scholarship) => String(scholarship.id_beca) === form.data.id_beca);
    const selectedCareer = carreras.find((career) => String(career.id_carrera) === form.data.id_carrera_objetivo);
    const cycleGroups = useMemo(() => grupos.filter((group) => String(group.id_ciclo) === form.data.id_ciclo), [grupos, form.data.id_ciclo]);
    const discount = Number(selectedScholarship?.porcentaje_descuento ?? 0);
    const total = Number(form.data.plan.monto_total || 0);
    const finalTotal = Math.max(0, total * (1 - discount / 100));
    const installments = Math.max(1, Number(form.data.plan.numero_cuotas || 1));

    const updatePlan = (field: keyof FormData['plan'], value: string) => {
        form.setData('plan', { ...form.data.plan, [field]: value });
    };

    const continueStep = () => {
        const fieldset = formRef.current?.querySelector(`fieldset[data-step="${step}"]`);
        const invalidField = fieldset?.querySelector<HTMLElement>(':invalid');
        if (invalidField) {
            invalidField.focus();
            setStepError('Completa los campos obligatorios para continuar.');
            return;
        }
        setStepError('');
        setStep((current) => Math.min(current + 1, steps.length - 1));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(route('matriculas.store'), { preserveScroll: true });
    };

    return (
        <PageShell title="Nueva matrícula" description="Registra al estudiante y prepara su plan de cuotas paso a paso.">
            <div className="mx-auto max-w-4xl">
                <ol className="mb-6 grid grid-cols-2 gap-2 sm:grid-cols-4" aria-label="Pasos de matrícula">
                    {steps.map(({ title, icon: Icon }, index) => <li key={title} aria-current={step === index ? 'step' : undefined} className={`flex items-center gap-2 rounded-xl border px-3 py-3 text-sm ${step === index ? 'border-blue-200 bg-blue-50 font-semibold text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200' : index < step ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200' : 'border-slate-200 bg-white text-slate-500 dark:border-slate-800 dark:bg-slate-900'}`}><span className="grid size-7 shrink-0 place-items-center rounded-lg bg-white/80 dark:bg-slate-900"><Icon className="size-4" /></span><span>{index + 1}. {title}</span>{index < step && <Check className="ml-auto size-4" />}</li>)}
                </ol>

                <form ref={formRef} onSubmit={submit} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-7">
                    <fieldset data-step="0" hidden={step !== 0} className="grid gap-5 sm:grid-cols-2">
                        <legend className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Selecciona al estudiante</legend>
                        <div className="sm:col-span-2"><InputLabel htmlFor="id_estudiante" value="Estudiante activo" /><select id="id_estudiante" required value={form.data.id_estudiante} onChange={(event) => form.setData('id_estudiante', event.target.value)} className="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><option value="">Selecciona un estudiante</option>{estudiantes.map((student) => <option key={student.id_estudiante} value={student.id_estudiante}>{fullName(student)} · DNI {student.dni}</option>)}</select><InputError message={form.errors.id_estudiante} /></div>
                        <div><InputLabel htmlFor="fecha_matricula" value="Fecha de matrícula" /><input id="fecha_matricula" name="fecha_matricula" type="date" required value={form.data.fecha_matricula} onChange={(event) => form.setData('fecha_matricula', event.target.value)} className="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950" /><InputError message={form.errors.fecha_matricula} /></div>
                    </fieldset>

                    <fieldset data-step="1" hidden={step !== 1} className="grid gap-5 sm:grid-cols-2">
                        <legend className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Asigna ciclo y grupo</legend>
                        <div><InputLabel htmlFor="id_ciclo" value="Ciclo" /><select id="id_ciclo" required value={form.data.id_ciclo} onChange={(event) => form.setData((current) => ({ ...current, id_ciclo: event.target.value, id_grupo: '' }))} className="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><option value="">Selecciona un ciclo</option>{ciclos.map((cycle) => <option key={cycle.id_ciclo} value={cycle.id_ciclo}>{cycle.nombre_ciclo} · {cycle.modalidad}</option>)}</select><InputError message={form.errors.id_ciclo} /></div>
                        <div><InputLabel htmlFor="id_grupo" value="Grupo" /><select id="id_grupo" value={form.data.id_grupo} onChange={(event) => form.setData('id_grupo', event.target.value)} className="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><option value="">Sin asignar</option>{cycleGroups.map((group) => <option key={group.id_grupo} value={group.id_grupo}>{group.nombre} · Turno {group.turno}</option>)}</select><InputError message={form.errors.id_grupo} /></div>
                        <p className="text-sm text-slate-500 sm:col-span-2">Solo se muestran grupos del ciclo elegido.</p>
                    </fieldset>

                    <fieldset data-step="2" hidden={step !== 2} className="grid gap-5 sm:grid-cols-2">
                        <legend className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Define el plan de cuotas</legend>
                        <div><InputLabel htmlFor="id_beca" value="Beca o descuento" /><select id="id_beca" value={form.data.id_beca} onChange={(event) => form.setData('id_beca', event.target.value)} className="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><option value="">Sin beca</option>{becas.map((scholarship) => <option key={scholarship.id_beca} value={scholarship.id_beca}>{scholarship.nombre} · {scholarship.porcentaje_descuento}%</option>)}</select><InputError message={form.errors.id_beca} /></div>
                        <div><InputLabel htmlFor="id_carrera_objetivo" value="Carrera objetivo" /><select id="id_carrera_objetivo" value={form.data.id_carrera_objetivo} onChange={(event) => form.setData('id_carrera_objetivo', event.target.value)} className="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><option value="">Sin asignar</option>{carreras.map((career) => <option key={career.id_carrera} value={career.id_carrera}>{career.nombre} · {career.universidad.nombre}</option>)}</select><InputError message={form.errors.id_carrera_objetivo} /></div>
                        <div><InputLabel htmlFor="monto_total" value="Costo total antes de beca (S/)" /><input id="monto_total" type="number" min="0.01" max="1000000" step="0.01" required value={form.data.plan.monto_total} onChange={(event) => updatePlan('monto_total', event.target.value)} className="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950" /><InputError message={form.errors['plan.monto_total']} /></div>
                        <div><InputLabel htmlFor="numero_cuotas" value="Número de cuotas mensuales" /><input id="numero_cuotas" type="number" min="1" max="24" required value={form.data.plan.numero_cuotas} onChange={(event) => updatePlan('numero_cuotas', event.target.value)} className="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950" /><InputError message={form.errors['plan.numero_cuotas']} /></div>
                        <div><InputLabel htmlFor="fecha_primera_cuota" value="Vencimiento de la primera cuota" /><input id="fecha_primera_cuota" type="date" required value={form.data.plan.fecha_primera_cuota} onChange={(event) => updatePlan('fecha_primera_cuota', event.target.value)} className="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950" /><InputError message={form.errors['plan.fecha_primera_cuota']} /></div>
                        <aside className="rounded-xl bg-blue-50 p-4 text-sm text-blue-900 dark:bg-blue-950 dark:text-blue-100 sm:col-span-2"><p className="font-semibold">Total con descuento: {formatMoney(finalTotal)}</p><p className="mt-1">{discount}% de beca · {installments} {installments === 1 ? 'cuota mensual' : 'cuotas mensuales'} · aproximadamente {formatMoney(finalTotal / installments)} por cuota.</p><p className="mt-1 text-xs opacity-80">El ajuste de centavos se distribuye entre las cuotas. El costo lo define secretaría según el programa.</p></aside>
                    </fieldset>

                    <fieldset data-step="3" hidden={step !== 3} className="space-y-5">
                        <legend className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Revisa y confirma</legend>
                        <dl className="grid gap-3 sm:grid-cols-2">{[
                            ['Estudiante', selectedStudent ? `${fullName(selectedStudent)} · DNI ${selectedStudent.dni}` : 'Sin seleccionar'],
                            ['Fecha', form.data.fecha_matricula || 'Sin definir'],
                            ['Ciclo', selectedCycle?.nombre_ciclo ?? 'Sin seleccionar'],
                            ['Grupo', selectedGroup?.nombre ?? 'Sin asignar'],
                            ['Carrera objetivo', selectedCareer ? `${selectedCareer.nombre} · ${selectedCareer.universidad.nombre}` : 'Sin asignar'],
                            ['Beca', selectedScholarship ? `${selectedScholarship.nombre} · ${discount}%` : 'Sin beca'],
                            ['Costo final', formatMoney(finalTotal)],
                            ['Plan', `${installments} cuotas a partir del ${form.data.plan.fecha_primera_cuota || 'sin fecha'}`],
                        ].map(([label, value]) => <div key={label} className="rounded-xl bg-slate-50 p-4 dark:bg-slate-950"><dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt><dd className="mt-1 font-semibold text-slate-900 dark:text-white">{value}</dd></div>)}</dl>
                        <p className="text-sm text-slate-500">Al confirmar, se crea la matrícula y sus cuotas quedan listas para caja.</p>
                    </fieldset>

                    <InputError message={stepError} className="mt-4" />
                    <div className="mt-7 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                        <div className="flex gap-2">{step > 0 ? <SecondaryButton type="button" onClick={() => { setStepError(''); setStep((current) => current - 1); }}><ArrowLeft className="mr-2 size-4" />Anterior</SecondaryButton> : <Link href={route('matriculas.index')}><SecondaryButton type="button">Cancelar</SecondaryButton></Link>}</div>
                        {step < steps.length - 1 ? <PrimaryButton type="button" onClick={continueStep}>Continuar<ArrowRight className="ml-2 size-4" /></PrimaryButton> : <PrimaryButton type="submit" disabled={form.processing}>{form.processing ? 'Registrando matrícula…' : 'Confirmar matrícula'}</PrimaryButton>}
                    </div>
                </form>
            </div>
        </PageShell>
    );
}
