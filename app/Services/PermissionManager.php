<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;

class PermissionManager
{
    public static function inventory(): array
    {
        return [
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage',
            'roles.view',
            'content.view', 'content.create', 'content.update', 'content.delete', 'content.publish',
            'media.upload',
            'settings.manage',
        ];
    }

    public function ensureAll(): void
    {
        foreach (self::inventory() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
