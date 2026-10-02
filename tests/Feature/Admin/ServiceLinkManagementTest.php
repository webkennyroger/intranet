<?php

use App\Livewire\Admin\Services\ServiceLinkIndex;
use App\Models\ServiceLink;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsServicesAdmin(): User
{
    test()->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');
    test()->actingAs($admin);

    return $admin;
}

test('an Administrador can view the services management page', function () {
    actingAsServicesAdmin();

    $this->get(route('admin.services.index'))->assertStatus(200);
});

test('a Gestor is forbidden from the services management page', function () {
    $this->seed(AclSeeder::class);
    $gestor = User::factory()->create();
    $gestor->assignRole('Gestor');

    $this->actingAs($gestor)
        ->get(route('admin.services.index'))
        ->assertStatus(403);
});

test('an Editor is forbidden from the services management page', function () {
    $this->seed(AclSeeder::class);
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $this->actingAs($editor)
        ->get(route('admin.services.index'))
        ->assertStatus(403);
});

test('creating a service link with a valid url succeeds', function () {
    actingAsServicesAdmin();

    // ServiceLinkForm is a separate Livewire component embedded in
    // ServiceLinkIndex's modal via wire:key; exercise it directly here
    // to prove validation + persistence (ServiceLinkIndex itself has no
    // title/icon/url properties of its own to set).
    Livewire::test(\App\Livewire\Admin\Services\ServiceLinkForm::class)
        ->set('title', 'Vade Meccum')
        ->set('icon', 'book-open')
        ->set('url', 'https://example.com/vade-meccum')
        ->call('save')
        ->assertHasNoErrors();

    expect(ServiceLink::where('title', 'Vade Meccum')->exists())->toBeTrue();
});

test('creating a service link with an invalid url is rejected', function () {
    actingAsServicesAdmin();

    Livewire::test(\App\Livewire\Admin\Services\ServiceLinkForm::class)
        ->set('title', 'Link Quebrado')
        ->set('icon', 'link')
        ->set('url', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['url']);

    expect(ServiceLink::where('title', 'Link Quebrado')->exists())->toBeFalse();
});

test('editing an existing service link updates it', function () {
    actingAsServicesAdmin();
    $link = ServiceLink::factory()->create(['title' => 'Nome Antigo']);

    Livewire::test(\App\Livewire\Admin\Services\ServiceLinkForm::class, ['link' => $link])
        ->set('title', 'Nome Novo')
        ->call('save')
        ->assertHasNoErrors();

    expect($link->refresh()->title)->toBe('Nome Novo');
});

test('moving a service link up swaps its order with the previous one', function () {
    actingAsServicesAdmin();
    $first = ServiceLink::factory()->create(['order' => 1]);
    $second = ServiceLink::factory()->create(['order' => 2]);

    Livewire::test(ServiceLinkIndex::class)->call('moveUp', $second->id);

    expect($first->refresh()->order)->toBe(2)
        ->and($second->refresh()->order)->toBe(1);
});

test('moving the first service link up does nothing', function () {
    actingAsServicesAdmin();
    $first = ServiceLink::factory()->create(['order' => 1]);

    Livewire::test(ServiceLinkIndex::class)->call('moveUp', $first->id);

    expect($first->refresh()->order)->toBe(1);
});

test('moving the last service link down does nothing', function () {
    actingAsServicesAdmin();
    $last = ServiceLink::factory()->create(['order' => 1]);

    Livewire::test(ServiceLinkIndex::class)->call('moveDown', $last->id);

    expect($last->refresh()->order)->toBe(1);
});

test('deleting a service link removes it', function () {
    actingAsServicesAdmin();
    $link = ServiceLink::factory()->create();

    Livewire::test(ServiceLinkIndex::class)->call('delete', $link->id);

    expect(ServiceLink::find($link->id))->toBeNull();
});

test('toggling active status flips it', function () {
    actingAsServicesAdmin();
    $link = ServiceLink::factory()->create(['is_active' => true]);

    Livewire::test(ServiceLinkIndex::class)->call('toggleActive', $link->id);

    expect($link->refresh()->is_active)->toBeFalse();
});
