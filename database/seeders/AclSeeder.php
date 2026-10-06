<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\PermissionManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cria os três papéis de acesso da intranet (Super Administrador, Administrador
 * e Defensor) com suas permissões, garante que todas as permissões do sistema
 * existam no banco e cria o usuário admin padrão para o primeiro acesso.
 */
class AclSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Garante que todas as permissões cadastradas em PermissionManager existam no banco.
        (new PermissionManager)->ensureAll();

        // Mapa de papel => lista de permissões concedidas a esse papel.
        $roleDefinitions = [
            'Super Administrador' => PermissionManager::inventory(),

            'Administrador' => [
                'content.view', 'content.create', 'content.update', 'content.delete', 'content.publish',
                'media.upload',
                'services.manage',
                'forum.manage',
            ],

            'Defensor' => [
                'council.manage',
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
