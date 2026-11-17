import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { ArrowRight, LockKeyhole, UserRound } from 'lucide-react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        identifier: '',
        password: '',
        remember: false as boolean,
    });
    const isStudentLogin = /^\d{8}$/.test(data.identifier);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Iniciar sesión" />

            <div className="mb-7">
                <p className="text-xs font-bold uppercase tracking-[0.16em] text-blue-700 dark:text-blue-300">Acceso seguro</p>
                <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">¡Qué bueno verte!</h2>
                <p className="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">Personal: correo y contraseña. Estudiantes: DNI y PIN de 6 dígitos.</p>
            </div>

            {status && (
                <div className="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="grid gap-5">
                <div className="relative grid gap-2">
                    <InputLabel htmlFor="identifier" value="Correo electrónico o DNI" className="text-sm font-semibold text-slate-700 dark:text-slate-200" />

                    <TextInput
                        id="identifier"
                        type="text"
                        name="identifier"
                        value={data.identifier}
                        className="block min-h-12 w-full rounded-xl border-slate-200 bg-white/80 px-4 pl-11 text-sm shadow-sm transition focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/70"
                        autoComplete="username"
                        isFocused={true}
                        inputMode={isStudentLogin ? 'numeric' : 'text'}
                        maxLength={120}
                        onChange={(e) => setData('identifier', e.target.value.trimStart())}
                    />
                    <UserRound className="pointer-events-none absolute left-4 top-[2.8rem] size-4 text-slate-400" aria-hidden="true" />

                    <InputError message={errors.identifier} className="mt-2" />
                </div>

                <div className="relative grid gap-2">
                    <InputLabel htmlFor="password" value="Contraseña o PIN" className="text-sm font-semibold text-slate-700 dark:text-slate-200" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="block min-h-12 w-full rounded-xl border-slate-200 bg-white/80 px-4 pl-11 text-sm shadow-sm transition focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/70"
                        autoComplete="current-password"
                        inputMode={isStudentLogin ? 'numeric' : undefined}
                        maxLength={isStudentLogin ? 6 : undefined}
                        pattern={isStudentLogin ? '[0-9]{6}' : undefined}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <LockKeyhole className="pointer-events-none absolute left-4 top-[2.8rem] size-4 text-slate-400" aria-hidden="true" />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <label className="inline-flex cursor-pointer items-center gap-2.5">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData(
                                    'remember',
                                    (e.target.checked || false) as false,
                                )
                            }
                        />
                        <span className="text-sm text-slate-600 dark:text-slate-300">
                            Mantener sesión iniciada
                        </span>
                    </label>
                </div>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="rounded-lg text-sm font-semibold text-blue-700 transition hover:text-blue-900 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300 dark:hover:text-blue-200"
                        >
                            ¿Olvidaste tu contraseña o PIN?
                        </Link>
                    )}

                <PrimaryButton className="group mt-1 min-h-12 w-full justify-center rounded-xl text-sm" disabled={processing}>
                        {processing ? 'Ingresando…' : 'Ingresar'}
                        {!processing && <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />}
                    </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
