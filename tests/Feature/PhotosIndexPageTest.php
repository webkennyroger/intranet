<?php

use App\Livewire\Site\Photos\PhotosIndex;
use App\Models\PhotoAlbum;
use App\Models\User;
use App\Models\Video;
use Livewire\Livewire;

test('a published photo album is listed on the public media page', function () {
    $user = User::factory()->create();
    $album = PhotoAlbum::factory()->create(['title' => 'Álbum Publicado', 'is_published' => true]);
    PhotoAlbum::factory()->create(['title' => 'Álbum Rascunho', 'is_published' => false]);

    $response = $this->actingAs($user)->get(route('photos.index'));

    $response->assertSee('Álbum Publicado')->assertDontSee('Álbum Rascunho');
});

test('a guest is redirected to login from the media index page', function () {
    $this->get(route('photos.index'))->assertRedirect(route('login'));
});

test('liking a photo album from the index page updates the like count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $album = PhotoAlbum::factory()->create(['is_published' => true]);

    Livewire::test(PhotosIndex::class)->call('toggleAlbumLike', $album->id);

    expect($album->refresh()->isLikedBy($user))->toBeTrue();
});

test('toggling the album like twice removes it again', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $album = PhotoAlbum::factory()->create(['is_published' => true]);

    Livewire::test(PhotosIndex::class)
        ->call('toggleAlbumLike', $album->id)
        ->call('toggleAlbumLike', $album->id);

    expect($album->refresh()->isLikedBy($user))->toBeFalse();
});

test('a published video is listed on the public media page', function () {
    $user = User::factory()->create();
    Video::factory()->create(['title' => 'Vídeo Publicado', 'is_published' => true]);
    Video::factory()->create(['title' => 'Vídeo Rascunho', 'is_published' => false]);

    $response = $this->actingAs($user)->get(route('photos.index'));

    $response->assertSee('Vídeo Publicado')->assertDontSee('Vídeo Rascunho');
});

test('liking a video from the index page updates the like count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $video = Video::factory()->create(['is_published' => true]);

    Livewire::test(PhotosIndex::class)->call('toggleVideoLike', $video->id);

    expect($video->refresh()->isLikedBy($user))->toBeTrue();
});

test('toggling the video like twice removes it again', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $video = Video::factory()->create(['is_published' => true]);

    Livewire::test(PhotosIndex::class)
        ->call('toggleVideoLike', $video->id)
        ->call('toggleVideoLike', $video->id);

    expect($video->refresh()->isLikedBy($user))->toBeFalse();
});

test('liking an unpublished album or video via the media page is rejected', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $draftAlbum = PhotoAlbum::factory()->create(['is_published' => false]);
    $draftVideo = Video::factory()->create(['is_published' => false]);

    Livewire::test(PhotosIndex::class)->call('toggleAlbumLike', $draftAlbum->id)->assertStatus(404);
    Livewire::test(PhotosIndex::class)->call('toggleVideoLike', $draftVideo->id)->assertStatus(404);

    expect($draftAlbum->isLikedBy($user))->toBeFalse()
        ->and($draftVideo->isLikedBy($user))->toBeFalse();
});

test('opening a video from the index page increments its views count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $video = Video::factory()->create(['is_published' => true, 'views_count' => 0]);

    Livewire::test(PhotosIndex::class)->call('incrementVideoViews', $video->id);

    expect($video->refresh()->views_count)->toBe(1);
});

test('the old /videos url redirects to the media page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/videos')->assertRedirect('/fotos');
});
