<?php

use App\Livewire\Admin\Services\ServiceLinkForm;
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

test('a Defensor is forbidden from the services management page', function () {
    $this->seed(AclSeeder::class);
    $defensor = User::factory()->create();
    $defensor->assignRole('Defensor');

    $this->actingAs($defensor)
        ->get(route('admin.services.index'))
        ->assertStatus(403);
});

test('creating a service link with a valid url succeeds', function () {
    actingAsServicesAdmin();

    // ServiceLinkForm is a separate Livewire component embedded in
    // ServiceLinkIndex's modal via wire:key; exercise it directly here
    // to prove validation + persistence (ServiceLinkIndex itself has no
    // title/icon/url properties of its own to set).
    Livewire::test(ServiceLinkForm::class)
        ->set('title', 'Vade Meccum')
        ->set('icon', 'book-open')
        ->set('url', 'https://example.com/vade-meccum')
        ->call('save')
        ->assertHasNoErrors();

    expect(ServiceLink::where('title', 'Vade Meccum')->exists())->toBeTrue();
});

test('creating a service link with an invalid url is rejected', function () {
    actingAsServicesAdmin();

    Livewire::test(ServiceLinkForm::class)
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

    Livewire::test(ServiceLinkForm::class, ['link' => $link])
        ->set('title', 'Nome Novo')
        ->call('save')
        ->assertHasNoErrors();

    expect($link->refresh()->title)->toBe('Nome Novo');
});

test('reordering service links updates each order to match the given sequence', function () {
    actingAsServicesAdmin();
    $first = ServiceLink::factory()->create(['order' => 1]);
    $second = ServiceLink::factory()->create(['order' => 2]);
    $third = ServiceLink::factory()->create(['order' => 3]);

    Livewire::test(ServiceLinkIndex::class)->call('reorder', [$third->id, $first->id, $second->id]);

    expect($third->refresh()->order)->toBe(0)
        ->and($first->refresh()->order)->toBe(1)
        ->and($second->refresh()->order)->toBe(2);
});

test('reordering rejects an id list that does not match the existing service links', function () {
    actingAsServicesAdmin();
    $first = ServiceLink::factory()->create(['order' => 1]);
    $second = ServiceLink::factory()->create(['order' => 2]);

    Livewire::test(ServiceLinkIndex::class)->call('reorder', [$first->id, 999999]);

    expect($first->refresh()->order)->toBe(1)
        ->and($second->refresh()->order)->toBe(2);
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

test('bulkActivate activates only the selected inactive links and leaves others untouched', function () {
    actingAsServicesAdmin();
    $inactive1 = ServiceLink::factory()->create(['is_active' => false]);
    $inactive2 = ServiceLink::factory()->create(['is_active' => false]);
    $notSelected = ServiceLink::factory()->create(['is_active' => false]);

    Livewire::test(ServiceLinkIndex::class)
        ->set('selected', [$inactive1->id, $inactive2->id])
        ->call('bulkActivate');

    expect($inactive1->refresh()->is_active)->toBeTrue()
        ->and($inactive2->refresh()->is_active)->toBeTrue()
        ->and($notSelected->refresh()->is_active)->toBeFalse();
});

test('bulkDeactivate deactivates only the selected active links', function () {
    actingAsServicesAdmin();
    $active = ServiceLink::factory()->create(['is_active' => true]);
    $notSelected = ServiceLink::factory()->create(['is_active' => true]);

    Livewire::test(ServiceLinkIndex::class)
        ->set('selected', [$active->id])
        ->call('bulkDeactivate');

    expect($active->refresh()->is_active)->toBeFalse()
        ->and($notSelected->refresh()->is_active)->toBeTrue();
});

test('bulkDelete removes every selected service link and clears the selection', function () {
    actingAsServicesAdmin();
    $link1 = ServiceLink::factory()->create();
    $link2 = ServiceLink::factory()->create();

    $component = Livewire::test(ServiceLinkIndex::class)
        ->set('selected', [$link1->id, $link2->id])
        ->call('bulkDelete');

    expect(ServiceLink::find($link1->id))->toBeNull()
        ->and(ServiceLink::find($link2->id))->toBeNull();
    $component->assertSet('selected', []);
});

test('a Defensor cannot bulk activate, deactivate or delete service links', function () {
    $this->seed(AclSeeder::class);
    $defensor = User::factory()->create();
    $defensor->assignRole('Defensor');
    $this->actingAs($defensor);
    $link = ServiceLink::factory()->create(['is_active' => false]);

    Livewire::test(ServiceLinkIndex::class)->set('selected', [$link->id])->call('bulkActivate');
    expect($link->refresh()->is_active)->toBeFalse();

    Livewire::test(ServiceLinkIndex::class)->set('selected', [$link->id])->call('bulkDelete');
    expect(ServiceLink::find($link->id))->not->toBeNull();
});

test('toggleSelectAllOnPage selects then deselects every service link', function () {
    actingAsServicesAdmin();
    ServiceLink::factory()->count(3)->create();

    $component = Livewire::test(ServiceLinkIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(3);

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});
