<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * RolesSeeder
 * Clase para poblar la tabla de roles con los roles predefinidos.
 * Utiliza Spatie Permission para manejar los roles y permisos.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['administrador', 'especialista', 'paciente', 'usuario'];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role, 'guard_name' => 'api']
            );
        }
    }
}
