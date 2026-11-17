import Form from '@/Components/InertiaForm';
import { router } from '@inertiajs/react';
import { KeyRound, ShieldCheck, UserPlus, Users, UserRoundX } from 'lucide-react';
import { useState } from 'react';
import PageShell from '@/Components/PageShell';
import InputError from '@/Components/InputError';
import Pagination from '@/Components/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';

type Rol = 'admin' | 'secretaria' | 'caja' | 'profesor' | 'estudiante' | 'apoderado';
type Usuario = {
    id_usuario: number;
    name: string;
    email: string;
    role: Rol;
    id_profesor: number | null;
    id_estudiante: number | null;
    id_apoderado: number | null;
    created_at: string;
    activo: boolean;
};
type Perfiles = {
    estudiantes: { id_estudiante: number; nombre: string; apellido: string; dni: string; user?: { id_usuario: number } | null }[];
    apoderados: { id_apoderado: number; nombre: string; apellido: string; user?: { id_usuario: number } | null; estudiante?: { nombre: string; apellido: string } }[];
    profesores: { id_profesor: number; nombre: string; apellido: string; dni: string; user?: { id_usuario: number } | null }[];
};

type Props = {
    usuarios: {
        data: Usuario[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    perfiles: Perfiles;
    currentUserId: number;
};

export default function UsuariosIndex({ usuarios, perfiles, currentUserId }: Props) {
    const [newRole, setNewRole] = useState<Rol>('secretaria');
    const roles: Record<Usuario['role'], string> = {
        admin: 'Administrador',
        secretaria: 'Secretaría',
        caja: 'Caja',
        profesor: 'Profesor',
        estudiante: 'Estudiante',
        apoderado: 'Apoderado',
    };

    return (
        <PageShell title="Usuarios y accesos" description="Crea cuentas para el equipo y asigna permisos de administración o secretaría.">
            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]">
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-wrap items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div className="flex items-center gap-3">
                            <div className="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700"><Users className="size-5" /></div>
                            <div><h2 className="font-semibold text-slate-900">Equipo de la academia</h2><p className="text-sm text-slate-500">{usuarios.total} cuentas registradas</p></div>
                        </div>
                    </div>
                    <div className="divide-y divide-slate-100">
                        {usuarios.data.map((usuario) => (
                            <div key={usuario.id_usuario} className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex min-w-0 items-center gap-3">
                                    <div className="grid size-10 shrink-0 place-items-center rounded-full bg-slate-100 font-semibold text-slate-600">{usuario.name.slice(0, 1).toUpperCase()}</div>
                                    <div className="min-w-0"><p className="truncate font-medium text-slate-900">{usuario.name}</p><p className="truncate text-sm text-slate-500">{usuario.email}</p></div>
                                </div>
                                <div className="flex flex-wrap items-center gap-3">
                                    <span className={`inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ${usuario.role === 'admin' ? 'bg-violet-50 text-violet-700' : 'bg-emerald-50 text-emerald-700'}`}>
                                        {usuario.role === 'admin' ? <ShieldCheck className="size-3.5" /> : <Users className="size-3.5" />}
                                        {roles[usuario.role]}
                                    </span>
                                    {!usuario.activo && <span className="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Inactiva</span>}
                                    <RoleEditor key={usuario.id_usuario} usuario={usuario} roles={roles} perfiles={perfiles} />
                                    {usuario.id_usuario !== currentUserId && <button type="button" onClick={() => {
                                        if (window.confirm(`¿Eliminar la cuenta de ${usuario.name}? Si conserva historial, se desactivará para proteger los registros.`)) {
                                            router.delete(route('usuarios.destroy', usuario.id_usuario), { preserveScroll: true });
                                        }
                                    }} className="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950" aria-label={`Eliminar cuenta de ${usuario.name}`}><UserRoundX className="size-3.5" />Eliminar</button>}
                                </div>
                            </div>
                        ))}
                        {usuarios.data.length === 0 && <p className="px-5 py-10 text-center text-sm text-slate-500">Aún no hay cuentas de equipo.</p>}
                    </div>
                    <div className="border-t border-slate-100 p-4"><Pagination links={usuarios.links} /></div>
                </section>

                <section className="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div className="mb-5 flex items-center gap-3">
                        <div className="grid size-10 place-items-center rounded-xl bg-indigo-50 text-indigo-700"><UserPlus className="size-5" /></div>
                        <div><h2 className="font-semibold text-slate-900">Agregar usuario</h2><p className="text-sm text-slate-500">Las cuentas nuevas empiezan sin privilegios de administrador.</p></div>
                    </div>
                    <Form action={route('usuarios.store')} method="post" resetOnSuccess className="grid gap-4">
                        {({ errors, processing }) => (
                            <>
                                <label className="grid gap-1.5 text-sm font-medium text-slate-700">Nombre completo
                                    <input name="name" required maxLength={255} className="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Nombre del colaborador" />
                                    <InputError message={errors.name} />
                                </label>
                                <label className="grid gap-1.5 text-sm font-medium text-slate-700">Correo electrónico
                                    <input name="email" required type="email" className="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="nombre@academia.com" />
                                    <InputError message={errors.email} />
                                </label>
                                <label className="grid gap-1.5 text-sm font-medium text-slate-700">Rol
                                    <select name="role" value={newRole} onChange={(event) => setNewRole(event.target.value as Rol)} className="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        {Object.entries(roles).map(([role, label]) => <option key={role} value={role}>{label}</option>)}
                                    </select>
                                    <InputError message={errors.role} />
                                </label>
                                <ProfileSelect role={newRole} perfiles={perfiles} errors={errors} />
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <label className="grid gap-1.5 text-sm font-medium text-slate-700">{newRole === 'estudiante' ? 'PIN de acceso (6 dígitos)' : 'Contraseña'}
                                        <input name="password" required type="password" minLength={newRole === 'estudiante' ? 6 : 8} maxLength={newRole === 'estudiante' ? 6 : undefined} pattern={newRole === 'estudiante' ? '[0-9]{6}' : undefined} inputMode={newRole === 'estudiante' ? 'numeric' : undefined} autoComplete="new-password" className="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                        <InputError message={errors.password} />
                                    </label>
                                    <label className="grid gap-1.5 text-sm font-medium text-slate-700">{newRole === 'estudiante' ? 'Confirmar PIN' : 'Confirmar contraseña'}
                                        <input name="password_confirmation" required type="password" minLength={newRole === 'estudiante' ? 6 : 8} maxLength={newRole === 'estudiante' ? 6 : undefined} pattern={newRole === 'estudiante' ? '[0-9]{6}' : undefined} inputMode={newRole === 'estudiante' ? 'numeric' : undefined} autoComplete="new-password" className="rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                    </label>
                                </div>
                                <div className="flex items-start gap-2 rounded-xl bg-slate-50 p-3 text-xs leading-relaxed text-slate-500">
                                    <KeyRound className="mt-0.5 size-4 shrink-0 text-slate-400" />
                                    {newRole === 'estudiante' ? 'El estudiante podrá cambiar su PIN desde su perfil.' : 'Comparte la contraseña de forma segura. El usuario podrá cambiarla en su perfil.'}
                                </div>
                                <PrimaryButton disabled={processing} className="justify-center rounded-xl bg-blue-600 py-3 text-xs hover:bg-blue-700">
                                    {processing ? 'Creando cuenta…' : 'Crear cuenta'}
                                </PrimaryButton>
                            </>
                        )}
                    </Form>
                </section>
            </div>
        </PageShell>
    );
}

function RoleEditor({ usuario, roles, perfiles }: { usuario: Usuario; roles: Record<Rol, string>; perfiles: Perfiles }) {
    const [role, setRole] = useState<Rol>(usuario.role);
    return <Form action={route('usuarios.update', usuario.id_usuario)} method="put" className="flex flex-col items-stretch gap-2 sm:items-end">
        {({ errors, processing }) => <>
            <select name="role" value={role} onChange={(event) => setRole(event.target.value as Rol)} className="rounded-lg border-slate-200 py-1.5 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" aria-label={`Cambiar rol de ${usuario.name}`}>
                {Object.entries(roles).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </select>
            <ProfileSelect role={role} perfiles={perfiles} user={usuario} errors={errors} />
            {role === 'estudiante' && usuario.role !== 'estudiante' && <div className="grid w-full gap-2 sm:max-w-56"><input name="password" type="password" required minLength={6} maxLength={6} pattern="[0-9]{6}" inputMode="numeric" autoComplete="new-password" placeholder="Nuevo PIN de 6 dígitos" className="w-full rounded-lg border-slate-200 py-1.5 text-xs" /><input name="password_confirmation" type="password" required minLength={6} maxLength={6} pattern="[0-9]{6}" inputMode="numeric" autoComplete="new-password" placeholder="Confirmar PIN" className="w-full rounded-lg border-slate-200 py-1.5 text-xs" /><InputError message={errors.password} /></div>}
            <div className="flex items-center gap-2"><button type="submit" disabled={processing} className="rounded-lg px-2 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 disabled:opacity-50">{processing ? 'Guardando…' : 'Guardar'}</button><InputError message={errors.role} /></div>
        </>}
    </Form>;
}

function ProfileSelect({ role, perfiles, user, errors }: { role: Rol; perfiles: Perfiles; user?: Usuario; errors?: Record<string, string> }) {
    if (role === 'estudiante') {
        return <div className="w-full sm:max-w-56"><select key={`estudiante-${user?.id_estudiante ?? 'nuevo'}`} name="id_estudiante" required defaultValue={user?.id_estudiante ?? ''} className="w-full rounded-lg border-slate-200 py-1.5 text-xs" aria-label="Vincular estudiante"><option value="">Vincular estudiante…</option>{perfiles.estudiantes.map((item) => <option key={item.id_estudiante} value={item.id_estudiante} disabled={Boolean(item.user && item.user.id_usuario !== user?.id_usuario)}>{item.nombre} {item.apellido} · {item.dni}{item.user && item.user.id_usuario !== user?.id_usuario ? ' · ya vinculado' : ''}</option>)}</select>{errors?.id_estudiante && <InputError message={errors.id_estudiante} />}</div>;
    }
    if (role === 'apoderado') {
        return <div className="w-full sm:max-w-56"><select key={`apoderado-${user?.id_apoderado ?? 'nuevo'}`} name="id_apoderado" required defaultValue={user?.id_apoderado ?? ''} className="w-full rounded-lg border-slate-200 py-1.5 text-xs" aria-label="Vincular apoderado"><option value="">Vincular apoderado…</option>{perfiles.apoderados.map((item) => <option key={item.id_apoderado} value={item.id_apoderado} disabled={Boolean(item.user && item.user.id_usuario !== user?.id_usuario)}>{item.nombre} {item.apellido}{item.estudiante ? ` · ${item.estudiante.nombre} ${item.estudiante.apellido}` : ''}{item.user && item.user.id_usuario !== user?.id_usuario ? ' · ya vinculado' : ''}</option>)}</select>{errors?.id_apoderado && <InputError message={errors.id_apoderado} />}</div>;
    }
    if (role === 'profesor') {
        return <div className="w-full sm:max-w-56"><select key={`profesor-${user?.id_profesor ?? 'nuevo'}`} name="id_profesor" required defaultValue={user?.id_profesor ?? ''} className="w-full rounded-lg border-slate-200 py-1.5 text-xs" aria-label="Vincular docente"><option value="">Vincular docente…</option>{perfiles.profesores.map((item) => <option key={item.id_profesor} value={item.id_profesor} disabled={Boolean(item.user && item.user.id_usuario !== user?.id_usuario)}>{item.nombre} {item.apellido} · {item.dni}{item.user && item.user.id_usuario !== user?.id_usuario ? ' · ya vinculado' : ''}</option>)}</select>{errors?.id_profesor && <InputError message={errors.id_profesor} />}</div>;
    }
    return null;
}
