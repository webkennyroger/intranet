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
