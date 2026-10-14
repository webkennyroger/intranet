<?php

use App\Livewire\Admin\MediaLibraryIndex;
use App\Models\Article;
use App\Models\CouncilMember;
use App\Models\Document;
use App\Models\Media;
use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function actingAsRoleForMedia(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can view the media library page', function () {
    actingAsRoleForMedia('Administrador');

    $this->get(route('admin.media.index'))->assertStatus(200);
});

test('the media library aggregates real uploaded files from every source', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');

    $document = Document::factory()->create(['title' => 'Edital 2026']);
    Storage::disk('public')->put($document->file_path, 'fake');

    $album = PhotoAlbum::factory()->create();
    $photo = Photo::factory()->create(['photo_album_id' => $album->id, 'caption' => 'Foto da Confraternização']);
    Storage::disk('public')->put($photo->image_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)
        ->assertSee('Edital 2026')
        ->assertSee('Foto da Confraternização');
});

test('mural articles are not shown in the media library', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');

    $article = Article::factory()->create(['title' => 'Artigo Com Capa', 'featured_image' => 'articles/capa.jpg']);
    Storage::disk('public')->put($article->featured_image, 'fake');

    Livewire::test(MediaLibraryIndex::class)->assertDontSee('Artigo Com Capa');
});

test('council member photos are not shown in the media library', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');

    $member = CouncilMember::factory()->create(['name' => 'Defensor Exemplo', 'photo_path' => 'council/foto.jpg']);
    Storage::disk('public')->put($member->photo_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)->assertDontSee('Defensor Exemplo');
});

test('files that no longer exist on disk are skipped silently', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');

    Document::factory()->create(['title' => 'Documento Sem Arquivo No Disco']);

    Livewire::test(MediaLibraryIndex::class)
        ->assertDontSee('Documento Sem Arquivo No Disco');
});

test('filtering by source only shows media from that source', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');

    $album = PhotoAlbum::factory()->create();
    $photo = Photo::factory()->create(['photo_album_id' => $album->id, 'caption' => 'Foto Filtrada']);
    Storage::disk('public')->put($photo->image_path, 'fake');

    $document = Document::factory()->create(['title' => 'Documento Filtrado']);
    Storage::disk('public')->put($document->file_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)
        ->set('source', 'Documentos')
        ->assertSee('Documento Filtrado')
        ->assertDontSee('Foto Filtrada');
});

test('searching filters media by name', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');

    $one = Document::factory()->create(['title' => 'Comunicado Especial']);
    Storage::disk('public')->put($one->file_path, 'fake');

    $two = Document::factory()->create(['title' => 'Outro Assunto']);
    Storage::disk('public')->put($two->file_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)
        ->set('search', 'Comunicado')
        ->assertSee('Comunicado Especial')
        ->assertDontSee('Outro Assunto');
});

test('uploading without a source selected is rejected', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $file = UploadedFile::fake()->image('solta.jpg');

    Livewire::test(MediaLibraryIndex::class)
        ->set('newMediaFiles', [$file])
        ->assertHasErrors(['newMediaFiles']);

    expect(Media::count())->toBe(0);
});

test('uploading a standalone file with a source selected stores it tagged under that source', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $file = UploadedFile::fake()->image('solta.jpg');

    Livewire::test(MediaLibraryIndex::class)
        ->set('source', 'Documentos')
        ->set('newMediaFiles', [$file])
        ->assertHasNoErrors()
        ->assertSee('solta.jpg');

    $media = Media::first();
    expect($media)->not->toBeNull()
        ->and($media->name)->toBe('solta.jpg')
        ->and($media->source)->toBe('Documentos');
    Storage::disk('public')->assertExists($media->path);
});

test('uploading several files at once with a source selected stores one media record per file', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');

    Livewire::test(MediaLibraryIndex::class)
        ->set('source', 'Documentos')
        ->set('newMediaFiles', [
            UploadedFile::fake()->image('solta-1.jpg'),
            UploadedFile::fake()->image('solta-2.jpg'),
        ])
        ->assertHasNoErrors();

    expect(Media::count())->toBe(2)
        ->and(Media::where('name', 'solta-1.jpg')->exists())->toBeTrue()
        ->and(Media::where('name', 'solta-2.jpg')->exists())->toBeTrue();
});

test('deleting a standalone media file removes it from disk and the database', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $media = Media::factory()->create();
    Storage::disk('public')->put($media->path, 'fake');

    Livewire::test(MediaLibraryIndex::class)->call('deleteMedia', $media->id);

    expect(Media::find($media->id))->toBeNull();
    Storage::disk('public')->assertMissing($media->path);
});

test('deleting a photo removes it from disk and the database', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $album = PhotoAlbum::factory()->create();
    $photo = Photo::factory()->create(['photo_album_id' => $album->id]);
    Storage::disk('public')->put($photo->image_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)->call('deletePhoto', $photo->id);

    expect(Photo::find($photo->id))->toBeNull();
    Storage::disk('public')->assertMissing($photo->image_path);
});

test('deleting the album cover photo reassigns the cover to another photo', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $album = PhotoAlbum::factory()->create();
    $cover = Photo::factory()->create(['photo_album_id' => $album->id]);
    $other = Photo::factory()->create(['photo_album_id' => $album->id]);
    $album->update(['cover_image_path' => $cover->image_path]);
    Storage::disk('public')->put($cover->image_path, 'fake');
    Storage::disk('public')->put($other->image_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)->call('deletePhoto', $cover->id);

    expect($album->refresh()->cover_image_path)->toBe($other->image_path);
});

test('deleting a photo without permission is forbidden', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Defensor');
    $album = PhotoAlbum::factory()->create();
    $photo = Photo::factory()->create(['photo_album_id' => $album->id]);
    Storage::disk('public')->put($photo->image_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)->call('deletePhoto', $photo->id)->assertForbidden();
});

test('renaming a photo updates its caption', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $album = PhotoAlbum::factory()->create();
    $photo = Photo::factory()->create(['photo_album_id' => $album->id, 'caption' => 'Legenda antiga']);
    Storage::disk('public')->put($photo->image_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)
        ->set('renamingOwnerType', 'photo')
        ->set('renamingMediaId', $photo->id)
        ->set('renamingName', 'Legenda nova')
        ->call('renameItem');

    expect($photo->refresh()->caption)->toBe('Legenda nova');
});

test('deleting a document from the media library removes it from disk and the database', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $document = Document::factory()->create();
    Storage::disk('public')->put($document->file_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)->call('deleteDocument', $document->id);

    expect(Document::find($document->id))->toBeNull();
    Storage::disk('public')->assertMissing($document->file_path);
});

test('renaming a document updates its title', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $document = Document::factory()->create(['title' => 'Título antigo']);
    Storage::disk('public')->put($document->file_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)
        ->set('renamingOwnerType', 'document')
        ->set('renamingMediaId', $document->id)
        ->set('renamingName', 'Título novo')
        ->call('renameItem');

    expect($document->refresh()->title)->toBe('Título novo');
});

test('the generic deleteItem dispatcher routes to the right deletion by owner type', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $album = PhotoAlbum::factory()->create();
    $photo = Photo::factory()->create(['photo_album_id' => $album->id]);
    Storage::disk('public')->put($photo->image_path, 'fake');

    Livewire::test(MediaLibraryIndex::class)->call('deleteItem', 'photo', $photo->id);

    expect(Photo::find($photo->id))->toBeNull();
});

test('bulkDelete removes every selected item regardless of owner type and clears the selection', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $media = Media::factory()->create(['path' => 'media/solta.jpg', 'source' => 'Documentos']);
    Storage::disk('public')->put($media->path, 'fake');
    $document = Document::factory()->create(['file_path' => 'documents/edital.pdf']);
    Storage::disk('public')->put($document->file_path, 'fake');

    $component = Livewire::test(MediaLibraryIndex::class)
        ->set('selected', [
            "media:{$media->id}:",
            "document:{$document->id}:",
        ])
        ->call('bulkDelete');

    expect(Media::find($media->id))->toBeNull()
        ->and(Document::find($document->id))->toBeNull();
    $component->assertSet('selected', []);
});

test('toggleSelectAllOnPage selects then deselects every deletable item on the current page', function () {
    Storage::fake('public');
    actingAsRoleForMedia('Administrador');
    $media1 = Media::factory()->create(['path' => 'media/um.jpg']);
    $media2 = Media::factory()->create(['path' => 'media/dois.jpg']);
    Storage::disk('public')->put($media1->path, 'fake');
    Storage::disk('public')->put($media2->path, 'fake');

    $component = Livewire::test(MediaLibraryIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(2);

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});
