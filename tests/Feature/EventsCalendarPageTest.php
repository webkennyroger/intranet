<?php

use App\Livewire\Site\Events\EventsIndex;
use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('the calendar shows a published event on its day in the current month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Event::factory()->create([
        'title' => 'Evento Deste Mês',
        'is_published' => true,
        'starts_at' => now()->startOfMonth()->addDays(5),
    ]);

    $this->get(route('events.index'))->assertOk()->assertSee('Evento Deste Mês');
});

test('nextMonth and previousMonth navigate the calendar correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(EventsIndex::class);
    $startMonth = $component->get('month');
    $startYear = $component->get('year');

    $component->call('nextMonth');
    $afterNext = now()->createFromDate($startYear, $startMonth, 1)->addMonth();
    $component->assertSet('month', $afterNext->month)->assertSet('year', $afterNext->year);

    $component->call('previousMonth');
    $component->assertSet('month', $startMonth)->assertSet('year', $startYear);
});

test('goToToday resets the calendar to the current month after navigating away', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(EventsIndex::class)
        ->call('nextMonth')
        ->call('nextMonth')
        ->call('goToToday');

    $component->assertSet('month', now()->month)->assertSet('year', now()->year);
});

test('the event detail modal includes location, description and the attached link', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Event::factory()->create([
        'title' => 'Audiência Pública',
        'location' => 'Auditório Central',
        'description' => 'Discussão aberta com a comunidade.',
        'link' => 'https://meet.exemplo.com/audiencia',
        'is_published' => true,
        'starts_at' => now()->addDays(3),
    ]);

    $this->get(route('events.index'))
        ->assertSee('Audiência Pública')
        ->assertSee('Auditório Central')
        ->assertSee('Discussão aberta com a comunidade.')
        ->assertSee('https://meet.exemplo.com/audiencia', false);
});

test('an unpublished event does not appear anywhere on the calendar page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Event::factory()->create([
        'title' => 'Evento Rascunho Oculto',
        'is_published' => false,
        'starts_at' => now()->addDays(2),
    ]);

    $this->get(route('events.index'))->assertDontSee('Evento Rascunho Oculto');
});
