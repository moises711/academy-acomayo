import { Head, Link } from '@inertiajs/react';
import Form from '@/Components/InertiaForm';
import { ArrowDown, ArrowRight, Award, BookOpen, CalendarDays, ChartNoAxesCombined, Check, ChevronDown, Clock3, GraduationCap, Target, Users, LogIn, Menu, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import ThemeToggle from '@/Components/ThemeToggle';
import { Ciclo } from '@/types/academia';
import { landingContent } from '@/data/landing';

const featureIcons = { book: BookOpen, chart: ChartNoAxesCombined, people: Users, target: Target };

type DocenteWeb = { id_profesor: number; nombre: string; bio: string; cursos: string[]; fotoUrl: string | null };
type ScholarshipExam = { nombre: string; fecha_examen: string; hora_inicio_examen: string; hora_fin_examen: string };
type Props = { ciclos: Ciclo[]; contenido: Record<string, any>; docentesWeb: DocenteWeb[]; fotoPortadaUrl: string | null; examenBecas: ScholarshipExam | null };

function mergeContent(base: Record<string, any>, override: Record<string, any>): Record<string, any> {
    const result = { ...base };
    Object.entries(override).forEach(([key, value]) => {
        result[key] = value && typeof value === 'object' && !Array.isArray(value)
            ? mergeContent(base[key] ?? {}, value)
            : value;
    });
    return result;
}

export default function Welcome({ ciclos, contenido, docentesWeb, fotoPortadaUrl, examenBecas }: Props) {
    const [visibleExam, setVisibleExam] = useState<ScholarshipExam | null>(examenBecas);
    const content = mergeContent({
        ...landingContent,
        hero: { ...landingContent.hero, caption: 'Una imagen de estudiantes podrá reemplazar esta ilustración.' },
        admisiones: { fecha_apertura: '', fecha_cierre: '', mensaje: '' },
    }, contenido) as typeof landingContent & {
        hero: { caption: string };
        admisiones: { fecha_apertura: string; fecha_cierre: string; mensaje: string };
        contact: { publicPhone?: string; publicEmail?: string; phoneLabel?: string; emailLabel?: string };
    };
    useEffect(() => {
        if (!examenBecas) {
            setVisibleExam(null);
            return;
        }

        const closesAt = new Date(`${examenBecas.fecha_examen.slice(0, 10)}T${examenBecas.hora_fin_examen}:00-05:00`).getTime();
        let timer: number;
        const hideAfterEnd = () => {
            const remaining = closesAt - Date.now();
            if (remaining <= 0) setVisibleExam(null);
            else timer = window.setTimeout(hideAfterEnd, Math.min(remaining, 2_147_483_647));
        };
        hideAfterEnd();

        return () => window.clearTimeout(timer);
    }, [examenBecas]);
    useEffect(() => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.querySelectorAll('[data-reveal]').forEach((element) => element.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        document.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));
        return () => observer.disconnect();
    }, []);

    const [menuOpen, setMenuOpen] = useState(false);
    const publicIcons = [BookOpen, Target, Award, Users];

    const programs = ciclos.length > 0 ? ciclos : [];

    return (
        <>
            <Head title={content.brand}>
                <meta name="description" content={content.hero.description} />
                <meta name="theme-color" content="#173B66" />
            </Head>
            <div className="min-h-screen bg-white pb-20 text-slate-900 transition-colors duration-200 dark:bg-slate-950 lg:pb-0">
                <header className="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#102b4b]/90 text-white shadow-lg shadow-slate-950/10 backdrop-blur-xl">
                    <nav aria-label="Navegación principal" className="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                        <a href="#inicio" className="flex min-w-0 items-center gap-3" aria-label={`${content.brand}, inicio`}>
                            <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-400 text-[#102b4b] shadow-sm"><GraduationCap className="size-6" /></span>
                            <span className="max-w-32 truncate text-sm font-bold leading-tight sm:max-w-none sm:text-base">{content.brand}</span>
                        </a>
                        <div className="hidden items-center gap-7 lg:flex">
                            {content.navigation.links.map((link) => <a key={link.href} href={link.href} className="text-sm font-medium text-blue-100 transition hover:text-white">{link.label}</a>)}
                        </div>
                        <div className="flex shrink-0 items-center gap-2 sm:gap-3">
                            <ThemeToggle className="text-white hover:bg-white/10 dark:text-blue-100 dark:hover:bg-white/10" />
                            <Link href="/login" className="hidden rounded-xl px-3 py-2 text-sm font-semibold text-white transition hover:bg-white/10 sm:inline-flex">{content.navigation.login}</Link>
                            <a href="#contacto" className="hidden sm:inline-flex rounded-xl bg-amber-400 px-3 py-2.5 text-xs font-bold text-[#102b4b] transition hover:bg-amber-300 sm:px-4 sm:text-sm">{content.navigation.enroll}</a>
                        </div>
                        <button type="button" className="rounded-xl p-3 focus-visible:outline focus-visible:outline-2 lg:hidden" aria-label={menuOpen ? 'Cerrar navegación' : 'Abrir navegación'} aria-expanded={menuOpen} aria-controls="public-mobile-menu" onClick={() => setMenuOpen(!menuOpen)}>{menuOpen ? <X className="size-5" /> : <Menu className="size-5" />}</button>
                    </nav>
                    {menuOpen && <nav id="public-mobile-menu" aria-label="Secciones del sitio" className="max-h-[calc(100dvh-9rem)] overflow-y-auto border-t border-white/15 p-4 lg:hidden">
                        {content.navigation.links.map((link) => <a key={link.href} href={link.href} onClick={() => setMenuOpen(false)} className="block rounded-xl px-4 py-3 text-sm font-semibold hover:bg-white/10 focus-visible:outline focus-visible:outline-2">{link.label}</a>)}
                        <Link href="/login" className="block rounded-xl px-4 py-3 text-sm font-semibold">{content.navigation.login}</Link>
                    </nav>}
                </header>
                <nav aria-label="Navegación inferior del sitio" className="mobile-navigation fixed inset-x-0 bottom-0 z-40 flex border-t border-[hsl(var(--border))] bg-[hsl(var(--surface))] text-[hsl(var(--foreground))] lg:hidden">
                    {content.navigation.links.slice(0, 4).map((link, index) => { const Icon = publicIcons[index]; return <a key={link.href} href={link.href} onClick={() => setMenuOpen(false)} className="mobile-navigation-item"><Icon aria-hidden="true" className="size-5" /><span className="w-full truncate text-center text-xs font-semibold">{link.label}</span></a>; })}
                    <Link href="/login" className="mobile-navigation-item"><LogIn aria-hidden="true" className="size-5" /><span className="text-xs font-semibold">{content.navigation.login}</span></Link>
                </nav>

                <main className="landing-page animate-view-enter pt-[4.5rem] [&_p]:font-serif [&_blockquote]:font-serif">
                    {visibleExam && <aside className="relative z-20 border-y border-blue-400/20 bg-gradient-to-r from-[#07152f] via-[#102b4b] to-[#07152f] px-4 py-3 text-blue-50 shadow-lg shadow-blue-950/20"><div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 sm:flex-row"><div className="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-center sm:justify-start sm:text-left"><span className="inline-flex items-center gap-2 rounded-full border border-amber-300/30 bg-amber-300/10 px-3 py-1 text-xs font-bold text-amber-200"><CalendarDays className="size-4" />Examen de becas</span><span className="text-sm font-semibold">{formatExamDate(visibleExam.fecha_examen)}</span><span className="inline-flex items-center gap-1.5 text-sm text-blue-100"><Clock3 className="size-4 text-sky-300" />{visibleExam.hora_inicio_examen} – {visibleExam.hora_fin_examen}</span></div><Link href={route('becas.inscripcion')} className="inline-flex min-h-10 items-center gap-2 rounded-xl bg-amber-400 px-4 text-sm font-bold text-[#102b4b] transition hover:bg-amber-300">{landingContent.becas.link}<ArrowRight aria-hidden="true" className="size-4" /></Link></div></aside>}
                    <section id="inicio" className="relative isolate scroll-mt-20 overflow-hidden bg-[#102b4b] text-white">
                        <div aria-hidden="true" className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_78%_30%,rgba(37,99,235,.34),transparent_32%),radial-gradient(ellipse_at_8%_92%,rgba(14,165,233,.14),transparent_34%),linear-gradient(120deg,#050b1b_0%,#0a1b38_52%,#102b4b_100%)]" />
                        <div aria-hidden="true" className="absolute -right-28 top-16 -z-10 size-96 rounded-full border border-blue-400/10 shadow-[0_0_100px_rgba(37,99,235,.15)]" />
                        <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:py-24">
                            <div data-reveal className="reveal space-y-7">
                                <span className="inline-flex items-center gap-2 rounded-full border border-blue-200/20 bg-white/5 px-4 py-2 text-xs font-semibold tracking-wide text-blue-100 shadow-sm"><span className="size-2 rounded-full bg-amber-400" />{content.hero.eyebrow}</span>
                                <h1 className="max-w-3xl bg-gradient-to-br from-white via-white to-blue-200 bg-clip-text font-serif text-4xl font-medium leading-[1.08] tracking-tight text-transparent sm:text-5xl lg:text-6xl">{content.hero.title}</h1>
                                <p className="max-w-xl text-base leading-7 text-blue-100 sm:text-lg">{content.hero.description}</p>
                                {(content.admisiones.fecha_apertura || content.admisiones.fecha_cierre || content.admisiones.mensaje) && <p className="max-w-xl rounded-xl border border-white/15 bg-white/10 p-3 text-sm text-blue-50">{admissionWindow(content.admisiones.fecha_apertura, content.admisiones.fecha_cierre)}{content.admisiones.mensaje}</p>}
                                <div className="flex flex-col gap-3 sm:flex-row">
                                    <a href="#programas" className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 font-bold text-white shadow-lg shadow-blue-950/40 ring-1 ring-blue-400/30 transition hover:-translate-y-0.5 hover:bg-blue-500">{content.hero.primary}<ArrowRight className="size-4" /></a>
                                    <a href="#contacto" className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-blue-200/25 bg-white/[.03] px-5 font-semibold text-white transition hover:border-blue-200/50 hover:bg-white/10">{content.hero.secondary}<ArrowDown className="size-4" /></a>
                                </div>
                                {visibleExam && <div className="max-w-xl rounded-2xl border border-sky-200/20 bg-gradient-to-br from-blue-400/15 via-white/[.06] to-amber-300/10 p-4 shadow-lg shadow-blue-950/20 backdrop-blur-sm"><div className="flex items-center gap-2 text-xs font-bold uppercase tracking-[.16em] text-sky-200"><CalendarDays className="size-4" />Próximo examen de becas</div><h2 className="mt-2 text-lg font-bold text-white">{visibleExam.nombre}</h2><div className="mt-3 grid gap-2 text-sm text-blue-50 sm:grid-cols-2"><p className="flex items-center gap-2"><CalendarDays className="size-4 shrink-0 text-amber-300" />{formatExamDate(visibleExam.fecha_examen)}</p><p className="flex items-center gap-2"><Clock3 className="size-4 shrink-0 text-amber-300" />{visibleExam.hora_inicio_examen} – {visibleExam.hora_fin_examen}</p></div></div>}
                                <div className="grid max-w-xl grid-cols-3 gap-3 border-t border-white/15 pt-6 sm:gap-6">
                                    {content.hero.stats.map((stat) => <div key={stat.label}><p className="text-2xl font-bold text-amber-300 sm:text-3xl">{stat.value}</p><p className="mt-1 text-xs leading-5 text-blue-100 sm:text-sm">{stat.label}</p></div>)}
                                </div>
                            </div>
                            <div data-reveal className="reveal relative mx-auto w-full max-w-lg">
                                <div className="absolute -inset-5 rounded-[2rem] bg-sky-400/10 blur-2xl" />
                                <div className="relative min-h-[350px] overflow-hidden rounded-[1.75rem] border border-white/20 bg-gradient-to-br from-sky-300/25 via-blue-500/20 to-amber-300/20 p-5 shadow-2xl shadow-slate-950/30 sm:min-h-[440px] sm:p-8">
                                        {fotoPortadaUrl && <img src={fotoPortadaUrl} alt="Estudiantes en clases de la academia" className="absolute inset-0 size-full object-cover" />}
                                        <div aria-hidden="true" className="absolute inset-0 bg-gradient-to-t from-[#0c2643]/95 via-[#0c2643]/15 to-[#0c2643]/10" />
                                        <div aria-hidden="true" className="absolute inset-0 bg-[linear-gradient(135deg,transparent_30%,rgba(255,255,255,.07)_30%,transparent_31%),linear-gradient(45deg,transparent_58%,rgba(255,255,255,.06)_58%,transparent_59%)]" />
                                    <div className="relative flex h-full min-h-[300px] flex-col justify-between sm:min-h-[380px]">
                                        <div className="flex items-center justify-between"><span className="rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-blue-50">Horizonte · Admisión</span><Award className="size-6 text-amber-300" /></div>
                                        {!fotoPortadaUrl && <div className="mx-auto grid size-48 place-items-center rounded-full border border-white/20 bg-white/10 shadow-[0_0_100px_rgba(147,197,253,.28)] sm:size-60">
                                            <div className="grid size-36 place-items-center rounded-full border border-amber-200/40 bg-[#153b61]/70 sm:size-44"><GraduationCap className="size-20 text-amber-300 sm:size-24" /></div>
                                        </div>}
                                        <div className="rounded-2xl border border-white/15 bg-[#0c2643]/75 p-4 backdrop-blur"><p className="text-xs font-semibold uppercase tracking-wider text-sky-200">{content.hero.visualLabel}</p><p className="mt-2 text-sm text-white">{content.hero.caption}</p></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="metodologia" className="scroll-mt-24 bg-[#f7f9ff] py-20 dark:bg-[#070d1c] sm:py-24">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <SectionIntro eyebrow={content.methodology.eyebrow} title={content.methodology.title} description={content.methodology.description} />
                            <div className="mt-11 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                {content.methodology.features.map((feature) => {
                                    const Icon = featureIcons[feature.icon as keyof typeof featureIcons] ?? BookOpen;
                                    return <article key={feature.title} data-reveal className="reveal group rounded-2xl border border-slate-200/90 bg-white p-6 shadow-[0_12px_35px_rgba(15,23,42,.04)] transition duration-200 hover:-translate-y-1 hover:border-blue-300 hover:shadow-[0_18px_45px_rgba(37,99,235,.12)] dark:border-blue-200/10 dark:bg-[#0b1428] dark:shadow-[0_14px_40px_rgba(0,0,0,.25)] dark:hover:border-blue-400/40 dark:hover:shadow-[0_18px_48px_rgba(37,99,235,.12)]"><span className="grid size-12 place-items-center rounded-2xl bg-blue-50 text-blue-700 transition group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-500/10 dark:text-blue-300 dark:group-hover:bg-blue-500"><Icon className="size-6" /></span><h3 className="mt-5 text-lg font-bold">{feature.title}</h3><p className="mt-2 text-sm leading-6 text-slate-600">{feature.description}</p></article>;
                                })}
                            </div>
                        </div>
                    </section>

                    <section id="programas" className="scroll-mt-24 bg-white py-20 dark:bg-[#050a16] sm:py-24">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <SectionIntro eyebrow={content.programs.eyebrow} title={content.programs.title} description={content.programs.description} />
                            {programs.length === 0 ? <div className="mt-10 grid gap-4 md:grid-cols-3">{content.programs.fallback.map((program) => <ProgramCard key={program.name} program={program} buttonText={content.programs.inquire} />)}</div> : <div className="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3">{programs.map((program) => <ProgramCard key={program.id_ciclo} program={{ name: program.nombre_ciclo, type: program.tipo_ciclo, modality: program.modalidad, dates: `${program.fecha_inicio} — ${program.fecha_fin}` }} buttonText={content.programs.inquire} />)}</div>}
                        </div>
                    </section>

                    <section className="relative isolate overflow-hidden bg-[#08142a] py-20 text-white sm:py-24">
                        <div aria-hidden="true" className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_4%_50%,rgba(37,99,235,.2),transparent_32%),radial-gradient(ellipse_at_100%_100%,rgba(14,165,233,.12),transparent_38%)]" />
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <SectionIntro eyebrow={content.faculty.eyebrow} title={content.faculty.title} description={content.faculty.description} inverted />
                            <div className="mt-10 grid gap-4 sm:grid-cols-3">{docentesWeb.length ? docentesWeb.map((teacher) => <article key={teacher.id_profesor} data-reveal className="reveal overflow-hidden rounded-2xl border border-white/10 bg-white/[.06] p-4"><div className="grid aspect-[4/3] place-items-center overflow-hidden rounded-xl bg-gradient-to-br from-sky-400/30 to-indigo-500/30">{teacher.fotoUrl ? <img src={teacher.fotoUrl} alt={`Fotografía de ${teacher.nombre}`} className="size-full object-cover" /> : <div className="grid size-20 place-items-center rounded-full border border-white/25 bg-white/10"><Users className="size-8 text-white/80" /></div>}</div><h3 className="mt-4 font-bold">{teacher.nombre}</h3><p className="mt-1 text-sm text-blue-100">{teacher.cursos.join(' · ')}</p>{teacher.bio && <p className="mt-2 text-sm text-blue-50">{teacher.bio}</p>}</article>) : content.faculty.people.map((person: { name: string; area: string }, index: number) => <article key={person.name} data-reveal className="reveal overflow-hidden rounded-2xl border border-white/10 bg-white/[.06] p-4"><div className={`grid aspect-[4/3] place-items-center rounded-xl bg-gradient-to-br ${['from-sky-400/30 to-indigo-500/30', 'from-amber-300/30 to-rose-400/20', 'from-emerald-300/20 to-cyan-400/25'][index]}`}><div className="grid size-20 place-items-center rounded-full border border-white/25 bg-white/10"><Users className="size-8 text-white/80" /></div><span className="sr-only">{content.faculty.placeholder}</span></div><h3 className="mt-4 font-bold">{person.name}</h3><p className="mt-1 text-sm text-blue-100">{person.area}</p></article>)}</div>
                        </div>
                    </section>

                    <section id="resultados" className="scroll-mt-24 bg-[#f7f9ff] py-20 dark:bg-[#070d1c] sm:py-24">
                        <div className="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[.9fr_1.1fr] lg:px-8">
                            <div><SectionIntro eyebrow={content.results.eyebrow} title={content.results.title} description={content.results.description} align="left" /><p className="mt-6 text-xs text-slate-400">{content.results.note}</p></div>
                            <div className="grid gap-4 sm:grid-cols-3">{content.results.metrics.map((metric) => <div key={metric.label} data-reveal className="reveal flex min-h-40 flex-col justify-end rounded-2xl border border-blue-100 bg-white p-6 shadow-sm dark:border-blue-300/10 dark:bg-[#0b1428]"><p className="text-4xl font-bold tracking-tight text-blue-700 dark:text-blue-300">{metric.value}</p><p className="mt-2 text-sm text-slate-600">{metric.label}</p></div>)}</div>
                        </div>
                    </section>

                    <section className="bg-blue-50/70 py-20 dark:bg-[#0a1428] sm:py-24">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <SectionIntro eyebrow={content.schedule.eyebrow} title={content.schedule.title} description={content.schedule.description} />
                            <div className="mt-10 grid gap-4 md:grid-cols-3">{content.schedule.options.map((option) => <article key={option.title} className="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm dark:border-blue-200/10 dark:bg-[#0b1428]"><div className="flex items-center gap-3"><span className="grid size-10 place-items-center rounded-xl bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-300"><Check className="size-5" /></span><h3 className="font-bold">{option.title}</h3></div><p className="mt-4 text-sm leading-6 text-slate-600">{option.time}</p></article>)}</div>
                        </div>
                    </section>

                    <section className="py-20 sm:py-24">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <SectionIntro eyebrow={content.testimonials.eyebrow} title={content.testimonials.title} />
                            <div className="mt-10 grid gap-4 md:grid-cols-2">{content.testimonials.items.map((item) => <figure key={item.name} className="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-blue-200/10 dark:bg-[#0b1428]"><div aria-hidden="true" className="absolute -right-8 -top-10 size-36 rounded-full bg-blue-500/10 blur-3xl" /><blockquote className="relative text-lg leading-8 text-slate-700 dark:text-slate-200">“{item.quote}”</blockquote><figcaption className="relative mt-6 border-t border-slate-100 pt-4 dark:border-blue-200/10"><p className="font-bold">{item.name}</p><p className="text-sm text-slate-500">{item.detail}</p></figcaption></figure>)}</div>
                        </div>
                    </section>

                    <section className="bg-[#f7f9ff] py-20 dark:bg-[#070d1c] sm:py-24">
                        <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"><SectionIntro eyebrow={content.faq.eyebrow} title={content.faq.title} />
                            <div className="mt-9 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white px-5 shadow-sm dark:divide-blue-200/10 dark:border-blue-200/10 dark:bg-[#0b1428]">{content.faq.items.map((item) => <details key={item.question} className="group py-5"><summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold marker:hidden transition-colors hover:text-blue-700 dark:hover:text-blue-300">{item.question}<ChevronDown className="size-4 shrink-0 text-slate-500 transition group-open:rotate-180 group-open:text-blue-600 dark:group-open:text-blue-300" /></summary><p className="mt-3 pr-8 text-sm leading-6 text-slate-600 dark:text-slate-300">{item.answer}</p></details>)}</div>
                        </div>
                    </section>

                    <section id="contacto" className="relative isolate scroll-mt-24 overflow-hidden bg-[#071329] py-20 text-white sm:py-24">
                        <div aria-hidden="true" className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_10%_20%,rgba(37,99,235,.24),transparent_36%),radial-gradient(ellipse_at_90%_90%,rgba(14,165,233,.14),transparent_40%)]" />
                        <div className="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[1.1fr_.9fr] lg:px-8">
                            <div><p className="text-xs font-bold uppercase tracking-[.2em] text-amber-300">{content.contact.eyebrow}</p><h2 className="mt-3 max-w-xl font-serif text-3xl font-medium tracking-tight sm:text-4xl">{content.contact.title}</h2><p className="mt-4 max-w-xl leading-7 text-blue-100">{content.contact.description}</p>
                                <div className="mt-8 grid gap-5 sm:grid-cols-2"><div><p className="text-xs font-semibold uppercase tracking-wider text-blue-200">{content.contact.addressLabel}</p><p className="mt-2 text-sm">{content.contact.address}</p></div><div><p className="text-xs font-semibold uppercase tracking-wider text-blue-200">{content.contact.hoursLabel}</p><p className="mt-2 text-sm">{content.contact.hours}</p></div></div>
                                {content.contact.publicPhone && <p className="mt-5 text-sm text-blue-100">{content.contact.phone}: <a href={`tel:${content.contact.publicPhone}`} className="font-semibold text-white">{content.contact.publicPhone}</a></p>}
                                {content.contact.publicEmail && <p className="mt-2 text-sm text-blue-100">{content.contact.email}: <a href={`mailto:${content.contact.publicEmail}`} className="font-semibold text-white">{content.contact.publicEmail}</a></p>}
                                <a href={content.contact.whatsappUrl || '#contacto'} target={content.contact.whatsappUrl ? '_blank' : undefined} rel="noreferrer" className="mt-6 inline-flex min-h-11 items-center gap-2 rounded-xl border border-blue-300/30 bg-blue-400/5 px-4 text-sm font-semibold transition hover:border-blue-200/60 hover:bg-white/10">{content.contact.whatsapp}<ArrowRight className="size-4" /></a>
                                <div className="mt-8 grid min-h-40 place-items-center rounded-2xl border border-dashed border-blue-200/25 bg-white/[.03] p-6 text-center text-sm text-blue-100">{content.contact.mapPlaceholder}</div>
                            </div>
                            <div className="rounded-2xl bg-white p-5 text-slate-900 shadow-2xl ring-1 ring-slate-950/5 sm:p-7 dark:bg-slate-900 dark:text-white dark:ring-white/10">
                                <Form action={route('consultas.store')} method="post" resetOnSuccess>
                                    {({ errors, processing, wasSuccessful }) => <div className="grid gap-4">
                                        <Field label={content.contact.name} name="nombre" error={errors.nombre} required />
                                        <Field label={content.contact.phoneLabel ?? content.contact.phone} name="telefono" error={errors.telefono} type="tel" required />
                                        <Field label={content.contact.emailLabel ?? content.contact.email} name="email" error={errors.email} type="email" />
                                        <label className="grid gap-1.5 text-sm font-semibold">{content.contact.interest}<select name="interes" defaultValue="" className="h-11 rounded-xl border-slate-200 text-sm font-normal focus:border-blue-600 focus:ring-blue-600"><option value="">{content.contact.interestPlaceholder}</option>{content.contact.interests.map((interest) => <option key={interest} value={interest}>{interest}</option>)}</select><InputError message={errors.interes} /></label>
                                        <label className="grid gap-1.5 text-sm font-semibold">{content.contact.message}<textarea name="mensaje" rows={3} maxLength={5000} className="rounded-xl border-slate-200 text-sm font-normal focus:border-blue-600 focus:ring-blue-600" /><InputError message={errors.mensaje} /></label>
                                        {wasSuccessful && <p role="status" className="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{content.contact.success}</p>}
                                        <PrimaryButton disabled={processing} className="min-h-11 justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-950/20 transition hover:bg-blue-500">{processing ? content.contact.sending : content.contact.submit}<ArrowRight className="ml-2 size-4" /></PrimaryButton>
                                    </div>}
                                </Form>
                            </div>
                        </div>
                    </section>
                </main>
                <footer className="border-t border-blue-200/10 bg-[#040916] py-10 text-blue-100">
                    <div className="mx-auto flex max-w-7xl flex-col gap-7 px-4 sm:px-6 md:flex-row md:items-end md:justify-between lg:px-8">
                        <div><p className="font-bold text-white">{content.brand}</p><p className="mt-2 max-w-sm text-sm leading-6">{content.footer.description}</p></div>
                        <div className="flex flex-col gap-2 text-sm"><span className="font-semibold text-white">{content.footer.linksTitle}</span>{content.navigation.links.map((link) => <a key={link.href} href={link.href} className="hover:text-white">{link.label}</a>)}</div>
                        <p className="text-xs text-blue-200">{content.footer.copyright}</p>
                    </div>
                </footer>
            </div>
        </>
    );
}

function SectionIntro({ eyebrow, title, description, inverted = false, align = 'center' }: { eyebrow: string; title: string; description?: string; inverted?: boolean; align?: 'center' | 'left' }) {
    return <div className={`${align === 'center' ? 'mx-auto text-center' : 'text-left'} max-w-2xl`}><p className={`text-xs font-bold uppercase tracking-[.2em] ${inverted ? 'text-amber-300' : 'text-blue-700 dark:text-blue-300'}`}>{eyebrow}</p><h2 className={`mt-3 font-serif text-3xl font-medium tracking-tight sm:text-4xl ${inverted ? 'text-white' : 'text-slate-900 dark:text-white'}`}>{title}</h2>{description && <p className={`mt-4 leading-7 ${inverted ? 'text-blue-100' : 'text-slate-600 dark:text-slate-300'}`}>{description}</p>}</div>;
}

function ProgramCard({ program, buttonText }: { program: { name: string; type: string; modality: string; dates: string }; buttonText: string }) {
    return <article className="group flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-blue-300 hover:shadow-lg dark:border-blue-200/10 dark:bg-[#0b1428] dark:hover:border-blue-400/40 dark:hover:shadow-[0_16px_45px_rgba(37,99,235,.12)]"><div className="flex items-center justify-between"><span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-200">{program.type}</span><BookOpen className="size-5 text-blue-600 dark:text-blue-300" /></div><h3 className="mt-5 text-xl font-bold">{program.name}</h3><p className="mt-2 text-sm text-slate-600 dark:text-slate-300">{program.modality} · {program.dates}</p><a href="#contacto" className="mt-6 inline-flex items-center gap-2 text-sm font-bold text-blue-700 transition group-hover:gap-3 dark:text-blue-300">{buttonText}<ArrowRight className="size-4" /></a></article>;
}

function formatDate(value: string): string {
    const date = new Date(`${value.slice(0, 10)}T12:00:00`);
    return new Intl.DateTimeFormat('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
}

function formatExamDate(date: string): string {
    return new Intl.DateTimeFormat('es-PE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'America/Lima' }).format(new Date(`${date.slice(0, 10)}T12:00:00-05:00`));
}

function admissionWindow(openDate: string, closeDate: string): string {
    if (!openDate || !closeDate) return '';
    const today = new Date();
    const start = new Date(`${openDate.slice(0, 10)}T00:00:00`);
    const end = new Date(`${closeDate.slice(0, 10)}T23:59:59`);
    const range = `del ${formatDate(openDate)} al ${formatDate(closeDate)}. `;
    if (today < start) return `Matrículas abrirán ${range}`;
    if (today > end) return `El periodo de matrículas cerró ${range}`;
    return `Matrículas abiertas ${range}`;
}

function Field({ label, name, error, type = 'text', required = false }: { label: string; name: string; error?: string; type?: string; required?: boolean }) {
    return <label className="grid gap-1.5 text-sm font-semibold">{label}<input name={name} type={type} required={required} maxLength={name === 'telefono' ? 20 : 120} className="h-11 rounded-xl border-slate-200 text-sm font-normal focus:border-blue-600 focus:ring-blue-600" /><InputError message={error} /></label>;
}
