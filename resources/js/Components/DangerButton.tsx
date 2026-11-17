import { ButtonHTMLAttributes } from 'react';

export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            className={
                `inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-transparent bg-gradient-to-r from-red-700 to-rose-600 px-4 py-2.5 text-sm font-semibold tracking-wide text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:from-red-800 hover:to-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 active:translate-y-0 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 motion-reduce:transform-none motion-reduce:transition-none ${
                    disabled ? 'cursor-not-allowed' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
