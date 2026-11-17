<?php

namespace App\Http\Controllers;

use App\Models\Apoderado;
use App\Models\Auditoria;
use App\Models\Estudiante;
use App\Models\Profesor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->select(['id_usuario', 'id_rol', 'id_profesor', 'id_estudiante', 'id_apoderado', 'name', 'email', 'activo', 'created_at'])
            ->with('roleDefinition:id_rol,nombre')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
        $pageUserIds = collect($users->items())->pluck('id_usuario');

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $users->through(fn (User $user): array => [
                'id_usuario' => $user->id_usuario,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'id_profesor' => $user->id_profesor,
                'id_estudiante' => $user->id_estudiante,
                'id_apoderado' => $user->id_apoderado,
                'created_at' => $user->created_at,
                'activo' => $user->activo,
            ])
                ->withQueryString(),
            'perfiles' => [
                'estudiantes' => Estudiante::query()->where(fn ($query) => $query
                    ->whereDoesntHave('user')->orWhereHas('user', fn ($userQuery) => $userQuery->whereIn('id_usuario', $pageUserIds)))
                    ->with('user:id_usuario,id_estudiante')->orderBy('apellido')->orderBy('nombre')
                    ->get(['id_estudiante', 'nombre', 'apellido', 'dni']),
                'apoderados' => Apoderado::query()->where(fn ($query) => $query
                    ->whereDoesntHave('user')->orWhereHas('user', fn ($userQuery) => $userQuery->whereIn('id_usuario', $pageUserIds)))
                    ->with(['estudiante:id_estudiante,nombre,apellido', 'user:id_usuario,id_apoderado'])->orderBy('apellido')->orderBy('nombre')
                    ->get(['id_apoderado', 'id_estudiante', 'nombre', 'apellido', 'parentesco']),
                'profesores' => Profesor::query()->where(fn ($query) => $query
                    ->whereDoesntHave('user')->orWhereHas('user', fn ($userQuery) => $userQuery->whereIn('id_usuario', $pageUserIds)))
                    ->with('user:id_usuario,id_profesor')->orderBy('apellido')->orderBy('nombre')
                    ->get(['id_profesor', 'nombre', 'apellido', 'dni']),
            ],
            'currentUserId' => request()->user()->id_usuario,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isStudent = $request->input('role') === 'estudiante';
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', $isStudent ? 'digits:6' : Password::defaults()],
            'role' => ['required', Rule::exists('roles', 'nombre')],
            'id_estudiante' => ['required_if:role,estudiante', 'nullable', 'integer', 'exists:estudiantes,id_estudiante', Rule::unique('users', 'id_estudiante')],
            'id_apoderado' => ['required_if:role,apoderado', 'nullable', 'integer', 'exists:apoderados,id_apoderado', Rule::unique('users', 'id_apoderado')],
            'id_profesor' => ['required_if:role,profesor', 'nullable', 'integer', 'exists:profesores,id_profesor', Rule::unique('users', 'id_profesor')],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'id_rol' => Role::query()->where('nombre', $validated['role'])->value('id_rol'),
                'id_estudiante' => $validated['id_estudiante'] ?? null,
                'id_apoderado' => $validated['id_apoderado'] ?? null,
                'id_profesor' => $validated['id_profesor'] ?? null,
            ]);

            Auditoria::query()->create([
                'id_usuario' => request()->user()->id_usuario,
                'accion' => 'crear',
                'tabla' => 'users',
                'id_registro' => (string) $user->id_usuario,
                'datos_despues' => $user->only(['name', 'email', 'id_rol', 'id_profesor', 'id_estudiante', 'id_apoderado', 'activo']),
                'ip' => request()->ip(),
            ]);
        });

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $isStudent = $request->input('role') === 'estudiante';
        $validated = $request->validate([
            'role' => ['required', Rule::exists('roles', 'nombre')],
            'id_estudiante' => ['required_if:role,estudiante', 'nullable', 'integer', 'exists:estudiantes,id_estudiante', Rule::unique('users', 'id_estudiante')->ignore($usuario->id_usuario, 'id_usuario')],
            'id_apoderado' => ['required_if:role,apoderado', 'nullable', 'integer', 'exists:apoderados,id_apoderado', Rule::unique('users', 'id_apoderado')->ignore($usuario->id_usuario, 'id_usuario')],
            'id_profesor' => ['required_if:role,profesor', 'nullable', 'integer', 'exists:profesores,id_profesor', Rule::unique('users', 'id_profesor')->ignore($usuario->id_usuario, 'id_usuario')],
            'password' => [Rule::requiredIf($isStudent && $usuario->role !== 'estudiante'), 'nullable', 'confirmed', $isStudent ? 'digits:6' : Password::defaults()],
        ]);

        if ($usuario->activo && $usuario->role === 'admin' && $validated['role'] !== 'admin'
            && User::query()->where('activo', true)->whereHas('roleDefinition', fn ($query) => $query->where('nombre', 'admin'))->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Debe quedar al menos un administrador activo.',
            ]);
        }

        $before = $usuario->only(['id_rol', 'id_profesor', 'id_estudiante', 'id_apoderado']);
        DB::transaction(function () use ($request, $usuario, $validated, $before): void {
            $usuario->forceFill([
                'id_rol' => Role::query()->where('nombre', $validated['role'])->value('id_rol'),
                'id_estudiante' => $validated['id_estudiante'] ?? null,
                'id_apoderado' => $validated['id_apoderado'] ?? null,
                'id_profesor' => $validated['id_profesor'] ?? null,
                ...isset($validated['password']) ? ['password' => $validated['password']] : [],
            ])->save();

            Auditoria::query()->create([
                'id_usuario' => $request->user()->id_usuario,
                'accion' => 'editar',
                'tabla' => 'users',
                'id_registro' => (string) $usuario->id_usuario,
                'datos_antes' => $before,
                'datos_despues' => $usuario->only(['id_rol', 'id_profesor', 'id_estudiante', 'id_apoderado']),
                'ip' => $request->ip(),
            ]);
        });

        return back()->with('success', 'Permisos actualizados correctamente.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->id_usuario === $request->user()->id_usuario) {
            throw ValidationException::withMessages(['usuario' => 'No puedes eliminar tu propia cuenta desde esta pantalla.']);
        }

        if ($usuario->activo && $usuario->role === 'admin'
            && User::query()->where('activo', true)->whereHas('roleDefinition', fn ($query) => $query->where('nombre', 'admin'))->count() <= 1) {
            throw ValidationException::withMessages(['usuario' => 'Debe permanecer al menos un administrador activo.']);
        }

        $before = $usuario->only(['name', 'email', 'id_rol', 'id_profesor', 'id_estudiante', 'id_apoderado', 'activo']);
        $references = [
            ['hojas_respuesta', 'id_usuario'], ['registros_ingreso', 'id_usuario'], ['anuncios', 'id_usuario'],
            ['asistencias', 'id_usuario_registro'], ['pagos', 'id_usuario'], ['pagos', 'anulado_por'],
            ['pagos_docentes', 'id_usuario'], ['pagos_docentes', 'anulado_por'], ['auditoria', 'id_usuario'],
            ['notas', 'id_usuario'], ['materiales', 'id_usuario'],
        ];

        $deactivated = DB::transaction(function () use ($request, $usuario, $before, $references): bool {
            $mustRetain = collect($references)->contains(fn (array $reference): bool => DB::table($reference[0])->where($reference[1], $usuario->id_usuario)->exists()
            );

            if ($mustRetain) {
                $usuario->forceFill([
                    'activo' => false,
                    'id_profesor' => null,
                    'id_estudiante' => null,
                    'id_apoderado' => null,
                ])->save();
            } else {
                // The user table owns optional profile links with RESTRICT foreign keys.
                $usuario->forceFill([
                    'id_profesor' => null,
                    'id_estudiante' => null,
                    'id_apoderado' => null,
                ])->save();
                $usuario->delete();
            }

            Auditoria::query()->create([
                'id_usuario' => $request->user()->id_usuario,
                'accion' => $mustRetain ? 'editar' : 'eliminar',
                'tabla' => 'users',
                'id_registro' => (string) $usuario->id_usuario,
                'datos_antes' => $before,
                'datos_despues' => $mustRetain ? ['activo' => false, 'vinculos_desasociados' => true] : null,
                'ip' => $request->ip(),
            ]);

            return $mustRetain;
        });

        return back()->with('success', $deactivated
            ? 'La cuenta se desactivó y se conservaron sus registros históricos.'
            : 'La cuenta se eliminó correctamente.');
    }
}
