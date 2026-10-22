<?php

use App\Livewire\Admin\Events\EventForm;
use App\Livewire\Admin\Events\EventIndex;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

test('upcomingEvents still lists an event scheduled earlier today, even after its start time has passed', function () {
    actingAsRoleForEvents('Administrador');
    $today = Event::factory()->create(['title' => 'Evento De Hoje Mais Cedo', 'starts_at' => today(), 'ends_at' => today()->addHour()]);
    $yesterday = Event::factory()->create(['title' => 'Evento De Ontem', 'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()]);

    $ids = Livewire::test(EventIndex::class)->instance()->upcomingEvents()->pluck('id');

    expect($ids)->toContain($today->id)
        ->and($ids)->not->toContain($yesterday->id);
});

test('creating an event saves it with the given dates', function () {
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create();

    Livewire::test(EventForm::class)
        ->set('title', 'Semana da Defensoria')
        ->set('location', 'Auditório Central')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('endsAt', '2026-11-10T12:00')
        ->set('categoryId', $category->id)
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

test('creating an event without a category is rejected', function () {
    actingAsRoleForEvents('Administrador');

    Livewire::test(EventForm::class)
        ->set('title', 'Reunião de Equipe')
        ->set('startsAt', '2026-11-10T09:00')
        ->call('save')
        ->assertHasErrors(['categoryId']);

    expect(Event::where('title', 'Reunião de Equipe')->exists())->toBeFalse();
});

test('creating an event with a category links it, and its color comes from the category', function () {
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create(['color' => '#9333ea']);

    Livewire::test(EventForm::class)
        ->set('title', 'Audiência Pública')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('categoryId', $category->id)
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::where('title', 'Audiência Pública')->first();
    expect($event->event_category_id)->toBe($category->id)
        ->and($event->colorHex())->toBe('#9333ea');
});

test('editing an event to change its category updates its effective color', function () {
    actingAsRoleForEvents('Administrador');
    $oldCategory = EventCategory::factory()->create(['color' => '#111111']);
    $newCategory = EventCategory::factory()->create(['color' => '#222222']);
    $event = Event::factory()->create(['event_category_id' => $oldCategory->id, 'color' => null]);

    Livewire::test(EventForm::class, ['event' => $event])
        ->set('categoryId', $newCategory->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($event->refresh()->event_category_id)->toBe($newCategory->id)
        ->and($event->colorHex())->toBe('#222222');
});

test('an invalid category id is rejected', function () {
    actingAsRoleForEvents('Administrador');

    Livewire::test(EventForm::class)
        ->set('title', 'Categoria Inválida')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('categoryId', 999999)
        ->call('save')
        ->assertHasErrors(['categoryId']);

    expect(Event::where('title', 'Categoria Inválida')->exists())->toBeFalse();
});

test('a Defensor cannot edit an event', function () {
    actingAsRoleForEvents('Defensor');
    $event = Event::factory()->create(['title' => 'Original']);

    Livewire::test(EventForm::class, ['event' => $event])
        ->set('title', 'Alterado')
        ->call('save')
        ->assertStatus(403);

    expect($event->refresh()->title)->toBe('Original');
});

test('an Administrador can delete an event', function () {
    actingAsRoleForEvents('Administrador');
    $event = Event::factory()->create();

    Livewire::test(EventIndex::class)->call('delete', $event->id);

    expect(Event::find($event->id))->toBeNull();
});

test('a Defensor cannot delete an event', function () {
    actingAsRoleForEvents('Defensor');
    $event = Event::factory()->create();

    Livewire::test(EventIndex::class)->call('delete', $event->id)->assertStatus(403);

    expect(Event::find($event->id))->not->toBeNull();
});

test('rescheduleEvent moves the event to the new date, keeping its time and duration', function () {
    actingAsRoleForEvents('Administrador');
    $event = Event::factory()->create([
        'starts_at' => '2026-10-04 14:00:00',
        'ends_at' => '2026-10-04 16:00:00',
    ]);

    Livewire::test(EventIndex::class)->call('rescheduleEvent', $event->id, '2026-10-20');

    expect($event->refresh()->starts_at->format('Y-m-d H:i:s'))->toBe('2026-10-20 14:00:00')
        ->and($event->ends_at->format('Y-m-d H:i:s'))->toBe('2026-10-20 16:00:00');
});

test('a Defensor cannot reschedule an event', function () {
    actingAsRoleForEvents('Defensor');
    $event = Event::factory()->create(['starts_at' => '2026-10-04 14:00:00']);

    Livewire::test(EventIndex::class)->call('rescheduleEvent', $event->id, '2026-10-20')->assertStatus(403);

    expect($event->refresh()->starts_at->format('Y-m-d'))->toBe('2026-10-04');
});

test('quickCreate creates an event linked to the dropped category, with its name and date, and opens it for editing', function () {
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create(['color' => '#0ab39c']);

    $component = Livewire::test(EventIndex::class)->call('quickCreate', $category->id, 'Reunião', '2026-11-10');

    $event = Event::where('title', 'Reunião')->first();
    expect($event)->not->toBeNull()
        ->and($event->event_category_id)->toBe($category->id)
        ->and($event->colorHex())->toBe('#0ab39c')
        ->and($event->starts_at->format('Y-m-d'))->toBe('2026-11-10')
        ->and($event->is_published)->toBeTrue();
    $component->assertSet('editingEventId', $event->id);
});

test('a Defensor cannot quickCreate an event', function () {
    actingAsRoleForEvents('Defensor');
    $category = EventCategory::factory()->create();

    Livewire::test(EventIndex::class)
        ->call('quickCreate', $category->id, 'Reunião', '2026-11-10')
        ->assertStatus(403);

    expect(Event::where('title', 'Reunião')->exists())->toBeFalse();
});

test('createOnDate opens the form prefilled with the given date without creating anything', function () {
    actingAsRoleForEvents('Administrador');

    $component = Livewire::test(EventIndex::class)->call('createOnDate', '2026-11-15');

    $component->assertSet('prefillStartsAt', '2026-11-15')
        ->assertSet('editingEventId', null);
    expect(Event::count())->toBe(0);
});

test('the prefilled date from createOnDate flows into the event form\'s startsAt field', function () {
    actingAsRoleForEvents('Administrador');

    Livewire::test(EventForm::class, ['prefillDate' => '2026-11-15'])
        ->assertSet('startsAt', '2026-11-15T09:00');
});

test('creating an event saves its link and attached file of any type', function () {
    Storage::fake('public');
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create();

    Livewire::test(EventForm::class)
        ->set('title', 'Semana Jurídica')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('categoryId', $category->id)
        ->set('link', 'https://meet.exemplo.com/semana-juridica')
        ->set('file', UploadedFile::fake()->create('programacao.zip', 100))
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::where('title', 'Semana Jurídica')->first();
    expect($event->link)->toBe('https://meet.exemplo.com/semana-juridica')
        ->and($event->file_path)->not->toBeNull()
        ->and($event->fileIcon())->toBe('file-zipper');
    Storage::disk('public')->assertExists($event->file_path);
});

test('an event attachment with a disallowed file type (e.g. html or svg) is rejected', function () {
    Storage::fake('public');
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create();

    Livewire::test(EventForm::class)
        ->set('title', 'Semana Jurídica')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('categoryId', $category->id)
        ->set('file', UploadedFile::fake()->create('ataque.html', 10, 'text/html'))
        ->call('save')
        ->assertHasErrors(['file']);

    expect(Event::where('title', 'Semana Jurídica')->exists())->toBeFalse();
});

test('picking an existing file path that was never offered by the reuse list is forbidden', function () {
    Storage::fake('public');
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create();

    Livewire::test(EventForm::class)
        ->set('title', 'Tentativa De Burlar A Lista')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('categoryId', $category->id)
        ->call('pickExistingFile', '../../.env')
        ->call('save')
        ->assertForbidden();

    expect(Event::where('title', 'Tentativa De Burlar A Lista')->exists())->toBeFalse();
});

test('creating an event with an invalid link is rejected', function () {
    actingAsRoleForEvents('Administrador');

    Livewire::test(EventForm::class)
        ->set('title', 'Link Inválido')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('link', 'nao-e-uma-url')
        ->call('save')
        ->assertHasErrors(['link']);

    expect(Event::where('title', 'Link Inválido')->exists())->toBeFalse();
});

test('removeFile clears the attached file when editing an event', function () {
    Storage::fake('public');
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create();
    $event = Event::factory()->create(['file_path' => 'events/programacao.pdf', 'event_category_id' => $category->id]);

    Livewire::test(EventForm::class, ['event' => $event])
        ->call('removeFile')
        ->call('save')
        ->assertHasNoErrors();

    expect($event->refresh()->file_path)->toBeNull();
});

test('pickExistingFile reuses a path already in the media library instead of uploading again', function () {
    Storage::fake('public');
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create();
    $document = Document::factory()->create(['file_path' => 'documents/edital.pdf']);
    Storage::disk('public')->put($document->file_path, 'fake');

    Livewire::test(EventForm::class)
        ->set('title', 'Evento Com Arquivo Reaproveitado')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('categoryId', $category->id)
        ->call('pickExistingFile', $document->file_path)
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::where('title', 'Evento Com Arquivo Reaproveitado')->first();
    expect($event->file_path)->toBe($document->file_path);
});

test('pickExistingCoverImage reuses a path already in the media library instead of uploading again', function () {
    Storage::fake('public');
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create();
    $other = Event::factory()->create(['cover_image' => 'events/outro/capa.jpg']);
    Storage::disk('public')->put($other->cover_image, 'fake');

    Livewire::test(EventForm::class)
        ->set('title', 'Evento Com Capa Reaproveitada')
        ->set('startsAt', '2026-11-10T09:00')
        ->set('categoryId', $category->id)
        ->call('pickExistingCoverImage', $other->cover_image)
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::where('title', 'Evento Com Capa Reaproveitada')->first();
    expect($event->cover_image)->toBe($other->cover_image);
});

test('editing an event keeps its existing cover image and file when nothing is changed', function () {
    Storage::fake('public');
    actingAsRoleForEvents('Administrador');
    $category = EventCategory::factory()->create();
    $event = Event::factory()->create([
        'event_category_id' => $category->id,
        'cover_image' => 'events/abc/capa.jpg',
        'file_path' => 'events/abc/programacao.pdf',
    ]);
    Storage::disk('public')->put($event->cover_image, 'fake');
    Storage::disk('public')->put($event->file_path, 'fake');

    Livewire::test(EventForm::class, ['event' => $event])
        ->set('title', 'Título Atualizado')
        ->call('save')
        ->assertHasNoErrors();

    $event->refresh();
    expect($event->title)->toBe('Título Atualizado')
        ->and($event->cover_image)->toBe('events/abc/capa.jpg')
        ->and($event->file_path)->toBe('events/abc/programacao.pdf');
});
