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
