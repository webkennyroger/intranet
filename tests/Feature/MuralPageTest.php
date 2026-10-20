<?php

use App\Livewire\Site\Mural\MuralIndex;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

function muralTestCategory(): Category
{
    return Category::firstOrCreate(['slug' => 'mural'], ['name' => 'Mural']);
}

test('a guest is redirected to login from the mural page', function () {
    $this->get('/mural')->assertRedirect(route('login'));
});

test('only published articles in the Mural category appear on the mural page', function () {
    $user = User::factory()->create();
    $mural = muralTestCategory();
    $other = Category::factory()->create();
    Article::factory()->hasAttached($mural)->create(['title' => 'Publicado', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($mural)->create(['title' => 'Rascunho', 'is_published' => false]);
    Article::factory()->hasAttached($other)->create(['title' => 'Outra Categoria', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get('/mural');

    $response->assertSee('Publicado')->assertDontSee('Rascunho')->assertDontSee('Outra Categoria');
});

test('searching by keyword filters the mural feed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $mural = muralTestCategory();
    Article::factory()->hasAttached($mural)->create(['title' => 'Campanha de Natal', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($mural)->create(['title' => 'Outro Assunto', 'is_published' => true, 'published_at' => now()]);

    Livewire::test(MuralIndex::class)
        ->set('search', 'Natal')
        ->assertSee('Campanha de Natal')
        ->assertDontSee('Outro Assunto');
});

test('filtering by the last 7 days excludes older articles', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $mural = muralTestCategory();
    Article::factory()->hasAttached($mural)->create(['title' => 'Recente', 'is_published' => true, 'published_at' => now()->subDays(2)]);
    Article::factory()->hasAttached($mural)->create(['title' => 'Antigo', 'is_published' => true, 'published_at' => now()->subDays(20)]);

    Livewire::test(MuralIndex::class)
        ->set('period', '7d')
        ->assertSee('Recente')
        ->assertDontSee('Antigo');
});

test('toggling like adds and then removes the like for the acting user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $mural = muralTestCategory();
    $article = Article::factory()->hasAttached($mural)->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(MuralIndex::class)->call('toggleLike', $article->id);
    expect($article->refresh()->isLikedBy($user))->toBeTrue();

    Livewire::test(MuralIndex::class)->call('toggleLike', $article->id);
    expect($article->refresh()->isLikedBy($user))->toBeFalse();
});

test('clearing filters resets search and period', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(MuralIndex::class)
        ->set('search', 'Natal')
        ->set('period', '7d')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('period', '');
});

test('the mural feed paginates at 9 articles per page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $mural = muralTestCategory();
    Article::factory()->count(10)->hasAttached($mural)->sequence(fn ($sequence) => ['title' => "Artigo {$sequence->index}"])
        ->create(['is_published' => true, 'published_at' => now()]);

    $component = Livewire::test(MuralIndex::class);

    expect($component->instance()->articles)
        ->toHaveCount(9)
        ->and($component->instance()->articles->hasMorePages())->toBeTrue();
});

test('an article title is escaped in the rendered feed', function () {
    $user = User::factory()->create();
    $mural = muralTestCategory();
    Article::factory()->hasAttached($mural)->create(['title' => '<script>alert(1)</script>', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get('/mural');

    $response->assertDontSee('<script>alert(1)</script>', false);
});

test('the mural feed does not show the "Explore por tags" widget', function () {
    $user = User::factory()->create();
    Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get('/mural');

    $response->assertDontSee('Explore por tags');
});

test('the category label uses the Mural category own color', function () {
    $user = User::factory()->create();
    $mural = muralTestCategory();
    $mural->update(['color' => '#2563EB']);
    Article::factory()->hasAttached($mural)->create(['is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get('/mural');

    $response->assertSee('color: #2563EB', false);
});
