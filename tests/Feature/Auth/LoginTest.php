<?php

use App\Enums\UserStatus;
use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

test('a user with correct credentials and active status can log in', function () {
    $user = User::factory()->create([
        'email' => 'ana@defensoria.am.gov.br',
        'password' => bcrypt('senha-correta'),
        'status' => UserStatus::Active,
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'senha-correta')
        ->call('submit')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('a user with the wrong password cannot log in', function () {
    User::factory()->create([
        'email' => 'ana@defensoria.am.gov.br',
        'password' => bcrypt('senha-correta'),
        'status' => UserStatus::Active,
    ]);

    Livewire::test(Login::class)
        ->set('email', 'ana@defensoria.am.gov.br')
        ->set('password', 'senha-errada')
        ->call('submit')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('a blocked user cannot log in even with the correct password', function () {
    User::factory()->create([
        'email' => 'bloqueado@defensoria.am.gov.br',
        'password' => bcrypt('senha-correta'),
        'status' => UserStatus::Blocked,
    ]);

    Livewire::test(Login::class)
        ->set('email', 'bloqueado@defensoria.am.gov.br')
        ->set('password', 'senha-correta')
        ->call('submit')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('logout ends the session and redirects to login', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
