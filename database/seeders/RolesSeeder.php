<?php

namespace Database\Seeders;

use App\Support\Rol;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Rol::PERMISOS as $rol => $permisos) {
            foreach ($permisos as $permiso) {
                Permission::findOrCreate($permiso, 'web');
            }
            Role::findOrCreate($rol, 'web')->syncPermissions($permisos);
        }
    }
}
