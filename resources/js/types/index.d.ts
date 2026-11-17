export interface User {
    id_usuario: number;
    name: string;
    email: string;
    role: 'admin' | 'secretaria' | 'caja' | 'profesor' | 'estudiante' | 'apoderado';
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
        permissions: string[];
    };
    flash: {
        success?: string | null;
        error?: string | null;
        warning?: string | null;
    };
};
