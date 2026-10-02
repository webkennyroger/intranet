<?php

use App\Livewire\Mural\MuralIndex;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

test('a guest is redirected to login from the mural page', function () {
    $this->get('/mural')->assertRedirect(route('login'));
});

test('only published articles appear on the mural page', function () {
    $user = User::factory()->create();
    Article::factory()->create(['title' => 'Publicado', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['title' => 'Rascunho', 'is_published' => false]);

    $response = $this->actingAs($user)->get('/mural');

    $response->assertSee('Publicado')->assertDontSee('Rascunho');
});

test('searching by keyword filters the mural feed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Article::factory()->create(['title' => 'Campanha de Natal', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['title' => 'Outro Assunto', 'is_published' => true, 'published_at' => now()]);

    Livewire::test(MuralIndex::class)
        ->set('search', 'Natal')
        ->assertSee('Campanha de Natal')
        ->assertDontSee('Outro Assunto');
});

test('filtering by category shows only articles in that category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();
    Article::factory()->create(['title' => 'Da Categoria A', 'category_id' => $categoryA->id, 'is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['title' => 'Da Categoria B', 'category_id' => $categoryB->id, 'is_published' => true, 'published_at' => now()]);

    Livewire::test(MuralIndex::class)
        ->set('categoryId', (string) $categoryA->id)
        ->assertSee('Da Categoria A')
        ->assertDontSee('Da Categoria B');
});

test('filtering by the last 7 days excludes older articles', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Article::factory()->create(['title' => 'Recente', 'is_published' => true, 'published_at' => now()->subDays(2)]);
    Article::factory()->create(['title' => 'Antigo', 'is_published' => true, 'published_at' => now()->subDays(20)]);

    Livewire::test(MuralIndex::class)
        ->set('period', '7d')
        ->assertSee('Recente')
        ->assertDontSee('Antigo');
});

test('toggling like adds and then removes the like for the acting user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(MuralIndex::class)->call('toggleLike', $article->id);
    expect($article->refresh()->isLikedBy($user))->toBeTrue();

    Livewire::test(MuralIndex::class)->call('toggleLike', $article->id);
    expect($article->refresh()->isLikedBy($user))->toBeFalse();
});

test('an article title is escaped in the rendered feed', function () {
    $user = User::factory()->create();
    Article::factory()->create(['title' => '<script>alert(1)</script>', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get('/mural');

    $response->assertDontSee('<script>alert(1)</script>', false);
});
