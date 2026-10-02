<?php

use App\Models\User;
use Database\Seeders\AclSeeder;

test('an Administrador sees Gerenciar Serviços in the real sidebar config', function () {
    $this->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertSee('Gerenciar Serviços');
});

test('an Editor does not see Gerenciar Serviços in the real sidebar config', function () {
    $this->seed(AclSeeder::class);
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $response = $this->actingAs($editor)->get(route('dashboard'));

    $response->assertDontSee('Gerenciar Serviços');
});

test('the real sidebar config routes Serviços to the public services page, not a placeholder', function () {
    $servicosItem = collect(config('sidebar'))
        ->flatMap(fn ($section) => $section['items'] ?? [$section])
        ->firstWhere('label', 'Serviços');

    expect($servicosItem['route'])->toBe('services.index');
});
