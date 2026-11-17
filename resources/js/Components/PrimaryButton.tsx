import { ButtonHTMLAttributes } from 'react';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            className={
                `inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-transparent bg-gradient-to-r from-blue-700 to-blue-600 px-4 py-2.5 text-sm font-semibold tracking-wide text-white shadow-[0_6px_16px_rgba(37,99,235,0.2)] transition duration-200 hover:-translate-y-0.5 hover:from-blue-800 hover:to-blue-700 hover:shadow-[0_10px_22px_rgba(37,99,235,0.25)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 active:translate-y-0 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 motion-reduce:transform-none motion-reduce:transition-none ${
                    disabled ? 'cursor-not-allowed' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
