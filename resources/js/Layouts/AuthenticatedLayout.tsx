import { AppSidebar } from '@/Components/AppSidebar';
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/Components/Sidebar';
import { Link, usePage } from '@inertiajs/react';
import { FormEvent, PropsWithChildren, ReactNode, useState } from 'react';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import MobileNavigation from '@/Components/MobileNavigation';
import { Search } from 'lucide-react';
import ThemeToggle from '@/Components/ThemeToggle';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const user = usePage().props.auth.user!;
    const permissions = usePage().props.auth.permissions;
    const [search, setSearch] = useState('');
    const [loggingOut, setLoggingOut] = useState(false);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (search.trim() !== '' && permissions.includes('estudiantes.ver')) {
            router.get(route('estudiantes.index'), { search: search.trim() }, { preserveState: false });
        }
    };

    return (
        <SidebarProvider defaultOpen={true}>
            <AppSidebar />
            <SidebarInset className="app-content flex min-h-screen flex-col bg-slate-50 transition-colors duration-200 ease-in-out dark:bg-slate-950">
                <nav className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/90 px-3 shadow-sm backdrop-blur transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900/90 sm:px-6">
                    <div className="flex items-center gap-3">
                        <SidebarTrigger className="text-slate-600" />
                        <div className="hidden h-6 w-px bg-slate-200 sm:block" />
                        <p className="hidden text-sm font-medium text-slate-500 sm:block">Gestión académica</p>
                    </div>
                    <div className="flex items-center gap-2 sm:gap-3">
                        {permissions.includes('estudiantes.ver') && (
                            <form onSubmit={submitSearch} className="relative hidden md:block">
                                <label htmlFor="global-search" className="sr-only">Buscar estudiantes</label>
                                <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    id="global-search"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Buscar estudiante…"
                                    className="h-10 w-52 rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 lg:w-64"
                                />
                            </form>
                        )}
                        <ThemeToggle />
                        <div className="grid size-9 place-items-center rounded-full bg-blue-50 text-sm font-bold text-blue-700">
                            {user.name.slice(0, 1).toUpperCase()}
                        </div>
                        <div className="hidden sm:block">
                            <p className="text-sm font-semibold text-slate-800">{user.name}</p>
                            <p className="text-xs capitalize text-slate-500">{user.role}</p>
                        </div>
                        <button
                            type="button"
                            disabled={loggingOut}
                            onClick={() => {
                                setLoggingOut(true);
                                router.post(route('logout'), {}, {
                                    onSuccess: () => router.clearHistory(),
                                    onFinish: () => setLoggingOut(false),
                                });
                            }}
                            className="ml-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 disabled:cursor-wait disabled:opacity-60 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                        >
                            {loggingOut ? 'Saliendo…' : 'Salir'}
                        </button>
                    </div>
                </nav>

                {header && (
                    <header className="bg-white transition-colors duration-200 dark:bg-slate-900">
                        <div className="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {header}
                        </div>
                    </header>
                )}

                <main className="animate-view-enter min-w-0 flex-1">
                    {children}
                </main>
            </SidebarInset>
            <MobileNavigation />
        </SidebarProvider>
    );
}
