<?php

use App\Livewire\Mural\ArticleShow;
use App\Models\Article;
use App\Models\User;
use Livewire\Livewire;

test('a published article is viewable', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['title' => 'Artigo Publicado', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertStatus(200)->assertSee('Artigo Publicado');
});

test('a draft article returns 404 on the public show page', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['title' => 'Rascunho', 'is_published' => false]);

    $this->actingAs($user)->get(route('mural.show', $article))->assertStatus(404);
});

test('a guest is redirected to login from the article show page', function () {
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    $this->get(route('mural.show', $article))->assertRedirect(route('login'));
});

test('liking an article from the show page updates the like count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(ArticleShow::class, ['article' => $article])->call('toggleLike');

    expect($article->refresh()->isLikedBy($user))->toBeTrue();
});
