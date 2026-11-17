import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function FlashMessage() {
    const { flash } = usePage<PageProps>().props;

    if (!flash?.success && !flash?.error && !flash?.warning) {
        return null;
    }

    return (
        <div className="space-y-2">
            {flash.success && (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {flash.error}
                </div>
            )}
            {flash.warning && (
                <div role="status" className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {flash.warning}
                </div>
            )}
        </div>
    );
}
