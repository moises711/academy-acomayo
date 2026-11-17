import { createContext, PropsWithChildren, useContext, useEffect, useState } from 'react';

type Theme = 'light' | 'dark';
type ThemeContextValue = { theme: Theme; toggleTheme: () => void };

const ThemeContext = createContext<ThemeContextValue | null>(null);

function getInitialTheme(): Theme {
    const storedTheme = window.localStorage.getItem('academia-theme');
    const theme = storedTheme === 'light' || storedTheme === 'dark'
        ? storedTheme
        : window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

    document.documentElement.classList.toggle('dark', theme === 'dark');

    return theme;
}

export function ThemeProvider({ children }: PropsWithChildren) {
    const [theme, setTheme] = useState<Theme>(getInitialTheme);

    useEffect(() => {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        window.localStorage.setItem('academia-theme', theme);
    }, [theme]);

    const toggleTheme = () => setTheme((current) => current === 'dark' ? 'light' : 'dark');

    return <ThemeContext.Provider value={{ theme, toggleTheme }}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
    const context = useContext(ThemeContext);

    if (!context) {
        throw new Error('useTheme debe usarse dentro de ThemeProvider.');
    }

    return context;
}
