<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\PermissionManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AclSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        (new PermissionManager)->ensureAll();

        $roleDefinitions = [
            'Super Administrador' => PermissionManager::inventory(),

            'Administrador' => [
                'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage',
                'roles.view',
                'content.view', 'content.create', 'content.update', 'content.delete', 'content.publish',
                'media.upload',
                'settings.manage',
            ],

            'Gestor' => [
                'content.view', 'content.create', 'content.update', 'content.publish',
                'media.upload',
            ],

            'Editor' => [
                'content.view', 'content.create', 'content.update',
                'media.upload',
            ],
        ];

        foreach ($roleDefinitions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }

        // Default dev credential (admin@defensoria.am.gov.br / password) — must be rotated before any shared/staging/production deployment.
        $admin = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin@defensoria.am.gov.br'],
            [
                'name' => 'Super Administrador',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        $admin->syncRoles(['Super Administrador']);
    }
}
