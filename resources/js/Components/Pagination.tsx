import { Link } from '@inertiajs/react';

export default function Pagination({
    links,
}: {
    links: { url: string | null; label: string; active: boolean }[];
}) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav aria-label="Paginación" className="flex flex-wrap items-center justify-end gap-1.5">
            {links.map((link, index) => {
                const className = `inline-flex min-h-9 min-w-9 items-center justify-center rounded-xl px-3 py-1.5 text-sm font-semibold transition-colors ${
                    link.active
                        ? 'bg-gradient-to-br from-blue-700 to-blue-600 text-white shadow-sm ring-1 ring-blue-700/20'
                        : 'border border-slate-200 bg-white text-slate-700 hover:border-blue-200 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-800 dark:hover:bg-blue-950/60'
                } ${!link.url ? 'pointer-events-none opacity-45' : 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2'}`;

                if (!link.url) {
                    return (
                        <span
                            key={`${link.label}-${index}`}
                            className={className}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    );
                }

                return (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        className={className}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                );
            })}
        </nav>
    );
}
