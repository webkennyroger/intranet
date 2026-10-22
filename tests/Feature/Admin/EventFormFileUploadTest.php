<?php

use App\Livewire\Admin\Events\EventForm;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('cover image and file persist and reload on edit', function () {
    Storage::fake('public');
    $this->seed(AclSeeder::class);
    $category = EventCategory::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('Administrador');
    $this->actingAs($user);

    Livewire::test(EventForm::class)
        ->set('title', 'Evento Com Anexos')
        ->set('startsAt', '2026-10-20T09:00')
        ->set('categoryId', $category->id)
        ->set('coverImage', UploadedFile::fake()->image('capa.jpg', 800, 400))
        ->set('file', UploadedFile::fake()->create('programacao.pdf', 500))
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::where('title', 'Evento Com Anexos')->first();

    expect($event->cover_image)->not->toBeNull()
        ->and($event->file_path)->not->toBeNull();
    Storage::disk('public')->assertExists($event->cover_image);
    Storage::disk('public')->assertExists($event->file_path);

    // Reabre o form de edição pra esse mesmo evento, como a view faz.
    $reopened = Livewire::test(EventForm::class, ['event' => $event]);

    $reopened->assertSet('existingCoverImage', $event->cover_image)
        ->assertSet('existingFilePath', $event->file_path);
});
