import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import ThemeToggle from '@/Components/ThemeToggle';
import { GraduationCap, Sparkles } from 'lucide-react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="animate-view-enter relative isolate flex min-h-screen flex-col overflow-hidden bg-slate-50 px-4 py-6 text-slate-900 transition-colors duration-300 dark:bg-slate-950 dark:text-white sm:justify-center sm:px-6 sm:py-10">
            <div aria-hidden="true" className="auth-orb auth-orb-one" />
            <div aria-hidden="true" className="auth-orb auth-orb-two" />
            <div aria-hidden="true" className="auth-grid absolute inset-0 -z-10 opacity-40 dark:opacity-20" />
            <header className="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between">
                <Link href="/" className="group inline-flex items-center gap-3 rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-4 dark:focus-visible:ring-offset-slate-950">
                    <span className="grid size-12 place-items-center rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-600 text-white shadow-lg shadow-blue-900/20 transition-transform duration-200 group-hover:-rotate-3 group-hover:scale-105">
                        <GraduationCap className="size-6" aria-hidden="true" />
                    </span>
                    <span>
                        <span className="block text-base font-bold tracking-tight">Academia</span>
                        <span className="block text-xs font-medium text-slate-500 dark:text-slate-400">Formación preuniversitaria</span>
                    </span>
                </Link>
                <ThemeToggle className="border border-slate-200/80 bg-white/70 shadow-sm backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/70" />
            </header>

            <main className="relative z-10 mx-auto flex w-full max-w-6xl flex-1 items-center justify-center py-8 sm:py-12">
                <div className="grid w-full items-center gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(24rem,0.85fr)] lg:gap-16">
                    <section className="hidden max-w-xl lg:block">
                        <span className="inline-flex items-center gap-2 rounded-full border border-blue-200/70 bg-white/65 px-3 py-1.5 text-xs font-semibold text-blue-800 shadow-sm backdrop-blur dark:border-blue-900/70 dark:bg-slate-900/60 dark:text-blue-200"><Sparkles className="size-3.5" />Tu próximo paso empieza aquí</span>
                        <h1 className="mt-6 text-5xl font-bold leading-[1.08] tracking-tight text-slate-950 dark:text-white xl:text-6xl">Prepárate hoy para <span className="bg-gradient-to-r from-blue-700 via-indigo-600 to-cyan-600 bg-clip-text text-transparent dark:from-blue-300 dark:via-indigo-300 dark:to-cyan-200">llegar más lejos.</span></h1>
                        <p className="mt-6 max-w-lg text-lg leading-8 text-slate-600 dark:text-slate-300">Ingresa a tu espacio académico para continuar con tus clases, evaluaciones y avances.</p>
                        <div className="mt-10 flex items-center gap-3 text-sm font-medium text-slate-500 dark:text-slate-400"><span className="h-px w-10 bg-gradient-to-r from-blue-500 to-amber-400" />Una comunidad enfocada en tus metas</div>
                    </section>
                    <div className="mx-auto w-full max-w-md overflow-hidden rounded-[2rem] border border-white/80 bg-white/85 p-6 shadow-[0_28px_80px_rgba(23,59,102,0.16)] backdrop-blur-xl dark:border-slate-700/80 dark:bg-slate-900/80 dark:shadow-[0_28px_80px_rgba(0,0,0,0.4)] sm:p-9">
                        {children}
                    </div>
                </div>
            </main>
            <footer className="relative z-10 mx-auto w-full max-w-6xl text-center text-xs text-slate-500 dark:text-slate-500">© {new Date().getFullYear()} Academia · Plataforma académica</footer>
            </div>
    );
}
