<?php

use App\Livewire\Home\HomeIndex;
use App\Models\Article;
use App\Models\BirthdayBanner;
use App\Models\Event;
use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Models\User;
use Livewire\Livewire;

test('a logged-in user can view the home page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertStatus(200)
        ->assertSeeLivewire(HomeIndex::class);
});

test('the home page shows the latest published article as the headline', function () {
    $user = User::factory()->create();
    Article::factory()->create(['title' => 'Rascunho não publicado', 'is_published' => false]);
    Article::factory()->create([
        'title' => 'Notícia mais recente',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertSee('Notícia mais recente')
        ->assertDontSee('Rascunho não publicado');
});

test('the home page shows published photo albums with their photo count', function () {
    $user = User::factory()->create();
    $album = PhotoAlbum::factory()->create(['title' => 'Álbum Publicado', 'is_published' => true]);
    Photo::factory()->count(5)->create(['photo_album_id' => $album->id]);
    PhotoAlbum::factory()->create(['title' => 'Álbum Rascunho', 'is_published' => false]);

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertSee('Álbum Publicado')
        ->assertSee('5 Fotos')
        ->assertDontSee('Álbum Rascunho');
});

test('the home page shows the next upcoming published event', function () {
    $user = User::factory()->create();
    Event::factory()->create(['title' => 'Evento Passado', 'starts_at' => now()->subDays(5), 'ends_at' => now()->subDays(5)]);
    Event::factory()->create(['title' => 'Próximo Evento Real', 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)]);

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertSee('Próximo Evento Real')
        ->assertDontSee('Evento Passado');
});

test('the home page shows the current month\'s published birthday banner image', function () {
    $user = User::factory()->create();
    $banner = BirthdayBanner::factory()->create(['month' => now()->month, 'is_published' => true]);

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertSee($banner->imageUrl(), false);
});

test('the home page does not show the birthdays section when no banner is published', function () {
    $user = User::factory()->create();
    BirthdayBanner::factory()->create(['month' => now()->month, 'is_published' => false]);

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertDontSee('Aniversariantes de');
});

test('toggling the like button likes and unlikes the headline article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    $this->actingAs($user);

    Livewire::test(HomeIndex::class)->call('toggleLike', $article->id);
    expect($article->isLikedBy($user))->toBeTrue();

    Livewire::test(HomeIndex::class)->call('toggleLike', $article->id);
    expect($article->isLikedBy($user))->toBeFalse();
});
