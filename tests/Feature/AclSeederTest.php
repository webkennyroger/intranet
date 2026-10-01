<?php

use App\Models\User;
use App\Services\PermissionManager;
use Database\Seeders\AclSeeder;
use Spatie\Permission\Models\Role;

test('AclSeeder creates exactly the four intranet roles', function () {
    $this->seed(AclSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['Administrador', 'Editor', 'Gestor', 'Super Administrador']);
});

test('Super Administrador role has every permission in the inventory', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Super Administrador')->first();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect(PermissionManager::inventory())->sort()->values()->all());
});

test('Editor role cannot publish content', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Editor')->first();

    expect($role->hasPermissionTo('content.create'))->toBeTrue()
        ->and($role->hasPermissionTo('content.publish'))->toBeFalse();
});

test('AclSeeder creates a default Super Administrador user', function () {
    $this->seed(AclSeeder::class);

    $admin = User::where('email', 'admin@defensoria.am.gov.br')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->isSuperAdmin())->toBeTrue();
});
