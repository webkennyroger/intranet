<?php

use App\Livewire\Admin\Photos\PhotoAlbumForm;
use App\Livewire\Admin\Photos\PhotoAlbumIndex;
use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function actingAsRoleForPhotos(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can view the photo albums management page', function () {
    actingAsRoleForPhotos('Administrador');

    $this->get(route('admin.photos.index'))->assertStatus(200);
});

test('editing an album loads its existing photos into the form', function () {
    // "Editar" é clicado de dentro do island da tabela — o modal/form
    // precisa estar dentro do mesmo island (ver photo-album-index.blade.php)
    // pra receber o álbum certo; fora dele, ficava sempre vazio/com os
    // dados do carregamento inicial da página.
    actingAsRoleForPhotos('Administrador');
    $album = PhotoAlbum::factory()->create(['title' => 'Álbum Original']);
    Photo::factory()->for($album, 'album')->create(['caption' => 'Legenda Exclusiva Um']);

    Livewire::test(PhotoAlbumIndex::class)
        ->call('edit', $album->id)
        ->assertSee('Legenda Exclusiva Um');
});

test('switching from editing one album to another shows the new album\'s photos, not the previous one\'s', function () {
    actingAsRoleForPhotos('Administrador');
    $first = PhotoAlbum::factory()->create(['title' => 'Primeiro Álbum']);
    Photo::factory()->for($first, 'album')->create(['caption' => 'Legenda do Primeiro']);
    $second = PhotoAlbum::factory()->create(['title' => 'Segundo Álbum']);
    Photo::factory()->for($second, 'album')->create(['caption' => 'Legenda do Segundo']);

    Livewire::test(PhotoAlbumIndex::class)
        ->call('edit', $first->id)
        ->assertSee('Legenda do Primeiro')
        ->assertDontSee('Legenda do Segundo')
        ->call('edit', $second->id)
        ->assertSee('Legenda do Segundo')
        ->assertDontSee('Legenda do Primeiro');
});

test('viewing an album opens the gallery modal with all of its photos', function () {
    actingAsRoleForPhotos('Administrador');
    $album = PhotoAlbum::factory()->create(['title' => 'Álbum com Fotos']);
    Photo::factory()->for($album, 'album')->create(['caption' => 'Foto da Galeria']);

    Livewire::test(PhotoAlbumIndex::class)
        ->call('view', $album->id)
        ->assertSee('Foto da Galeria');
});

test('creating an album with photos stores each photo and sets the first as the cover', function () {
    Storage::fake('public');
    actingAsRoleForPhotos('Administrador');

    Livewire::test(PhotoAlbumForm::class)
        ->set('title', 'Confraternização 2026')
        ->set('newPhotos', [
            UploadedFile::fake()->image('foto1.jpg'),
            UploadedFile::fake()->image('foto2.jpg'),
        ])
        ->call('save')
        ->assertHasNoErrors();

    $album = PhotoAlbum::where('title', 'Confraternização 2026')->first();
    expect($album)->not->toBeNull()
        ->and($album->photos)->toHaveCount(2)
        ->and($album->cover_image_path)->not->toBeNull();
});

test('removing a pending photo before saving excludes it from the album', function () {
    Storage::fake('public');
    actingAsRoleForPhotos('Administrador');

    Livewire::test(PhotoAlbumForm::class)
        ->set('title', 'Removendo Antes de Salvar')
        ->set('newPhotos', [
            UploadedFile::fake()->image('foto1.jpg'),
            UploadedFile::fake()->image('foto2.jpg'),
            UploadedFile::fake()->image('foto3.jpg'),
        ])
        ->call('removeNewPhoto', 1)
        ->call('save')
        ->assertHasNoErrors();

    $album = PhotoAlbum::where('title', 'Removendo Antes de Salvar')->first();
    expect($album->photos)->toHaveCount(2);
});

test('creating an album without a title is rejected', function () {
    actingAsRoleForPhotos('Administrador');

    Livewire::test(PhotoAlbumForm::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('removing a photo from an album deletes it', function () {
    actingAsRoleForPhotos('Administrador');
    $album = PhotoAlbum::factory()->create();
    $photo = Photo::factory()->for($album, 'album')->create();

    Livewire::test(PhotoAlbumForm::class, ['album' => $album])
        ->call('removePhoto', $photo->id)
        ->assertHasNoErrors();

    expect(Photo::find($photo->id))->toBeNull();
});

test('removing selected photos deletes all of them at once', function () {
    actingAsRoleForPhotos('Administrador');
    $album = PhotoAlbum::factory()->create();
    $photo1 = Photo::factory()->for($album, 'album')->create();
    $photo2 = Photo::factory()->for($album, 'album')->create();
    $keptPhoto = Photo::factory()->for($album, 'album')->create();

    Livewire::test(PhotoAlbumForm::class, ['album' => $album])
        ->set('selectedPhotoIds', [$photo1->id, $photo2->id])
        ->call('removeSelectedPhotos')
        ->assertHasNoErrors()
        ->assertSet('selectedPhotoIds', []);

    expect(Photo::find($photo1->id))->toBeNull()
        ->and(Photo::find($photo2->id))->toBeNull()
        ->and(Photo::find($keptPhoto->id))->not->toBeNull();
});

test('deleting the cover photo (alone or in bulk) reassigns the cover to a remaining photo', function () {
    actingAsRoleForPhotos('Administrador');
    $album = PhotoAlbum::factory()->create();
    $coverPhoto = Photo::factory()->for($album, 'album')->create(['image_path' => 'photos/cover.jpg']);
    $otherPhoto = Photo::factory()->for($album, 'album')->create(['image_path' => 'photos/other.jpg']);
    $album->update(['cover_image_path' => 'photos/cover.jpg']);

    Livewire::test(PhotoAlbumForm::class, ['album' => $album])
        ->set('selectedPhotoIds', [$coverPhoto->id])
        ->call('removeSelectedPhotos');

    expect($album->refresh()->cover_image_path)->toBe('photos/other.jpg');
});

test('a Defensor cannot remove selected photos from an album', function () {
    actingAsRoleForPhotos('Defensor');
    $album = PhotoAlbum::factory()->create();
    $photo = Photo::factory()->for($album, 'album')->create();

    Livewire::test(PhotoAlbumForm::class, ['album' => $album])
        ->set('selectedPhotoIds', [$photo->id])
        ->call('removeSelectedPhotos');

    expect(Photo::find($photo->id))->not->toBeNull();
});

test('toggling select all photos selects then deselects every photo in the album', function () {
    actingAsRoleForPhotos('Administrador');
    $album = PhotoAlbum::factory()->create();
    $photo1 = Photo::factory()->for($album, 'album')->create();
    $photo2 = Photo::factory()->for($album, 'album')->create();

    $component = Livewire::test(PhotoAlbumForm::class, ['album' => $album])
        ->call('toggleSelectAllPhotos');

    expect($component->get('selectedPhotoIds'))->toEqualCanonicalizing([$photo1->id, $photo2->id]);

    $component->call('toggleSelectAllPhotos');
    expect($component->get('selectedPhotoIds'))->toBe([]);
});

test('a Defensor cannot delete an album', function () {
    actingAsRoleForPhotos('Defensor');
    $album = PhotoAlbum::factory()->create();

    Livewire::test(PhotoAlbumIndex::class)->call('delete', $album->id);

    expect(PhotoAlbum::find($album->id))->not->toBeNull();
});

test('an Administrador can delete an album', function () {
    actingAsRoleForPhotos('Administrador');
    $album = PhotoAlbum::factory()->create();

    Livewire::test(PhotoAlbumIndex::class)->call('delete', $album->id);

    expect(PhotoAlbum::find($album->id))->toBeNull();
});

test('bulkPublish publishes only the selected draft albums and leaves others untouched', function () {
    actingAsRoleForPhotos('Administrador');
    $draft1 = PhotoAlbum::factory()->create(['is_published' => false]);
    $draft2 = PhotoAlbum::factory()->create(['is_published' => false]);
    $notSelected = PhotoAlbum::factory()->create(['is_published' => false]);

    Livewire::test(PhotoAlbumIndex::class)
        ->set('selected', [$draft1->id, $draft2->id])
        ->call('bulkPublish');

    expect($draft1->refresh()->is_published)->toBeTrue()
        ->and($draft2->refresh()->is_published)->toBeTrue()
        ->and($notSelected->refresh()->is_published)->toBeFalse();
});

test('bulkUnpublish unpublishes only the selected published albums', function () {
    actingAsRoleForPhotos('Administrador');
    $published = PhotoAlbum::factory()->create(['is_published' => true]);
    $notSelected = PhotoAlbum::factory()->create(['is_published' => true]);

    Livewire::test(PhotoAlbumIndex::class)
        ->set('selected', [$published->id])
        ->call('bulkUnpublish');

    expect($published->refresh()->is_published)->toBeFalse()
        ->and($notSelected->refresh()->is_published)->toBeTrue();
});

test('a Defensor cannot bulk publish or bulk unpublish albums', function () {
    actingAsRoleForPhotos('Defensor');
    $draft = PhotoAlbum::factory()->create(['is_published' => false]);
    $published = PhotoAlbum::factory()->create(['is_published' => true]);

    Livewire::test(PhotoAlbumIndex::class)->set('selected', [$draft->id])->call('bulkPublish');
    Livewire::test(PhotoAlbumIndex::class)->set('selected', [$published->id])->call('bulkUnpublish');

    expect($draft->refresh()->is_published)->toBeFalse()
        ->and($published->refresh()->is_published)->toBeTrue();
});

test('bulkDelete removes every selected album and clears the selection', function () {
    actingAsRoleForPhotos('Administrador');
    $album1 = PhotoAlbum::factory()->create();
    $album2 = PhotoAlbum::factory()->create();

    $component = Livewire::test(PhotoAlbumIndex::class)
        ->set('selected', [$album1->id, $album2->id])
        ->call('bulkDelete');

    expect(PhotoAlbum::find($album1->id))->toBeNull()
        ->and(PhotoAlbum::find($album2->id))->toBeNull();
    $component->assertSet('selected', []);
});

test('a Defensor cannot bulk delete albums', function () {
    actingAsRoleForPhotos('Defensor');
    $album = PhotoAlbum::factory()->create();

    Livewire::test(PhotoAlbumIndex::class)->set('selected', [$album->id])->call('bulkDelete');

    expect(PhotoAlbum::find($album->id))->not->toBeNull();
});

test('toggleSelectAllOnPage selects then deselects every album on the current page', function () {
    actingAsRoleForPhotos('Administrador');
    PhotoAlbum::factory()->count(3)->create();

    $component = Livewire::test(PhotoAlbumIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(3);

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});

test('searching filters the photo albums table by title', function () {
    actingAsRoleForPhotos('Administrador');
    PhotoAlbum::factory()->create(['title' => 'Formatura 2026']);
    PhotoAlbum::factory()->create(['title' => 'Outro Assunto']);

    Livewire::test(PhotoAlbumIndex::class)
        ->set('search', 'Formatura')
        ->assertSee('Formatura 2026')
        ->assertDontSee('Outro Assunto');
});
