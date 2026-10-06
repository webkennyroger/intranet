<?php

use App\Models\User;
use Database\Seeders\AclSeeder;

test('a guest is redirected to login when visiting dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('an Administrador sees the dashboard with service stats but no user stats', function () {
    $this->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Total de Usuários')
        ->assertSee('Serviços Ativos');
});

test('a Super Administrador sees the dashboard with user and service stats', function () {
    $this->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Total de Usuários')
        ->assertSee('Serviços Ativos');
});

test('a Defensor sees the dashboard without content, user or service stats', function () {
    $this->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Defensor');

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Artigos Publicados')
        ->assertDontSee('Total de Usuários')
        ->assertDontSee('Serviços Ativos');
});
