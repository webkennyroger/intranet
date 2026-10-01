<?php

use App\Enums\UserStatus;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('a user with status active isActive returns true', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    expect($user->isActive())->toBeTrue();
});

test('a user with status blocked isActive returns false', function () {
    $user = User::factory()->create(['status' => UserStatus::Blocked]);

    expect($user->isActive())->toBeFalse();
});

test('isSuperAdmin is true only for users holding the Super Administrador role', function () {
    Role::create(['name' => 'Super Administrador']);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');

    $editor = User::factory()->create();

    expect($admin->isSuperAdmin())->toBeTrue()
        ->and($editor->isSuperAdmin())->toBeFalse();
});

test('superAdministratorCount reflects how many users hold the role', function () {
    Role::create(['name' => 'Super Administrador']);

    expect(User::superAdministratorCount())->toBe(0);

    User::factory()->create()->assignRole('Super Administrador');

    expect(User::superAdministratorCount())->toBe(1);
});
