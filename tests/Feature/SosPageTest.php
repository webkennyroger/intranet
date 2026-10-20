<?php

use App\Livewire\Site\Sos\SosIndex;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

function sosTestCategory(): Category
{
    return Category::firstOrCreate(['slug' => 'sos-dpemt'], ['name' => 'SOS DPEMT']);
}

test('a guest is redirected to login from the sos page', function () {
    $this->get('/sos')->assertRedirect(route('login'));
});

test('only published articles in the SOS DPEMT category appear on the sos page', function () {
    $user = User::factory()->create();
    $sos = sosTestCategory();
    $other = Category::factory()->create();
    Article::factory()->hasAttached($sos)->create(['title' => 'Alerta Importante', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($sos)->create(['title' => 'Rascunho', 'is_published' => false]);
    Article::factory()->hasAttached($other)->create(['title' => 'Artigo Fora do SOS', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get('/sos');

    $response->assertSee('Alerta Importante')->assertDontSee('Rascunho')->assertDontSee('Artigo Fora do SOS');
});

test('searching by keyword filters the sos feed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $sos = sosTestCategory();
    Article::factory()->hasAttached($sos)->create(['title' => 'Interrupção no Sistema', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($sos)->create(['title' => 'Outro Assunto', 'is_published' => true, 'published_at' => now()]);

    Livewire::test(SosIndex::class)
        ->set('search', 'Interrupção')
        ->assertSee('Interrupção no Sistema')
        ->assertDontSee('Outro Assunto');
});

test('filtering by the last 7 days excludes older sos articles', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $sos = sosTestCategory();
    Article::factory()->hasAttached($sos)->create(['title' => 'Recente', 'is_published' => true, 'published_at' => now()->subDays(2)]);
    Article::factory()->hasAttached($sos)->create(['title' => 'Antigo', 'is_published' => true, 'published_at' => now()->subDays(20)]);

    Livewire::test(SosIndex::class)
        ->set('period', '7d')
        ->assertSee('Recente')
        ->assertDontSee('Antigo');
});

test('toggling like on a sos article adds and then removes the like for the acting user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $sos = sosTestCategory();
    $article = Article::factory()->hasAttached($sos)->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(SosIndex::class)->call('toggleLike', $article->id);
    expect($article->refresh()->isLikedBy($user))->toBeTrue();

    Livewire::test(SosIndex::class)->call('toggleLike', $article->id);
    expect($article->refresh()->isLikedBy($user))->toBeFalse();
});

test('clearing filters resets search and period on the sos page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(SosIndex::class)
        ->set('search', 'Interrupção')
        ->set('period', '7d')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('period', '');
});

test('the sos page does not show the "Explore por tags" widget', function () {
    $user = User::factory()->create();
    $sos = sosTestCategory();
    Article::factory()->hasAttached($sos)->create(['is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get('/sos');

    $response->assertDontSee('Explore por tags');
});

test('the sos feed paginates at 9 articles per page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $sos = sosTestCategory();
    Article::factory()->count(10)->hasAttached($sos)->sequence(fn ($sequence) => ['title' => "Aviso {$sequence->index}"])
        ->create(['is_published' => true, 'published_at' => now()]);

    $component = Livewire::test(SosIndex::class);

    expect($component->instance()->articles)
        ->toHaveCount(9)
        ->and($component->instance()->articles->hasMorePages())->toBeTrue();
});
