export function formatDate(value?: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value.slice(0, 10);
    }

    return date.toLocaleDateString('es-PE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function formatMoney(value: number | string | null | undefined): string {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(Number(value ?? 0));
}

export function dateInputValue(value?: string | null): string {
    if (!value) {
        return '';
    }

    return value.slice(0, 10);
}

export function timeInputValue(value?: string | null): string {
    if (!value) {
        return '';
    }

    return value.slice(0, 5);
}

export function fullName(person?: { nombre?: string; apellido?: string } | null): string {
    if (!person) {
        return '—';
    }

    return `${person.nombre ?? ''} ${person.apellido ?? ''}`.trim() || '—';
}
