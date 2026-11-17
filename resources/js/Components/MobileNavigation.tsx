import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { navigationGroups } from '@/constants/navigation';
import { useSidebar } from '@/Components/Sidebar';

export default function MobileNavigation() {
    const { url, props: { auth } } = usePage();
    const { openMobile, setOpenMobile } = useSidebar();
    const path = url.split('?')[0];
    const available = navigationGroups.flatMap((group) => group.items).filter((item) =>
        !(item.url === "/mi" && auth.user?.role === "admin") && (
            auth.permissions.includes(item.permission) || item.permissionAny?.some((permission) => auth.permissions.includes(permission))
        ),
    );
    const preferred = ['/dashboard', '/mi', '/estudiantes', '/asistencias', '/pagos'];
    const shortcuts = available.filter((item) => preferred.includes(item.url)).slice(0, 4);
    if (shortcuts.length === 0) shortcuts.push(...available.slice(0, 4));
    const active = (href: string) => path === href || path.startsWith(`${href}/`);
    const otherActive = available.some((item) => active(item.url) && !shortcuts.includes(item));

    return (
        <nav aria-label="Navegación inferior" className="mobile-navigation fixed inset-x-0 bottom-0 z-30 flex border-t border-[hsl(var(--border))] bg-[hsl(var(--surface))] text-[hsl(var(--muted))] shadow-sm md:hidden print:hidden">
            {shortcuts.map((item) => (
                <Link key={item.url} href={item.url} aria-current={active(item.url) ? 'page' : undefined} onClick={() => setOpenMobile(false)} className={`mobile-navigation-item ${active(item.url) ? 'text-[hsl(var(--action))]' : ''}`}>
                    <item.icon aria-hidden="true" className="size-5 shrink-0" />
                    <span className="w-full truncate text-center text-xs font-semibold">{item.url === '/dashboard' ? 'Inicio' : item.title}</span>
                </Link>
            ))}
            <button type="button" aria-label="Abrir todos los módulos" aria-expanded={openMobile} onClick={() => setOpenMobile(!openMobile)} className={`mobile-navigation-item ${otherActive || openMobile ? 'text-[hsl(var(--action))]' : ''}`}>
                <Menu aria-hidden="true" className="size-5" />
                <span className="text-xs font-semibold">Menú</span>
            </button>
        </nav>
    );
}
