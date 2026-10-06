<?php

use App\Models\User;
use Database\Seeders\AclSeeder;

test('the site sidebar does not show admin management shortcuts, even for an Administrador', function () {
    $this->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $response = $this->actingAs($admin)->get(route('inicio'));

    $response->assertDontSee('Gerenciar Serviços');
});

test('the real sidebar config has no ADMINISTRAÇÃO group', function () {
    $groupLabels = collect(config('sidebar'))->pluck('group')->filter();

    expect($groupLabels)->not->toContain('ADMINISTRAÇÃO');
});

test('the real sidebar config routes Serviços to the public services page, not a placeholder', function () {
    $servicosItem = collect(config('sidebar'))
        ->flatMap(fn ($section) => $section['items'] ?? [$section])
        ->firstWhere('label', 'Serviços');

    expect($servicosItem['route'])->toBe('services.index');
});
