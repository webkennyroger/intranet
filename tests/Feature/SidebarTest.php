<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

test('a nav item with no permission requirement is visible to any authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSee('Mural');
});

test('a nav item requiring a permission the user lacks is not rendered', function () {
    Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'web']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertDontSee('Usuários');
});

test('a nav item requiring a permission the user has is rendered', function () {
    Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    $role->givePermissionTo('users.view');

    $user = User::factory()->create();
    $user->assignRole('Administrador');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSee('Usuários');
});
