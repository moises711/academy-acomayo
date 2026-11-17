import { Moon, Sun } from 'lucide-react';
import { useTheme } from '@/Components/ThemeProvider';

export default function ThemeToggle({ className = '' }: { className?: string }) {
    const { theme, toggleTheme } = useTheme();
    const isDark = theme === 'dark';

    return (
        <button
            type="button"
            onClick={toggleTheme}
            className={`grid size-10 shrink-0 place-items-center rounded-xl text-slate-600 transition-colors hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-slate-200 dark:hover:bg-slate-800 ${className}`}
            aria-label={isDark ? 'Activar modo claro' : 'Activar modo oscuro'}
            title={isDark ? 'Activar modo claro' : 'Activar modo oscuro'}
        >
            {isDark ? <Sun className="size-4" /> : <Moon className="size-4" />}
        </button>
    );
}
