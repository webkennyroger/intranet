<?php

use App\Models\User;
use App\Services\PermissionManager;
use Database\Seeders\AclSeeder;
use Spatie\Permission\Models\Role;

test('AclSeeder creates exactly the three intranet roles', function () {
    $this->seed(AclSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['Administrador', 'Defensor', 'Super Administrador']);
});

test('Super Administrador role has every permission in the inventory', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Super Administrador')->first();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect(PermissionManager::inventory())->sort()->values()->all());
});

test('Defensor role can only manage the council page', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Defensor')->first();

    expect($role->hasPermissionTo('council.manage'))->toBeTrue()
        ->and($role->hasPermissionTo('content.view'))->toBeFalse()
        ->and($role->hasPermissionTo('users.view'))->toBeFalse()
        ->and($role->hasPermissionTo('settings.manage'))->toBeFalse();
});

test('Administrador role has no access to user management, settings or the council page', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Administrador')->first();

    expect($role->hasPermissionTo('users.view'))->toBeFalse()
        ->and($role->hasPermissionTo('settings.manage'))->toBeFalse()
        ->and($role->hasPermissionTo('council.manage'))->toBeFalse()
        ->and($role->hasPermissionTo('content.view'))->toBeTrue()
        ->and($role->hasPermissionTo('forum.manage'))->toBeTrue()
        ->and($role->hasPermissionTo('activity-log.view'))->toBeFalse();
});

test('only Super Administrador has activity-log.view', function () {
    $this->seed(AclSeeder::class);

    expect(Role::where('name', 'Super Administrador')->first()->hasPermissionTo('activity-log.view'))->toBeTrue()
        ->and(Role::where('name', 'Administrador')->first()->hasPermissionTo('activity-log.view'))->toBeFalse()
        ->and(Role::where('name', 'Defensor')->first()->hasPermissionTo('activity-log.view'))->toBeFalse();
});

test('AclSeeder creates a default Super Administrador user', function () {
    $this->seed(AclSeeder::class);

    $admin = User::where('email', 'admin@defensoria.am.gov.br')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->isSuperAdmin())->toBeTrue();
});
