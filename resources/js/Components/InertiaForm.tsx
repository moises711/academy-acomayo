import { Form as InertiaForm } from '@inertiajs/react';
import type { ComponentProps } from 'react';

type Props = ComponentProps<typeof InertiaForm>;

/**
 * Inertia 2.3 emits `inert={false}` when React 18 is idle, which React warns about.
 * `null` omits the attribute; forms in this app manage their own submit buttons.
 */
export default function Form(props: Props) {
    return <InertiaForm {...props} disableWhileProcessing={null as unknown as boolean} />;
}
