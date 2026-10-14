<?php

use App\Livewire\Admin\Videos\VideoForm;
use App\Livewire\Admin\Videos\VideoIndex;
use App\Models\User;
use App\Models\Video;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForVideos(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can view the videos management page', function () {
    actingAsRoleForVideos('Administrador');

    $this->get(route('admin.videos.index'))->assertStatus(200);
});

test('creating a video saves it with the given url', function () {
    actingAsRoleForVideos('Administrador');

    Livewire::test(VideoForm::class)
        ->set('title', 'Boas Práticas 2026')
        ->set('url', 'https://www.youtube.com/watch?v=abc123')
        ->call('save')
        ->assertHasNoErrors();

    $video = Video::where('title', 'Boas Práticas 2026')->first();
    expect($video)->not->toBeNull()
        ->and($video->url)->toBe('https://www.youtube.com/watch?v=abc123');
});

test('creating a video with an invalid url is rejected', function () {
    actingAsRoleForVideos('Administrador');

    Livewire::test(VideoForm::class)
        ->set('title', 'Vídeo Inválido')
        ->set('url', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['url']);

    expect(Video::where('title', 'Vídeo Inválido')->exists())->toBeFalse();
});

test('a Defensor cannot publish a video', function () {
    actingAsRoleForVideos('Defensor');
    $video = Video::factory()->create(['is_published' => false]);

    Livewire::test(VideoIndex::class)->call('togglePublish', $video->id);

    expect($video->refresh()->is_published)->toBeFalse();
});

test('an Administrador can delete a video', function () {
    actingAsRoleForVideos('Administrador');
    $video = Video::factory()->create();

    Livewire::test(VideoIndex::class)->call('delete', $video->id);

    expect(Video::find($video->id))->toBeNull();
});

test('bulkPublish publishes only the selected draft videos and leaves others untouched', function () {
    actingAsRoleForVideos('Administrador');
    $draft1 = Video::factory()->create(['is_published' => false]);
    $draft2 = Video::factory()->create(['is_published' => false]);
    $notSelected = Video::factory()->create(['is_published' => false]);

    Livewire::test(VideoIndex::class)
        ->set('selected', [$draft1->id, $draft2->id])
        ->call('bulkPublish');

    expect($draft1->refresh()->is_published)->toBeTrue()
        ->and($draft2->refresh()->is_published)->toBeTrue()
        ->and($notSelected->refresh()->is_published)->toBeFalse();
});

test('bulkUnpublish unpublishes only the selected published videos', function () {
    actingAsRoleForVideos('Administrador');
    $published = Video::factory()->create(['is_published' => true]);
    $notSelected = Video::factory()->create(['is_published' => true]);

    Livewire::test(VideoIndex::class)
        ->set('selected', [$published->id])
        ->call('bulkUnpublish');

    expect($published->refresh()->is_published)->toBeFalse()
        ->and($notSelected->refresh()->is_published)->toBeTrue();
});

test('a Defensor cannot bulk publish or bulk unpublish videos', function () {
    actingAsRoleForVideos('Defensor');
    $draft = Video::factory()->create(['is_published' => false]);
    $published = Video::factory()->create(['is_published' => true]);

    Livewire::test(VideoIndex::class)->set('selected', [$draft->id])->call('bulkPublish');
    Livewire::test(VideoIndex::class)->set('selected', [$published->id])->call('bulkUnpublish');

    expect($draft->refresh()->is_published)->toBeFalse()
        ->and($published->refresh()->is_published)->toBeTrue();
});

test('bulkDelete removes every selected video and clears the selection', function () {
    actingAsRoleForVideos('Administrador');
    $video1 = Video::factory()->create();
    $video2 = Video::factory()->create();

    $component = Livewire::test(VideoIndex::class)
        ->set('selected', [$video1->id, $video2->id])
        ->call('bulkDelete');

    expect(Video::find($video1->id))->toBeNull()
        ->and(Video::find($video2->id))->toBeNull();
    $component->assertSet('selected', []);
});

test('a Defensor cannot bulk delete videos', function () {
    actingAsRoleForVideos('Defensor');
    $video = Video::factory()->create();

    Livewire::test(VideoIndex::class)->set('selected', [$video->id])->call('bulkDelete');

    expect(Video::find($video->id))->not->toBeNull();
});

test('toggleSelectAllOnPage selects then deselects every video on the current page', function () {
    actingAsRoleForVideos('Administrador');
    Video::factory()->count(3)->create();

    $component = Livewire::test(VideoIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(3);

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});
