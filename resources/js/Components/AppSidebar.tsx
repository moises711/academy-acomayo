import * as React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { navigationGroups } from '@/constants/navigation';
import { ChevronDown } from 'lucide-react';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuItem,
    SidebarMenuButton,
    SidebarHeader,
    SidebarFooter,
    useSidebar,
} from '@/Components/Sidebar';

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { url, props: pageProps } = usePage();
    const path = url.split('?')[0];
    const { auth } = pageProps;
    const permissions = auth.permissions;
    const isAdmin = auth.user?.role === 'admin';
    const { setOpenMobile, state } = useSidebar();
    const activeGroup = navigationGroups.find((group) => group.items.some((item) =>
        (path === item.url || path.startsWith(`${item.url}/`)) &&
        !(item.url === '/mi' && isAdmin) &&
        (permissions.includes(item.permission) || item.permissionAny?.some((permission) => permissions.includes(permission))),
    ))?.title ?? null;
    const [openGroup, setOpenGroup] = React.useState<string | null>(activeGroup);

    React.useEffect(() => {
        setOpenGroup(activeGroup);
    }, [activeGroup]);

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <div className="flex items-center gap-3">
                    <div className="grid size-9 shrink-0 place-items-center rounded-xl bg-blue-500 text-sm font-black text-white">A</div>
                    <div className="min-w-0 group-data-[collapsible=icon]:hidden">
                            <p className="truncate text-sm font-bold text-slate-900 dark:text-white">Academia</p>
                            <p className="truncate text-xs font-medium text-slate-500 dark:text-slate-400">Panel de gestión</p>
                    </div>
                </div>
            </SidebarHeader>
            <SidebarContent>
                {navigationGroups.map((group) => {
                    const items = group.items.filter((item) =>
                        !(item.url === "/mi" && auth.user?.role === "admin") && (
                            permissions.includes(item.permission) ||
                            item.permissionAny?.some((permission) => permissions.includes(permission))
                        ),
                    );

                    const isCollapsible = isAdmin && items.length > 1;
                    const isExpanded = !isCollapsible || state === 'collapsed' || openGroup === group.title;
                    const contentId = `sidebar-group-${group.title.toLowerCase().replace(/\s+/g, '-')}`;

                    return items.length > 0 && <SidebarGroup key={group.title}>
                        <SidebarGroupLabel className={`transition-opacity duration-300 ${isCollapsible ? 'px-0' : ''}`}>
                            {isCollapsible ? <button
                                type="button"
                                aria-expanded={openGroup === group.title}
                                aria-controls={contentId}
                                onClick={() => setOpenGroup((current) => current === group.title ? null : group.title)}
                                className="flex h-full w-full items-center justify-between gap-2 rounded-md px-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 transition-colors hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-slate-400 dark:hover:text-blue-300 group-data-[collapsible=icon]:hidden"
                            >
                                <span className="flex min-w-0 items-center gap-2"><group.icon aria-hidden="true" className="size-3.5 shrink-0 text-blue-600 dark:text-blue-300" /><span className="truncate">{group.title}</span></span>
                                <ChevronDown aria-hidden="true" className={`size-3.5 transition-transform duration-200 ${openGroup === group.title ? 'rotate-180' : ''}`} />
                            </button> : <span className="flex items-center gap-2"><group.icon aria-hidden="true" className="size-3.5 shrink-0 text-blue-600 dark:text-blue-300" />{group.title}</span>}
                        </SidebarGroupLabel>
                        {isExpanded && <SidebarGroupContent id={contentId}>
                            <SidebarMenu>
                                {items.map((item) => {
                                    const isActive =
                                        path === item.url || path.startsWith(`${item.url}/`);

                                    return (
                                        <SidebarMenuItem key={item.title}>
                                            <SidebarMenuButton
                                                isActive={isActive}
                                                render={<Link href={item.url} prefetch />}
                                                onClick={() => setOpenMobile(false)}
                                                className={`group relative transition-all duration-200 ease-in-out hover:translate-x-1.5 hover:bg-blue-600 hover:text-white ${
                                                    isActive
                                                        ? 'bg-blue-600 text-white font-semibold shadow-md'
                                                        : 'text-slate-700 hover:shadow-sm dark:text-slate-300'
                                                }`}
                                            >
                                                {isActive && (
                                                    <div className="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-white" />
                                                )}
                                                <item.icon
                                                    className={`size-4 transition-transform duration-200 ease-in-out group-hover:scale-110 ${
                                                        isActive ? 'text-white' : 'group-hover:text-white'
                                                    }`}
                                                />
                                                <span className="transition-colors duration-200">
                                                    {item.title}
                                                </span>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    );
                                })}
                            </SidebarMenu>
                        </SidebarGroupContent>}
                    </SidebarGroup>;
                })}
            </SidebarContent>

            <SidebarFooter />
        </Sidebar>
    );
}
