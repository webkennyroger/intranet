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

        $contentModules = ['articles', 'categories', 'media', 'photos', 'videos', 'documents', 'events', 'trainings', 'birthday-banners', 'services'];

        // Mapa de papel => [descrição, lista de permissões concedidas a esse papel].
        $roleDefinitions = [
            'Super Administrador' => [
                'description' => 'Acesso total a todos os módulos e configurações do portal.',
                'permissions' => PermissionManager::inventory(),
            ],

            'Administrador' => [
                'description' => 'Gerencia o conteúdo do portal (Mural, mídia, serviços) e modera o Fórum.',
                'permissions' => [
                    ...self::crudFor($contentModules),
                    'forum.view', 'forum.delete',
                ],
            ],

            'Defensor' => [
                'description' => 'Gerencia os membros e os processos exibidos na página do Conselho Superior.',
                'permissions' => self::crudFor(['council', 'council-processes']),
            ],
        ];

        foreach ($roleDefinitions as $roleName => $definition) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->description = $definition['description'];
            $role->save();
            $role->syncPermissions($definition['permissions']);
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

    /**
     * @param  array<int, string>  $modules
     * @return array<int, string>
     */
    private static function crudFor(array $modules): array
    {
        $permissions = [];

        foreach ($modules as $module) {
            foreach (PermissionManager::ACTIONS as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }
}
