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
