import { LucideIcon } from 'lucide-react';

export default function StatCard({
    title,
    value,
    hint,
    icon: Icon,
    tone = 'blue',
}: {
    title: string;
    value: string | number;
    hint?: string;
    icon: LucideIcon;
    tone?: 'blue' | 'cyan' | 'violet' | 'amber' | 'emerald';
}) {
    const tones = {
        blue: {
            surface: 'border-blue-200/80 from-white via-blue-50 to-indigo-100/90 hover:border-blue-300 dark:border-blue-300/15 dark:from-[#0b1730] dark:via-[#111d3b] dark:to-[#17285a] dark:hover:border-blue-400/40',
            glow: 'bg-blue-400/35 group-hover:bg-blue-500/45 dark:bg-blue-500/20 dark:group-hover:bg-blue-500/35',
            icon: 'from-cyan-400 via-blue-600 to-indigo-700 dark:from-cyan-400 dark:via-blue-500 dark:to-indigo-600',
            stripe: 'before:from-cyan-400 before:via-blue-600 before:to-violet-500',
        },
        cyan: {
            surface: 'border-cyan-200/80 from-white via-cyan-50 to-teal-100/90 hover:border-cyan-300 dark:border-cyan-300/15 dark:from-[#0a1c2a] dark:via-[#0b2938] dark:to-[#104355] dark:hover:border-cyan-400/40',
            glow: 'bg-cyan-400/35 group-hover:bg-cyan-500/45 dark:bg-cyan-500/20 dark:group-hover:bg-cyan-500/35',
            icon: 'from-teal-400 via-cyan-600 to-blue-700 dark:from-teal-300 dark:via-cyan-500 dark:to-blue-600',
            stripe: 'before:from-teal-300 before:via-cyan-500 before:to-blue-600',
        },
        violet: {
            surface: 'border-violet-200/80 from-white via-violet-50 to-fuchsia-100/90 hover:border-violet-300 dark:border-violet-300/15 dark:from-[#1a1230] dark:via-[#281544] dark:to-[#431c5b] dark:hover:border-violet-400/40',
            glow: 'bg-violet-400/35 group-hover:bg-violet-500/45 dark:bg-violet-500/20 dark:group-hover:bg-violet-500/35',
            icon: 'from-violet-400 via-purple-600 to-fuchsia-700 dark:from-violet-300 dark:via-purple-500 dark:to-fuchsia-600',
            stripe: 'before:from-violet-300 before:via-purple-500 before:to-fuchsia-600',
        },
        amber: {
            surface: 'border-amber-200/80 from-white via-amber-50 to-orange-100/90 hover:border-amber-300 dark:border-amber-300/15 dark:from-[#26190c] dark:via-[#39200d] dark:to-[#54270e] dark:hover:border-amber-400/40',
            glow: 'bg-amber-400/35 group-hover:bg-orange-500/45 dark:bg-amber-500/20 dark:group-hover:bg-orange-500/35',
            icon: 'from-amber-400 via-orange-500 to-rose-600 dark:from-amber-300 dark:via-orange-500 dark:to-rose-500',
            stripe: 'before:from-yellow-300 before:via-amber-500 before:to-rose-500',
        },
        emerald: {
            surface: 'border-emerald-200/80 from-white via-emerald-50 to-teal-100/90 hover:border-emerald-300 dark:border-emerald-300/15 dark:from-[#0b211b] dark:via-[#103329] dark:to-[#145146] dark:hover:border-emerald-400/40',
            glow: 'bg-emerald-400/35 group-hover:bg-teal-500/45 dark:bg-emerald-500/20 dark:group-hover:bg-teal-500/35',
            icon: 'from-emerald-400 via-teal-500 to-cyan-700 dark:from-emerald-300 dark:via-teal-500 dark:to-cyan-600',
            stripe: 'before:from-lime-300 before:via-emerald-500 before:to-cyan-500',
        },
    }[tone];

    return (
        <article className={`group relative isolate overflow-hidden rounded-[2rem] border bg-gradient-to-br p-5 shadow-[0_16px_40px_rgba(37,99,235,0.12)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_22px_48px_rgba(37,99,235,0.2)] before:absolute before:bottom-7 before:left-0 before:top-7 before:w-1 before:rounded-r-full before:bg-gradient-to-b before:content-[''] motion-reduce:transform-none motion-reduce:transition-none dark:shadow-[0_16px_42px_rgba(0,0,0,0.34)] dark:hover:shadow-[0_22px_52px_rgba(37,99,235,0.2)] sm:p-6 ${tones.surface} ${tones.stripe}`}>
            <div aria-hidden="true" className={`absolute -right-8 -top-12 -z-10 size-44 rounded-full blur-3xl transition-colors duration-300 ${tones.glow}`} />
            <div aria-hidden="true" className="absolute -bottom-16 left-1/3 -z-10 size-36 rounded-full bg-violet-300/35 blur-3xl dark:bg-cyan-500/10" />
            <div aria-hidden="true" className="absolute inset-x-7 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/70 to-transparent dark:via-cyan-300/50" />
            <div className="flex min-h-16 items-start justify-between gap-4">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{title}</p>
                    <p className="mt-3 break-words text-2xl font-bold leading-none tracking-tight text-slate-900 dark:text-white sm:text-[1.75rem]">{value}</p>
                    {hint && <p className="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{hint}</p>}
                </div>
                <div className={`grid size-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br text-white shadow-lg shadow-blue-700/25 ring-1 ring-inset ring-white/35 transition duration-300 group-hover:scale-110 group-hover:rotate-3 ${tones.icon}`}>
                    <Icon aria-hidden="true" className="size-5" />
                </div>
            </div>
        </article>
    );
}
