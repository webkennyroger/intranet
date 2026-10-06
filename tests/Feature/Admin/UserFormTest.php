<?php

use App\Livewire\Admin\Users\UserForm;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsAdmin(): User
{
    test()->seed(AclSeeder::class);

    // Delete the default admin created by the seeder so we can be sure our test admin is the only one
    User::where('email', 'admin@defensoria.am.gov.br')->delete();

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    test()->actingAs($admin);

    return $admin;
}

test('creating a user with role Defensor succeeds and department is optional', function () {
    actingAsAdmin();

    Livewire::test(UserForm::class)
        ->set('name', 'Marina Alves')
        ->set('username', 'marina.alves')
        ->set('email', 'marina@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Defensor')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'marina@defensoria.am.gov.br')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('Defensor'))->toBeTrue()
        ->and($user->department)->toBeNull();
});

test('editing an existing user updates their role without requiring a new password', function () {
    actingAsAdmin();
    $target = User::factory()->create(['email' => 'troca@defensoria.am.gov.br']);
    $target->assignRole('Administrador');

    Livewire::test(UserForm::class, ['user' => $target])
        ->set('role', 'Defensor')
        ->call('save')
        ->assertHasNoErrors();

    expect($target->refresh()->hasRole('Defensor'))->toBeTrue();
});

test('email must be unique when creating a user', function () {
    actingAsAdmin();
    User::factory()->create(['email' => 'duplicado@defensoria.am.gov.br']);

    Livewire::test(UserForm::class)
        ->set('name', 'Outro Nome')
        ->set('username', 'outro.nome')
        ->set('email', 'duplicado@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Administrador')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('demoting the sole Super Administrador away from that role is blocked', function () {
    $admin = actingAsAdmin();

    Livewire::test(UserForm::class, ['user' => $admin])
        ->set('role', 'Administrador')
        ->call('save')
        ->assertHasErrors(['role']);

    expect($admin->refresh()->hasRole('Super Administrador'))->toBeTrue();
});

test('demoting one of two Super Administradores is allowed', function () {
    $admin = actingAsAdmin();
    $second = User::factory()->create();
    $second->assignRole('Super Administrador');

    Livewire::test(UserForm::class, ['user' => $second])
        ->set('role', 'Administrador')
        ->call('save')
        ->assertHasNoErrors();

    expect($second->refresh()->hasRole('Administrador'))->toBeTrue()
        ->and($admin->refresh()->hasRole('Super Administrador'))->toBeTrue();
});
