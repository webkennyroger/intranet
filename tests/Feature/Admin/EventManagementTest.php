<?php

use App\Livewire\Admin\Events\EventForm;
use App\Livewire\Admin\Events\EventIndex;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForEvents(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can view the events management page', function () {
    actingAsRoleForEvents('Administrador');

    $this->get(route('admin.events.index'))->assertStatus(200);
});

test('creating an event saves it with the given dates', function () {
    actingAsRoleForEvents('Administrador');

    Livewire::test(EventForm::class)
        ->set('title', 'Semana da Defensoria')
        ->set('location', 'Auditório Central')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('endsAt', '2026-11-10T12:00')
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::where('title', 'Semana da Defensoria')->first();
    expect($event)->not->toBeNull()
        ->and($event->starts_at->format('Y-m-d H:i'))->toBe('2026-11-10 09:00');
});

test('creating an event with an end date before the start date is rejected', function () {
    actingAsRoleForEvents('Administrador');

    Livewire::test(EventForm::class)
        ->set('title', 'Datas Inválidas')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('endsAt', '2026-11-10T08:00')
        ->call('save')
        ->assertHasErrors(['endsAt']);

    expect(Event::where('title', 'Datas Inválidas')->exists())->toBeFalse();
});

test('a Defensor cannot publish an event', function () {
    actingAsRoleForEvents('Defensor');
    $event = Event::factory()->create(['is_published' => false]);

    Livewire::test(EventIndex::class)->call('togglePublish', $event->id);

    expect($event->refresh()->is_published)->toBeFalse();
});

test('an Administrador can delete an event', function () {
    actingAsRoleForEvents('Administrador');
    $event = Event::factory()->create();

    Livewire::test(EventIndex::class)->call('delete', $event->id);

    expect(Event::find($event->id))->toBeNull();
});

test('bulkPublish publishes only the selected draft events and leaves others untouched', function () {
    actingAsRoleForEvents('Administrador');
    $draft1 = Event::factory()->create(['is_published' => false]);
    $draft2 = Event::factory()->create(['is_published' => false]);
    $notSelected = Event::factory()->create(['is_published' => false]);

    Livewire::test(EventIndex::class)
        ->set('selected', [$draft1->id, $draft2->id])
        ->call('bulkPublish');

    expect($draft1->refresh()->is_published)->toBeTrue()
        ->and($draft2->refresh()->is_published)->toBeTrue()
        ->and($notSelected->refresh()->is_published)->toBeFalse();
});

test('bulkUnpublish unpublishes only the selected published events', function () {
    actingAsRoleForEvents('Administrador');
    $published = Event::factory()->create(['is_published' => true]);
    $notSelected = Event::factory()->create(['is_published' => true]);

    Livewire::test(EventIndex::class)
        ->set('selected', [$published->id])
        ->call('bulkUnpublish');

    expect($published->refresh()->is_published)->toBeFalse()
        ->and($notSelected->refresh()->is_published)->toBeTrue();
});

test('a Defensor cannot bulk publish or bulk unpublish events', function () {
    actingAsRoleForEvents('Defensor');
    $draft = Event::factory()->create(['is_published' => false]);
    $published = Event::factory()->create(['is_published' => true]);

    Livewire::test(EventIndex::class)->set('selected', [$draft->id])->call('bulkPublish');
    Livewire::test(EventIndex::class)->set('selected', [$published->id])->call('bulkUnpublish');

    expect($draft->refresh()->is_published)->toBeFalse()
        ->and($published->refresh()->is_published)->toBeTrue();
});

test('bulkDelete removes every selected event and clears the selection', function () {
    actingAsRoleForEvents('Administrador');
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();

    $component = Livewire::test(EventIndex::class)
        ->set('selected', [$event1->id, $event2->id])
        ->call('bulkDelete');

    expect(Event::find($event1->id))->toBeNull()
        ->and(Event::find($event2->id))->toBeNull();
    $component->assertSet('selected', []);
});

test('a Defensor cannot bulk delete events', function () {
    actingAsRoleForEvents('Defensor');
    $event = Event::factory()->create();

    Livewire::test(EventIndex::class)->set('selected', [$event->id])->call('bulkDelete');

    expect(Event::find($event->id))->not->toBeNull();
});

test('toggleSelectAllOnPage selects then deselects every event on the current page', function () {
    actingAsRoleForEvents('Administrador');
    Event::factory()->count(3)->create();

    $component = Livewire::test(EventIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(3);

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});
