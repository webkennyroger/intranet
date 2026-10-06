<?php

use App\Models\User;
use Database\Seeders\AclSeeder;

test('the admin placeholder page for Assistente Geral renders inside the admin layout', function () {
    $this->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    $this->actingAs($user)->get(route('admin.placeholder.assistente-geral'))
        ->assertOk()
        ->assertSee('Assistente Geral')
        ->assertSee('Esta seção está em construção.');
});
