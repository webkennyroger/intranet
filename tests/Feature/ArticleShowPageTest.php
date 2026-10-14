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

test('visiting an article show page increments its views count', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now(), 'views_count' => 0]);

    $this->actingAs($user)->get(route('mural.show', $article));
    $this->actingAs($user)->get(route('mural.show', $article));

    expect($article->refresh()->views_count)->toBe(2);
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

test('a texto custom field whose value is a link renders its label as the clickable link, with a file-type icon', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create([
        'is_published' => true,
        'published_at' => now(),
        'custom_fields' => [
            ['type' => 'texto', 'label' => 'Manual explicativo diárias', 'value' => 'https://intranet.test/documentos/310-MANUAL.pdf'],
        ],
    ]);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertSee('href="https://intranet.test/documentos/310-MANUAL.pdf"', false)
        ->assertSee('fa-file-pdf', false)
        ->assertSee('Manual explicativo diárias')
        ->assertDontSeeText('https://intranet.test/documentos/310-MANUAL.pdf');
});

test('a plain texto custom field without a link value still renders as plain text', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create([
        'is_published' => true,
        'published_at' => now(),
        'custom_fields' => [
            ['type' => 'texto', 'label' => 'Observação', 'value' => 'Entregar até o dia 10.'],
        ],
    ]);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertSee('Entregar até o dia 10.')->assertDontSee('fa-file-pdf', false);
});
