<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        foreach ([
            'admin' => ['admin@academia.test', 'Administrador Academia'],
            'secretaria' => ['secretaria@academia.test', 'Secretaría Academia'],
            'caja' => ['caja@academia.test', 'Caja Academia'],
            'profesor' => ['profesor@academia.test', 'Profesor Academia'],
            'estudiante' => ['estudiante@academia.test', 'Estudiante Demo'],
            'apoderado' => ['apoderado@academia.test', 'Apoderado Demo'],
        ] as $roleName => [$email, $name]) {
            User::query()->updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make($roleName === 'estudiante' ? '123456' : 'Academia2026!'),
                'id_rol' => Role::query()->where('nombre', $roleName)->value('id_rol'),
                'email_verified_at' => now(),
                'activo' => true,
            ]);
        }

        $this->call(AcademiaDemoSeeder::class);
        $this->call(AsistenciaSeeder::class);
        $this->call(PagoDocenteSeeder::class);
        $this->call(ConvocatoriaBecaSeeder::class);
    }
}
