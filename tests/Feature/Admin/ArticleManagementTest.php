<?php

use App\Livewire\Admin\Articles\ArticleForm;
use App\Livewire\Admin\Articles\ArticleIndex;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRole(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Editor can view the articles management page', function () {
    actingAsRole('Editor');

    $this->get(route('admin.articles.index'))->assertStatus(200);
});

test('creating an article saves it as a draft', function () {
    actingAsRole('Editor');
    $category = Category::factory()->create();

    Livewire::test(ArticleForm::class)
        ->set('title', 'Novo Artigo')
        ->set('excerpt', 'Resumo do artigo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryId', (string) $category->id)
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasNoErrors();

    $article = Article::where('title', 'Novo Artigo')->first();
    expect($article)->not->toBeNull()
        ->and($article->is_published)->toBeFalse();
});

test('creating an article without a body is rejected', function () {
    actingAsRole('Editor');

    Livewire::test(ArticleForm::class)
        ->set('title', 'Sem Corpo')
        ->set('excerpt', 'Resumo')
        ->set('body', '')
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasErrors(['body']);

    expect(Article::where('title', 'Sem Corpo')->exists())->toBeFalse();
});

test('creating an article with a non-existent category is rejected', function () {
    actingAsRole('Editor');

    Livewire::test(ArticleForm::class)
        ->set('title', 'Categoria Inválida')
        ->set('excerpt', 'Resumo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryId', '999999')
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasErrors(['categoryId']);

    expect(Article::where('title', 'Categoria Inválida')->exists())->toBeFalse();
});

test('creating an article with no category succeeds', function () {
    actingAsRole('Editor');

    Livewire::test(ArticleForm::class)
        ->set('title', 'Sem Categoria')
        ->set('excerpt', 'Resumo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasNoErrors();

    expect(Article::where('title', 'Sem Categoria')->first()?->category_id)->toBeNull();
});

test('editing an existing article updates it', function () {
    actingAsRole('Editor');
    $article = Article::factory()->create(['title' => 'Antigo']);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->set('title', 'Novo Título')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->title)->toBe('Novo Título');
});

test('an Editor cannot publish an article', function () {
    actingAsRole('Editor');
    $article = Article::factory()->create(['is_published' => false]);

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);

    expect($article->refresh()->is_published)->toBeFalse();
});

test('a Gestor can publish an article and it gets a published_at date', function () {
    actingAsRole('Gestor');
    $article = Article::factory()->create(['is_published' => false, 'published_at' => null]);

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);

    expect($article->refresh()->is_published)->toBeTrue()
        ->and($article->published_at)->not->toBeNull();
});

test('unpublishing an article preserves its original published_at', function () {
    actingAsRole('Gestor');
    $originalDate = now()->subDays(5);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => $originalDate]);

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);

    expect($article->refresh()->is_published)->toBeFalse()
        ->and($article->published_at->isSameMinute($originalDate))->toBeTrue();
});

test('an Editor cannot delete an article', function () {
    actingAsRole('Editor');
    $article = Article::factory()->create();

    Livewire::test(ArticleIndex::class)->call('delete', $article->id);

    expect(Article::find($article->id))->not->toBeNull();
});

test('a Gestor cannot delete an article (only Administrador/Super Administrador can)', function () {
    actingAsRole('Gestor');
    $article = Article::factory()->create();

    Livewire::test(ArticleIndex::class)->call('delete', $article->id);

    expect(Article::find($article->id))->not->toBeNull();
});

test('an Administrador can delete an article', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create();

    Livewire::test(ArticleIndex::class)->call('delete', $article->id);

    expect(Article::find($article->id))->toBeNull();
});
