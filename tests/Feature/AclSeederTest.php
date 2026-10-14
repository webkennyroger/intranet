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

test('Defensor role can only manage the council module', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Defensor')->first();

    expect($role->hasPermissionTo('council.view'))->toBeTrue()
        ->and($role->hasPermissionTo('council.create'))->toBeTrue()
        ->and($role->hasPermissionTo('council.update'))->toBeTrue()
        ->and($role->hasPermissionTo('council.delete'))->toBeTrue()
        ->and($role->hasPermissionTo('articles.view'))->toBeFalse()
        ->and($role->hasPermissionTo('users.view'))->toBeFalse()
        ->and($role->hasPermissionTo('appearance.view'))->toBeFalse();
});

test('Administrador role has no access to user management, appearance or the council module', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Administrador')->first();

    expect($role->hasPermissionTo('users.view'))->toBeFalse()
        ->and($role->hasPermissionTo('appearance.view'))->toBeFalse()
        ->and($role->hasPermissionTo('council.view'))->toBeFalse()
        ->and($role->hasPermissionTo('articles.view'))->toBeTrue()
        ->and($role->hasPermissionTo('articles.create'))->toBeTrue()
        ->and($role->hasPermissionTo('forum.view'))->toBeTrue()
        ->and($role->hasPermissionTo('forum.delete'))->toBeTrue()
        ->and($role->hasPermissionTo('activity-log.view'))->toBeFalse()
        ->and($role->hasPermissionTo('roles.manage'))->toBeFalse();
});

test('only Super Administrador has activity-log.view and roles.manage', function () {
    $this->seed(AclSeeder::class);

    expect(Role::where('name', 'Super Administrador')->first()->hasPermissionTo('activity-log.view'))->toBeTrue()
        ->and(Role::where('name', 'Super Administrador')->first()->hasPermissionTo('roles.manage'))->toBeTrue()
        ->and(Role::where('name', 'Administrador')->first()->hasPermissionTo('activity-log.view'))->toBeFalse()
        ->and(Role::where('name', 'Defensor')->first()->hasPermissionTo('activity-log.view'))->toBeFalse();
});

test('AclSeeder creates a default Super Administrador user', function () {
    $this->seed(AclSeeder::class);

    $admin = User::where('email', 'admin@defensoria.am.gov.br')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->isSuperAdmin())->toBeTrue();
});
