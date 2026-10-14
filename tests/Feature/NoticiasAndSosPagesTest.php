<?php

use App\Livewire\Noticias\NoticiasIndex;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Livewire\Livewire;

test('the notícias page only shows published articles in the Notícia category', function () {
    $this->seed(CategorySeeder::class);
    $user = User::factory()->create();
    $noticiaCategory = Category::where('slug', 'noticia')->first();
    $otherCategory = Category::factory()->create();

    Article::factory()->hasAttached($noticiaCategory)->create(['title' => 'Notícia Oficial', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($otherCategory)->create(['title' => 'Artigo de Outra Categoria', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($noticiaCategory)->create(['title' => 'Notícia Rascunho', 'is_published' => false]);

    $this->actingAs($user)->get(route('noticias.index'))
        ->assertOk()
        ->assertSee('Notícia Oficial')
        ->assertDontSee('Artigo de Outra Categoria')
        ->assertDontSee('Notícia Rascunho');
});

test('searching the notícias page filters by title', function () {
    $this->seed(CategorySeeder::class);
    $user = User::factory()->create();
    $noticiaCategory = Category::where('slug', 'noticia')->first();
    Article::factory()->hasAttached($noticiaCategory)->create(['title' => 'Protocolo de Atendimento', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($noticiaCategory)->create(['title' => 'Outro Comunicado', 'is_published' => true, 'published_at' => now()]);

    $this->actingAs($user);
    $component = Livewire::test(NoticiasIndex::class)->set('search', 'Protocolo');

    expect($component->viewData('articles')->pluck('title')->all())->toBe(['Protocolo de Atendimento']);
});

test('filtering the notícias page by archive year shows only that year', function () {
    $this->seed(CategorySeeder::class);
    $user = User::factory()->create();
    $noticiaCategory = Category::where('slug', 'noticia')->first();
    Article::factory()->hasAttached($noticiaCategory)->create(['title' => 'De 2024', 'is_published' => true, 'published_at' => '2024-05-01']);
    Article::factory()->hasAttached($noticiaCategory)->create(['title' => 'De 2025', 'is_published' => true, 'published_at' => '2025-05-01']);

    $this->actingAs($user);
    $component = Livewire::test(NoticiasIndex::class)->set('year', '2024');

    expect($component->viewData('articles')->pluck('title')->all())->toBe(['De 2024']);
});

test('a reader can like a notícia from the notícias page', function () {
    $this->seed(CategorySeeder::class);
    $user = User::factory()->create();
    $noticiaCategory = Category::where('slug', 'noticia')->first();
    $article = Article::factory()->hasAttached($noticiaCategory)->create(['is_published' => true, 'published_at' => now()]);

    $this->actingAs($user);
    Livewire::test(NoticiasIndex::class)->call('toggleLike', $article->id);

    expect($article->isLikedBy($user))->toBeTrue();
});

test('the notícias page has no edit, delete or create controls', function () {
    $this->seed(CategorySeeder::class);
    $user = User::factory()->create();
    $noticiaCategory = Category::where('slug', 'noticia')->first();
    Article::factory()->hasAttached($noticiaCategory)->create(['is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('noticias.index'));

    $response->assertDontSee('Nova notícia')
        ->assertDontSee('wire:click="delete', false)
        ->assertDontSee('wire:click="togglePublish', false);
});
