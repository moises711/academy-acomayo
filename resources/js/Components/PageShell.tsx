import { Head } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';

export default function PageShell({
    title,
    description,
    actions,
    children,
}: PropsWithChildren<{
    title: string;
    description?: string;
    actions?: ReactNode;
}>) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0 space-y-1">
                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">Academia / Gestión</p>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{title}</h1>
                        {description && <p className="max-w-2xl text-sm text-slate-500">{description}</p>}
                    </div>
                    {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
                </div>
            }
        >
            <Head title={title} />

            <div className="py-6 px-4 sm:py-8 sm:px-6 lg:px-8">
                <div className="mx-auto min-w-0 max-w-7xl space-y-6">
                    <FlashMessage />

                    {children}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
