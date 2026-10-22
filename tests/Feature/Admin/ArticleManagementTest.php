<?php

use App\Livewire\Admin\Articles\ArticleForm;
use App\Livewire\Admin\Articles\ArticleIndex;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function actingAsRole(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can view the articles management page', function () {
    actingAsRole('Administrador');

    $this->get(route('admin.articles.index'))->assertStatus(200);
});

test('an Administrador can view the dedicated create and edit article pages', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create();

    $this->get(route('admin.articles.create'))->assertStatus(200);
    $this->get(route('admin.articles.edit', $article))->assertStatus(200);
});

test('saving the article form redirects back to the articles index', function () {
    actingAsRole('Administrador');
    $category = Category::factory()->create();

    Livewire::test(ArticleForm::class)
        ->set('title', 'Artigo Redirecionado')
        ->set('excerpt', 'Resumo do artigo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryIds', [$category->id])
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertRedirect(route('admin.articles.index'));
});

test('a Defensor cannot create or update an article via ArticleForm', function () {
    actingAsRole('Defensor');
    $category = Category::factory()->create();

    Livewire::test(ArticleForm::class)
        ->set('title', 'Tentativa Sem Permissão')
        ->set('excerpt', 'Resumo do artigo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryIds', [$category->id])
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertForbidden();

    expect(Article::where('title', 'Tentativa Sem Permissão')->exists())->toBeFalse();
});

test('creating an article with "postar agora" publishes it immediately', function () {
    actingAsRole('Administrador');
    $category = Category::factory()->create();

    Livewire::test(ArticleForm::class)
        ->set('title', 'Novo Artigo')
        ->set('excerpt', 'Resumo do artigo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryIds', [$category->id])
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasNoErrors();

    $article = Article::where('title', 'Novo Artigo')->first();
    expect($article)->not->toBeNull()
        ->and($article->is_published)->toBeTrue()
        ->and($article->published_at)->not->toBeNull();
});

test('creating an article with "agendar" publishes it with a future date', function () {
    actingAsRole('Administrador');

    $futureDate = now()->addDays(3);

    Livewire::test(ArticleForm::class)
        ->set('title', 'Artigo Agendado')
        ->set('excerpt', 'Resumo do artigo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->set('scheduledDate', $futureDate->format('Y-m-d'))
        ->set('scheduledTime', $futureDate->format('H:i'))
        ->call('save')
        ->assertHasNoErrors();

    $article = Article::where('title', 'Artigo Agendado')->first();
    expect($article)->not->toBeNull()
        ->and($article->is_published)->toBeTrue()
        ->and($article->isScheduled())->toBeTrue();
});

test('creating an article with a past date backdates it', function () {
    actingAsRole('Administrador');

    $pastDate = now()->subDay();

    Livewire::test(ArticleForm::class)
        ->set('title', 'Artigo Data Passada')
        ->set('excerpt', 'Resumo do artigo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->set('scheduledDate', $pastDate->format('Y-m-d'))
        ->set('scheduledTime', '10:00')
        ->call('save')
        ->assertHasNoErrors();

    $article = Article::where('title', 'Artigo Data Passada')->first();
    expect($article)->not->toBeNull()
        ->and($article->is_published)->toBeTrue()
        ->and($article->published_at->format('Y-m-d H:i'))->toBe($pastDate->format('Y-m-d').' 10:00');
});

test('creating an article without a body is rejected', function () {
    actingAsRole('Administrador');

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
    actingAsRole('Administrador');

    Livewire::test(ArticleForm::class)
        ->set('title', 'Categoria Inválida')
        ->set('excerpt', 'Resumo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryIds', [999999])
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasErrors(['categoryIds.0']);

    expect(Article::where('title', 'Categoria Inválida')->exists())->toBeFalse();
});

test('creating an article with no category succeeds', function () {
    actingAsRole('Administrador');

    Livewire::test(ArticleForm::class)
        ->set('title', 'Sem Categoria')
        ->set('excerpt', 'Resumo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasNoErrors();

    expect(Article::where('title', 'Sem Categoria')->first()?->categories)->toHaveCount(0);
});

test('editing an existing article updates it', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create(['title' => 'Antigo']);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->set('title', 'Novo Título')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->title)->toBe('Novo Título');
});

test('adding a category attaches it to the article and it can be removed again', function () {
    actingAsRole('Administrador');
    $category = Category::factory()->create();
    $article = Article::factory()->create();

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->set('newCategoryId', (string) $category->id)
        ->call('addCategory')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->categories->pluck('id')->all())->toBe([$category->id]);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->call('removeCategory', $category->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->categories)->toHaveCount(0);
});

test('an article can be saved with more than one category', function () {
    actingAsRole('Administrador');
    $mural = Category::factory()->create(['name' => 'Mural']);
    $noticia = Category::factory()->create(['name' => 'Notícia']);

    Livewire::test(ArticleForm::class)
        ->set('title', 'Multi Categoria')
        ->set('excerpt', 'Resumo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryIds', [$mural->id, $noticia->id])
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasNoErrors();

    $article = Article::where('title', 'Multi Categoria')->first();
    expect($article->categories->pluck('id')->sort()->values()->all())
        ->toBe([$mural->id, $noticia->id]);
});

test('a Defensor cannot publish an article', function () {
    actingAsRole('Defensor');
    $article = Article::factory()->create(['is_published' => false]);

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);

    expect($article->refresh()->is_published)->toBeFalse();
});

test('an Administrador can publish an article and it gets a published_at date', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create(['is_published' => false, 'published_at' => null]);

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);

    expect($article->refresh()->is_published)->toBeTrue()
        ->and($article->published_at)->not->toBeNull();
});

test('unpublishing an article preserves its original published_at', function () {
    actingAsRole('Administrador');
    $originalDate = now()->subDays(5);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => $originalDate]);

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);

    expect($article->refresh()->is_published)->toBeFalse()
        ->and($article->published_at->isSameMinute($originalDate))->toBeTrue();
});

test('a Defensor cannot delete an article (only Administrador/Super Administrador can)', function () {
    actingAsRole('Defensor');
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

test('uploading a featured image stores it and attaches it to the article', function () {
    Storage::fake('public');
    actingAsRole('Administrador');
    $category = Category::factory()->create();
    $image = UploadedFile::fake()->image('capa.jpg');

    Livewire::test(ArticleForm::class)
        ->set('title', 'Com Imagem')
        ->set('excerpt', 'Resumo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryIds', [$category->id])
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->set('featuredImage', $image)
        ->call('save')
        ->assertHasNoErrors();

    $article = Article::where('title', 'Com Imagem')->first();
    expect($article->featured_image)->not->toBeNull();
    Storage::disk('public')->assertExists($article->featured_image);
});

test('removing the featured image clears it on save', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create(['featured_image' => 'articles/old.jpg']);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->call('removeFeaturedImage')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->featured_image)->toBeNull();
});

test('the author can be changed from the form', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create();
    $otherAuthor = User::factory()->create();

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->set('authorId', $otherAuthor->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->author_id)->toBe($otherAuthor->id);
});

test('toggling publish from the article form updates its status', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create(['is_published' => false, 'published_at' => null]);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->call('togglePublish');

    expect($article->refresh()->is_published)->toBeTrue()
        ->and($article->published_at)->not->toBeNull();
});

test('a Defensor cannot toggle publish from the article form', function () {
    actingAsRole('Defensor');
    $article = Article::factory()->create(['is_published' => false]);

    Livewire::test(ArticleForm::class, ['article' => $article])->call('togglePublish');

    expect($article->refresh()->is_published)->toBeFalse();
});

test('publishing with a future scheduled date schedules the article instead of publishing it immediately', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create(['is_published' => false, 'published_at' => null]);
    $future = now()->addDays(3);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->set('scheduledDate', $future->format('Y-m-d'))
        ->set('scheduledTime', $future->format('H:i'))
        ->call('togglePublish');

    $article->refresh();
    expect($article->is_published)->toBeTrue()
        ->and($article->published_at->isSameMinute($future))->toBeTrue()
        ->and($article->isScheduled())->toBeTrue()
        ->and(Article::published()->whereKey($article->id)->exists())->toBeFalse();
});

test('toggling publish with a past date backdates the article instead of rejecting it', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create(['is_published' => false, 'published_at' => null]);
    $past = now()->subDay();

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->set('scheduledDate', $past->format('Y-m-d'))
        ->set('scheduledTime', $past->format('H:i'))
        ->call('togglePublish')
        ->assertHasNoErrors();

    $article->refresh();
    expect($article->is_published)->toBeTrue()
        ->and($article->published_at->isSameMinute($past))->toBeTrue();
});

test('editing an already scheduled article pre-fills the date/time fields with its scheduled date', function () {
    actingAsRole('Administrador');
    $scheduledAt = now()->addDays(3)->startOfMinute();
    $article = Article::factory()->create(['is_published' => true, 'published_at' => $scheduledAt]);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->assertSet('scheduledDate', $scheduledAt->format('Y-m-d'))
        ->assertSet('scheduledTime', $scheduledAt->format('H:i'));
});

test('editing a published (non-scheduled) article pre-fills the date/time fields with its published date', function () {
    actingAsRole('Administrador');
    $publishedAt = now()->subDay()->startOfMinute();
    $article = Article::factory()->create(['is_published' => true, 'published_at' => $publishedAt]);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->assertSet('scheduledDate', $publishedAt->format('Y-m-d'))
        ->assertSet('scheduledTime', $publishedAt->format('H:i'));
});

test('rescheduling an already published article updates its published_at on save', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()->subDays(2)]);
    $future = now()->addDay();

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->set('scheduledDate', $future->format('Y-m-d'))
        ->set('scheduledTime', $future->format('H:i'))
        ->call('save');

    expect($article->refresh()->published_at->isSameMinute($future))->toBeTrue();
});

test('editing a draft without a scheduled date defaults the date fields to its creation date, not today', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create(['is_published' => false, 'published_at' => null]);
    $created = now()->subDays(10)->startOfMinute();
    $article->created_at = $created;
    $article->save();

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->assertSet('scheduledDate', $created->format('Y-m-d'))
        ->assertSet('scheduledTime', $created->format('H:i'));
});

test('publishing a draft without choosing a date keeps the creation order (uses created_at, not now)', function () {
    actingAsRole('Administrador');
    $created = now()->subDays(10)->startOfMinute();
    $article = Article::factory()->create(['is_published' => false, 'published_at' => null]);
    $article->created_at = $created;
    $article->save();

    Livewire::test(ArticleForm::class, ['article' => $article])->call('togglePublish');

    expect($article->refresh()->published_at->isSameMinute($created))->toBeTrue();
});

test('bulkPublish publishes only the selected draft articles and leaves others untouched', function () {
    actingAsRole('Administrador');
    $draft1 = Article::factory()->create(['is_published' => false, 'published_at' => null]);
    $draft2 = Article::factory()->create(['is_published' => false, 'published_at' => null]);
    $notSelected = Article::factory()->create(['is_published' => false, 'published_at' => null]);

    Livewire::test(ArticleIndex::class)
        ->set('selected', [$draft1->id, $draft2->id])
        ->call('bulkPublish');

    expect($draft1->refresh()->is_published)->toBeTrue()
        ->and($draft1->published_at)->not->toBeNull()
        ->and($draft2->refresh()->is_published)->toBeTrue()
        ->and($notSelected->refresh()->is_published)->toBeFalse();
});

test('bulkUnpublish unpublishes only the selected published articles', function () {
    actingAsRole('Administrador');
    $published = Article::factory()->create(['is_published' => true, 'published_at' => now()]);
    $notSelected = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(ArticleIndex::class)
        ->set('selected', [$published->id])
        ->call('bulkUnpublish');

    expect($published->refresh()->is_published)->toBeFalse()
        ->and($notSelected->refresh()->is_published)->toBeTrue();
});

test('a Defensor cannot bulk publish or bulk unpublish articles', function () {
    actingAsRole('Defensor');
    $draft = Article::factory()->create(['is_published' => false]);
    $published = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(ArticleIndex::class)->set('selected', [$draft->id])->call('bulkPublish');
    Livewire::test(ArticleIndex::class)->set('selected', [$published->id])->call('bulkUnpublish');

    expect($draft->refresh()->is_published)->toBeFalse()
        ->and($published->refresh()->is_published)->toBeTrue();
});

test('bulkDelete removes every selected article and clears the selection', function () {
    actingAsRole('Administrador');
    $article1 = Article::factory()->create();
    $article2 = Article::factory()->create();

    $component = Livewire::test(ArticleIndex::class)
        ->set('selected', [$article1->id, $article2->id])
        ->call('bulkDelete');

    expect(Article::find($article1->id))->toBeNull()
        ->and(Article::find($article2->id))->toBeNull();
    $component->assertSet('selected', []);
});

test('a Defensor cannot bulk delete articles', function () {
    actingAsRole('Defensor');
    $article = Article::factory()->create();

    Livewire::test(ArticleIndex::class)->set('selected', [$article->id])->call('bulkDelete');

    expect(Article::find($article->id))->not->toBeNull();
});

test('toggleSelectAllOnPage selects then deselects every article on the current page', function () {
    actingAsRole('Administrador');
    Article::factory()->count(3)->create(['is_published' => true, 'published_at' => now()]);

    $component = Livewire::test(ArticleIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(3);

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});

test('applying a new article saves it without redirecting and keeps editing the same row', function () {
    actingAsRole('Administrador');
    $category = Category::factory()->create();

    $component = Livewire::test(ArticleForm::class)
        ->set('title', 'Aplicado Sem Sair')
        ->set('excerpt', 'Resumo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryIds', [$category->id])
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('apply')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertDispatched('toast');

    $article = Article::where('title', 'Aplicado Sem Sair')->first();
    expect($article)->not->toBeNull();

    $component->set('title', 'Aplicado Sem Sair (editado)')->call('apply');

    expect(Article::where('title', 'Aplicado Sem Sair (editado)')->count())->toBe(1)
        ->and(Article::count())->toBe(1);
});

test('a text custom field is saved and shown back on edit', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create();

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->call('addCustomField')
        ->set('customFields.0.type', 'link')
        ->set('customFields.0.label', 'Edital completo')
        ->set('customFields.0.value', 'https://exemplo.gov.br/edital.pdf')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->custom_fields)->toBe([
        ['type' => 'link', 'label' => 'Edital completo', 'value' => 'https://exemplo.gov.br/edital.pdf'],
    ]);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->assertSet('customFields.0.label', 'Edital completo');
});

test('a file custom field uploads and stores the file path', function () {
    Storage::fake('public');
    actingAsRole('Administrador');
    $article = Article::factory()->create();
    $file = UploadedFile::fake()->create('manual.pdf', 100);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->call('addCustomField')
        ->set('customFields.0.type', 'arquivo')
        ->set('customFields.0.label', 'Manual do servidor')
        ->set('customFields.0.file', $file)
        ->call('save')
        ->assertHasNoErrors();

    $customFields = $article->refresh()->custom_fields;
    expect($customFields)->toHaveCount(1)
        ->and($customFields[0]['value'])->not->toBeNull();
    Storage::disk('public')->assertExists($customFields[0]['value']);
});

test('removing a custom field drops it from the saved article', function () {
    actingAsRole('Administrador');
    $article = Article::factory()->create();

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->call('addCustomField')
        ->set('customFields.0.label', 'Campo A')
        ->call('addCustomField')
        ->set('customFields.1.label', 'Campo B')
        ->call('removeCustomField', 0)
        ->call('save')
        ->assertHasNoErrors();

    $customFields = $article->refresh()->custom_fields;
    expect($customFields)->toHaveCount(1)
        ->and($customFields[0]['label'])->toBe('Campo B');
});

test('uploading a body image stores it and dispatches its URL to the editor instead of base64', function () {
    Storage::fake('public');
    actingAsRole('Administrador');
    $image = UploadedFile::fake()->image('foto.jpg');

    $component = Livewire::test(ArticleForm::class)
        ->set('bodyImage', $image);

    $component->assertDispatched('tiptap-image-inserted');
    expect($component->get('bodyImage'))->toBeNull();

    $files = Storage::disk('public')->allFiles('articles/body');
    expect($files)->toHaveCount(1);
});

test('filtering the management list by category shows only articles in that category', function () {
    actingAsRole('Administrador');
    $wanted = Category::factory()->create(['name' => 'Avisos']);
    $other = Category::factory()->create(['name' => 'Institucional']);
    Article::factory()->hasAttached($wanted)->create(['title' => 'Artigo Avisos']);
    Article::factory()->hasAttached($other)->create(['title' => 'Artigo Institucional']);

    $component = Livewire::test(ArticleIndex::class)->set('categoryId', (string) $wanted->id);

    expect($component->get('articles')->pluck('title')->all())->toBe(['Artigo Avisos']);
});

test('filtering the management list by archive year shows only articles published that year', function () {
    actingAsRole('Administrador');
    Article::factory()->create(['title' => 'De 2024', 'is_published' => true, 'published_at' => '2024-05-01']);
    Article::factory()->create(['title' => 'De 2025', 'is_published' => true, 'published_at' => '2025-05-01']);

    $component = Livewire::test(ArticleIndex::class)->set('year', '2024');

    expect($component->get('articles')->pluck('title')->all())->toBe(['De 2024']);
});

test('the Mural management page shows articles from every category', function () {
    actingAsRole('Administrador');
    $noticia = Category::where('slug', 'noticia')->first() ?? Category::factory()->create(['slug' => 'noticia']);
    $other = Category::factory()->create();
    Article::factory()->hasAttached($noticia)->create(['title' => 'Notícia Teste']);
    Article::factory()->hasAttached($other)->create(['title' => 'Outro Artigo']);

    $response = $this->get(route('admin.articles.index'));

    $response->assertSee('Notícia Teste')->assertSee('Outro Artigo');
});

test('clearing filters on the management list resets search, category and year', function () {
    actingAsRole('Administrador');
    $category = Category::factory()->create();

    Livewire::test(ArticleIndex::class)
        ->set('search', 'algo')
        ->set('categoryId', (string) $category->id)
        ->set('year', '2024')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('categoryId', '')
        ->assertSet('year', '');
});
