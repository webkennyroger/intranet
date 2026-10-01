<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('a nav item with no permission requirement is visible to any authenticated user', function () {
    config(['sidebar' => [
        ['label' => 'Dashboard', 'icon' => 'house', 'uri' => '/dashboard', 'route' => 'dashboard', 'permission' => null],
        ['group' => 'TEST', 'items' => [
            ['label' => 'ItemSemPermissão', 'icon' => 'newspaper', 'uri' => '/dashboard', 'route' => 'dashboard', 'permission' => null],
        ]],
    ]]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('ItemSemPermissão');
});

test('a nav item requiring a permission the user lacks is not rendered', function () {
    config(['sidebar' => [
        ['label' => 'Dashboard', 'icon' => 'house', 'uri' => '/dashboard', 'route' => 'dashboard', 'permission' => null],
        ['group' => 'TEST', 'items' => [
            ['label' => 'ItemProtegido', 'icon' => 'lock', 'uri' => '/dashboard', 'route' => 'dashboard', 'permission' => 'users.view'],
        ]],
    ]]);

    Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'web']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertDontSee('ItemProtegido');
});

test('a nav item requiring a permission the user has is rendered', function () {
    config(['sidebar' => [
        ['label' => 'Dashboard', 'icon' => 'house', 'uri' => '/dashboard', 'route' => 'dashboard', 'permission' => null],
        ['group' => 'TEST', 'items' => [
            ['label' => 'ItemProtegido', 'icon' => 'lock', 'uri' => '/dashboard', 'route' => 'dashboard', 'permission' => 'users.view'],
        ]],
    ]]);

    Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    $role->givePermissionTo('users.view');

    $user = User::factory()->create();
    $user->assignRole('Administrador');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('ItemProtegido');
});
