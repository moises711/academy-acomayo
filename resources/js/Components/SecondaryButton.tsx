import { ButtonHTMLAttributes } from 'react';

export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/90 px-4 py-2.5 text-sm font-semibold tracking-wide text-slate-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 active:translate-y-0 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 motion-reduce:transform-none motion-reduce:transition-none dark:border-slate-700 dark:bg-slate-800/90 dark:text-slate-200 dark:hover:border-blue-800 dark:hover:bg-blue-950/60 dark:hover:text-blue-200 ${
                    disabled ? 'cursor-not-allowed' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
