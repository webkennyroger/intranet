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

test('creating a user with role Editor does not require a department', function () {
    actingAsAdmin();

    Livewire::test(UserForm::class)
        ->set('name', 'João Lima')
        ->set('username', 'joao.lima')
        ->set('email', 'joao@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Editor')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'joao@defensoria.am.gov.br')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('Editor'))->toBeTrue()
        ->and($user->department)->toBeNull();
});

test('creating a user with role Gestor requires a department', function () {
    actingAsAdmin();

    Livewire::test(UserForm::class)
        ->set('name', 'Marina Alves')
        ->set('username', 'marina.alves')
        ->set('email', 'marina@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Gestor')
        ->set('department', '')
        ->call('save')
        ->assertHasErrors(['department']);

    expect(User::where('email', 'marina@defensoria.am.gov.br')->first())->toBeNull();
});

test('creating a user with role Gestor and a department succeeds', function () {
    actingAsAdmin();

    Livewire::test(UserForm::class)
        ->set('name', 'Marina Alves')
        ->set('username', 'marina.alves')
        ->set('email', 'marina@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Gestor')
        ->set('department', 'Recursos Humanos')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'marina@defensoria.am.gov.br')->first();
    expect($user->department)->toBe('Recursos Humanos')
        ->and($user->hasRole('Gestor'))->toBeTrue();
});

test('editing an existing user updates their role without requiring a new password', function () {
    actingAsAdmin();
    $editor = User::factory()->create(['email' => 'troca@defensoria.am.gov.br']);
    $editor->assignRole('Editor');

    Livewire::test(UserForm::class, ['user' => $editor])
        ->set('role', 'Gestor')
        ->set('department', 'Comunicação')
        ->call('save')
        ->assertHasNoErrors();

    expect($editor->refresh()->hasRole('Gestor'))->toBeTrue()
        ->and($editor->department)->toBe('Comunicação');
});

test('email must be unique when creating a user', function () {
    actingAsAdmin();
    User::factory()->create(['email' => 'duplicado@defensoria.am.gov.br']);

    Livewire::test(UserForm::class)
        ->set('name', 'Outro Nome')
        ->set('username', 'outro.nome')
        ->set('email', 'duplicado@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Editor')
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
