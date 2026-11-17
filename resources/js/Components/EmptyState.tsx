import { ReactNode } from 'react';

export default function EmptyState({
    title,
    description,
    action,
}: {
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <div className="rounded-xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center">
            <h3 className="text-base font-semibold text-gray-900">{title}</h3>
            <p className="mt-1 text-sm text-gray-500">{description}</p>
            {action && <div className="mt-4 flex justify-center">{action}</div>}
        </div>
    );
}
