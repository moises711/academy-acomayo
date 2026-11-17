export default function StatusBadge({
    value,
}: {
    value: string;
}) {
    const styles: Record<string, string> = {
        activo: 'bg-emerald-50 text-emerald-700',
        inactivo: 'bg-gray-100 text-gray-600',
        egresado: 'bg-indigo-50 text-indigo-700',
        inscrito: 'bg-emerald-50 text-emerald-700',
        retirado: 'bg-red-50 text-red-700',
        congelado: 'bg-amber-50 text-amber-800',
        Pendiente: 'bg-amber-50 text-amber-800',
        Pagado: 'bg-emerald-50 text-emerald-700',
        Vencido: 'bg-red-50 text-red-700',
        Presencial: 'bg-blue-50 text-blue-700',
        Virtual: 'bg-violet-50 text-violet-700',
        'Híbrido': 'bg-cyan-50 text-cyan-700',
        Aptitud: 'bg-orange-50 text-orange-700',
        Ciencias: 'bg-sky-50 text-sky-700',
        Letras: 'bg-rose-50 text-rose-700',
    };

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ${
                styles[value] ?? 'bg-gray-100 text-gray-700'
            }`}
        >
            {value}
        </span>
    );
}
