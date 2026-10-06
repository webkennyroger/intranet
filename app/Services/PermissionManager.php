<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;

/**
 * Centraliza a lista de permissões do sistema (ACL) e garante que todas
 * existam no banco, servindo de fonte única para seeders e telas de papéis.
 */
class PermissionManager
{
    /**
     * Lista mestre de todas as permissões conhecidas pelo sistema. Qualquer
     * nova permissão precisa ser adicionada aqui para existir no banco e
     * poder ser atribuída a papéis.
     */
    public static function inventory(): array
    {
        return [
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage',
            'roles.view',
            'content.view', 'content.create', 'content.update', 'content.delete', 'content.publish',
            'media.upload',
            'settings.manage',
            'services.manage',
            'council.manage',
            'forum.manage',
            'activity-log.view',
        ];
    }

    public function ensureAll(): void
    {
        foreach (self::inventory() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
