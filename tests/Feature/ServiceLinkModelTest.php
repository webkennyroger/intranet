<?php

use App\Models\ServiceLink;
use App\Services\PermissionManager;
use Database\Seeders\AclSeeder;
use Spatie\Permission\Models\Role;

test('a service link can be created with the expected fields', function () {
    $link = ServiceLink::create([
        'title' => 'Vade Meccum',
        'icon' => 'book-open',
        'url' => 'https://example.com/vade-meccum',
        'order' => 1,
        'is_active' => true,
    ]);

    expect($link->title)->toBe('Vade Meccum')
        ->and($link->is_active)->toBeTrue();
});

test('services.manage is in the permission inventory', function () {
    expect(PermissionManager::inventory())->toContain('services.manage');
});

test('Administrador role has services.manage after seeding', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Administrador')->first();

    expect($role->hasPermissionTo('services.manage'))->toBeTrue();
});

test('Gestor and Editor roles do not have services.manage', function () {
    $this->seed(AclSeeder::class);

    expect(Role::where('name', 'Gestor')->first()->hasPermissionTo('services.manage'))->toBeFalse()
        ->and(Role::where('name', 'Editor')->first()->hasPermissionTo('services.manage'))->toBeFalse();
});
