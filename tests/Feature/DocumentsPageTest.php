<?php

use App\Livewire\Site\Documents\DocumentsIndex;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('a guest is redirected to login from the documents page', function () {
    $this->get('/documentos')->assertRedirect(route('login'));
});

test('only published documents appear on the documents page', function () {
    $user = User::factory()->create();
    Document::factory()->create(['title' => 'Publicado', 'is_published' => true]);
    Document::factory()->create(['title' => 'Rascunho', 'is_published' => false]);

    $response = $this->actingAs($user)->get('/documentos');

    $response->assertSee('Publicado')->assertDontSee('Rascunho');
});

test('searching by keyword filters the documents list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Document::factory()->create(['title' => 'Edital de Licitação', 'is_published' => true]);
    Document::factory()->create(['title' => 'Outro Assunto', 'is_published' => true]);

    Livewire::test(DocumentsIndex::class)
        ->set('search', 'Licitação')
        ->assertSee('Edital de Licitação')
        ->assertDontSee('Outro Assunto');
});

test('filtering by category only shows documents from that folder', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $resolucoes = DocumentCategory::factory()->create(['name' => 'Resoluções']);
    $contratos = DocumentCategory::factory()->create(['name' => 'Contratos']);
    Document::factory()->create(['title' => 'Resolução 01', 'is_published' => true, 'document_category_id' => $resolucoes->id]);
    Document::factory()->create(['title' => 'Contrato 02', 'is_published' => true, 'document_category_id' => $contratos->id]);

    Livewire::test(DocumentsIndex::class)
        ->call('selectCategory', $resolucoes->id)
        ->assertSee('Resolução 01')
        ->assertDontSee('Contrato 02');
});

test('selecting the same category again clears the category filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = DocumentCategory::factory()->create();

    Livewire::test(DocumentsIndex::class)
        ->call('selectCategory', $category->id)
        ->assertSet('categoryId', (string) $category->id)
        ->call('selectCategory', $category->id)
        ->assertSet('categoryId', '');
});

test('filtering by the last 7 days excludes older documents', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Document::factory()->create(['title' => 'Recente', 'is_published' => true, 'created_at' => now()->subDays(2)]);
    Document::factory()->create(['title' => 'Antigo', 'is_published' => true, 'created_at' => now()->subDays(20)]);

    Livewire::test(DocumentsIndex::class)
        ->set('period', '7d')
        ->assertSee('Recente')
        ->assertDontSee('Antigo');
});

test('clearing filters resets search, category and period', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = DocumentCategory::factory()->create();

    Livewire::test(DocumentsIndex::class)
        ->set('search', 'algo')
        ->set('period', '7d')
        ->call('selectCategory', $category->id)
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('period', '')
        ->assertSet('categoryId', '');
});

test('the documents list paginates at 10 per page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Document::factory()->count(12)->sequence(fn ($sequence) => ['title' => "Documento {$sequence->index}"])->create(['is_published' => true]);

    $component = Livewire::test(DocumentsIndex::class);

    expect($component->instance()->documents)
        ->toHaveCount(10)
        ->and($component->instance()->documents->hasMorePages())->toBeTrue();
});

test('each folder shows the real count of published documents inside it', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = DocumentCategory::factory()->create(['name' => 'Cartilhas']);
    Document::factory()->create(['is_published' => true, 'document_category_id' => $category->id]);
    Document::factory()->create(['is_published' => true, 'document_category_id' => $category->id]);
    Document::factory()->create(['is_published' => false, 'document_category_id' => $category->id]);

    $response = $this->actingAs($user)->get('/documentos');

    $response->assertSee('Cartilhas')->assertSee('2 arquivos');
});
