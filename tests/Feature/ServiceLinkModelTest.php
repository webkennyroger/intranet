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

test('services.view is in the permission inventory', function () {
    expect(PermissionManager::inventory())->toContain('services.view');
});

test('Administrador role has full services access after seeding', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Administrador')->first();

    expect($role->hasPermissionTo('services.view'))->toBeTrue()
        ->and($role->hasPermissionTo('services.create'))->toBeTrue()
        ->and($role->hasPermissionTo('services.update'))->toBeTrue()
        ->and($role->hasPermissionTo('services.delete'))->toBeTrue();
});

test('Defensor role does not have services.view', function () {
    $this->seed(AclSeeder::class);

    expect(Role::where('name', 'Defensor')->first()->hasPermissionTo('services.view'))->toBeFalse();
});
