<?php

use App\Livewire\Site\Council\CouncilIndex;
use App\Livewire\Site\Photos\PhotoAlbumShow;
use App\Models\CouncilMember;
use App\Models\Document;
use App\Models\Event;
use App\Models\PhotoAlbum;
use App\Models\Training;
use App\Models\User;
use App\Models\Video;
use Livewire\Livewire;

test('the documents page only shows published documents', function () {
    $user = User::factory()->create();
    $published = Document::factory()->create(['title' => 'Documento Público', 'is_published' => true]);
    $draft = Document::factory()->create(['title' => 'Documento Rascunho', 'is_published' => false]);

    $this->actingAs($user)->get(route('documents.index'))
        ->assertOk()
        ->assertSee('Documento Público')
        ->assertDontSee('Documento Rascunho');
});

test('the council page only loads active members, even though the members section is currently hidden', function () {
    $user = User::factory()->create();
    CouncilMember::factory()->create(['name' => 'Membro Ativo', 'is_active' => true]);
    CouncilMember::factory()->create(['name' => 'Membro Inativo', 'is_active' => false]);

    $this->actingAs($user)->get(route('council.index'))->assertOk();

    $component = Livewire::test(CouncilIndex::class);
    expect($component->viewData('members')->pluck('name')->all())->toBe(['Membro Ativo']);
});

test('the photos page only shows published albums', function () {
    $user = User::factory()->create();
    PhotoAlbum::factory()->create(['title' => 'Álbum Público', 'is_published' => true]);
    PhotoAlbum::factory()->create(['title' => 'Álbum Rascunho', 'is_published' => false]);

    $this->actingAs($user)->get(route('photos.index'))
        ->assertOk()
        ->assertSee('Álbum Público')
        ->assertDontSee('Álbum Rascunho');
});

test('an unpublished album returns 404 on its show page', function () {
    $user = User::factory()->create();
    $album = PhotoAlbum::factory()->create(['is_published' => false]);

    $this->actingAs($user)->get(route('photos.show', $album))->assertNotFound();
});

test('visiting an album show page increments its views count', function () {
    $user = User::factory()->create();
    $album = PhotoAlbum::factory()->create(['is_published' => true, 'views_count' => 0]);

    $this->actingAs($user)->get(route('photos.show', $album));

    expect($album->refresh()->views_count)->toBe(1);
});

test('liking an album from its show page updates the like count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $album = PhotoAlbum::factory()->create(['is_published' => true]);

    Livewire::test(PhotoAlbumShow::class, ['album' => $album])->call('toggleLike');

    expect($album->refresh()->isLikedBy($user))->toBeTrue();
});

test('the media page only shows published videos', function () {
    $user = User::factory()->create();
    Video::factory()->create(['title' => 'Vídeo Público', 'is_published' => true]);
    Video::factory()->create(['title' => 'Vídeo Rascunho', 'is_published' => false]);

    $this->actingAs($user)->get(route('photos.index'))
        ->assertOk()
        ->assertSee('Vídeo Público')
        ->assertDontSee('Vídeo Rascunho');
});

test('the events page only shows published events', function () {
    $user = User::factory()->create();
    Event::factory()->create(['title' => 'Evento Público', 'is_published' => true, 'starts_at' => now()->addDay()]);
    Event::factory()->create(['title' => 'Evento Rascunho', 'is_published' => false, 'starts_at' => now()->addDay()]);

    $this->actingAs($user)->get(route('events.index'))
        ->assertOk()
        ->assertSee('Evento Público')
        ->assertDontSee('Evento Rascunho');
});

test('the trainings page only shows published trainings', function () {
    $user = User::factory()->create();
    Training::factory()->create(['title' => 'Curso Público', 'is_published' => true]);
    Training::factory()->create(['title' => 'Curso Rascunho', 'is_published' => false]);

    $this->actingAs($user)->get(route('trainings.index'))
        ->assertOk()
        ->assertSee('Curso Público')
        ->assertDontSee('Curso Rascunho');
});

test('a guest is redirected to login from any of the new site pages', function () {
    $this->get(route('documents.index'))->assertRedirect(route('login'));
});
