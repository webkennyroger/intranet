<?php

use App\Livewire\Admin\Documents\DocumentForm;
use App\Livewire\Admin\Documents\DocumentIndex;
use App\Models\Document;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function actingAsRoleForDocuments(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can view the documents management page', function () {
    actingAsRoleForDocuments('Administrador');

    $this->get(route('admin.documents.index'))->assertStatus(200);
});

test('creating a document uploads the file and stores its path', function () {
    Storage::fake('public');
    actingAsRoleForDocuments('Administrador');

    Livewire::test(DocumentForm::class)
        ->set('title', 'Regimento Interno')
        ->set('files', [UploadedFile::fake()->create('regimento.pdf', 100)])
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::where('title', 'Regimento Interno')->first();
    expect($document)->not->toBeNull();
    Storage::disk('public')->assertExists($document->file_path);
});

test('creating a document without a file is rejected', function () {
    actingAsRoleForDocuments('Administrador');

    Livewire::test(DocumentForm::class)
        ->set('title', 'Sem Arquivo')
        ->call('save')
        ->assertHasErrors(['files']);

    expect(Document::where('title', 'Sem Arquivo')->exists())->toBeFalse();
});

test('editing an existing document without a new file keeps the original file path', function () {
    Storage::fake('public');
    actingAsRoleForDocuments('Administrador');
    $document = Document::factory()->create(['file_path' => 'documents/original.pdf']);

    Livewire::test(DocumentForm::class, ['document' => $document])
        ->set('title', 'Título Atualizado')
        ->call('save')
        ->assertHasNoErrors();

    expect($document->refresh()->title)->toBe('Título Atualizado')
        ->and($document->file_path)->toBe('documents/original.pdf');
});

test('dropping more than one file at once creates one document per file, titled after the file name', function () {
    Storage::fake('public');
    actingAsRoleForDocuments('Administrador');

    Livewire::test(DocumentForm::class)
        ->set('files', [
            UploadedFile::fake()->create('edital-01.pdf', 50),
            UploadedFile::fake()->create('edital-02.pdf', 50),
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(Document::count())->toBe(2)
        ->and(Document::where('title', 'edital-01')->exists())->toBeTrue()
        ->and(Document::where('title', 'edital-02')->exists())->toBeTrue();

    foreach (Document::all() as $document) {
        Storage::disk('public')->assertExists($document->file_path);
    }
});

test('picking an existing file reuses its path instead of storing a new upload', function () {
    Storage::fake('public');
    actingAsRoleForDocuments('Administrador');
    $existing = Document::factory()->create(['title' => 'Edital Anterior', 'file_path' => 'documents/edital-anterior.pdf']);
    Storage::disk('public')->put($existing->file_path, 'fake');

    Livewire::test(DocumentForm::class)
        ->set('title', 'Novo Título Pro Mesmo Arquivo')
        ->set('useExistingFile', true)
        ->call('pickExistingFile', $existing->file_path, $existing->title)
        ->call('save')
        ->assertHasNoErrors();

    $new = Document::where('title', 'Novo Título Pro Mesmo Arquivo')->first();
    expect($new)->not->toBeNull()
        ->and($new->file_path)->toBe($existing->file_path);
});

test('existing file search filters by name', function () {
    Storage::fake('public');
    actingAsRoleForDocuments('Administrador');
    $a = Document::factory()->create(['title' => 'Edital de Licitação', 'file_path' => 'documents/a.pdf']);
    $b = Document::factory()->create(['title' => 'Outro Assunto', 'file_path' => 'documents/b.pdf']);
    Storage::disk('public')->put($a->file_path, 'fake');
    Storage::disk('public')->put($b->file_path, 'fake');

    Livewire::test(DocumentForm::class)
        ->set('useExistingFile', true)
        ->set('existingFileSearch', 'Licitação')
        ->assertSee('Edital de Licitação')
        ->assertDontSee('Outro Assunto');
});

test('choosing to reuse an existing file but not picking one is rejected', function () {
    actingAsRoleForDocuments('Administrador');

    Livewire::test(DocumentForm::class)
        ->set('title', 'Sem Arquivo Nenhum')
        ->set('useExistingFile', true)
        ->call('save')
        ->assertHasErrors(['files']);

    expect(Document::where('title', 'Sem Arquivo Nenhum')->exists())->toBeFalse();
});

test('creating a document with only a link stores no file and treats it as external', function () {
    actingAsRoleForDocuments('Administrador');

    Livewire::test(DocumentForm::class)
        ->set('title', 'Portal da Transparência')
        ->set('link', 'https://exemplo.gov.br/transparencia')
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::where('title', 'Portal da Transparência')->first();
    expect($document)->not->toBeNull()
        ->and($document->link)->toBe('https://exemplo.gov.br/transparencia')
        ->and($document->file_path)->toBeNull()
        ->and($document->isExternalLink())->toBeTrue()
        ->and($document->url())->toBe('https://exemplo.gov.br/transparencia')
        ->and($document->icon())->toBe('link');
});

test('an invalid link is rejected', function () {
    actingAsRoleForDocuments('Administrador');

    Livewire::test(DocumentForm::class)
        ->set('title', 'Link Inválido')
        ->set('link', 'isso não é um link')
        ->call('save')
        ->assertHasErrors(['link']);

    expect(Document::where('title', 'Link Inválido')->exists())->toBeFalse();
});

test('a plain text description does not make a document an external link', function () {
    Storage::fake('public');
    actingAsRoleForDocuments('Administrador');
    $document = Document::factory()->create(['description' => 'Resumo normal, não é um link.', 'file_path' => 'documents/edital.pdf', 'link' => null]);
    Storage::disk('public')->put($document->file_path, 'fake');

    expect($document->isExternalLink())->toBeFalse()
        ->and($document->icon())->toBe('file-pdf')
        ->and($document->url())->toBe(Storage::url($document->file_path));
});

test('a document can have both a file and a link at the same time', function () {
    Storage::fake('public');
    actingAsRoleForDocuments('Administrador');
    $document = Document::factory()->create(['file_path' => 'documents/edital.pdf', 'link' => 'https://exemplo.gov.br/pagina']);
    Storage::disk('public')->put($document->file_path, 'fake');

    expect($document->fileUrl())->toBe(Storage::url($document->file_path))
        ->and($document->fileIcon())->toBe('file-pdf')
        ->and($document->isExternalLink())->toBeTrue()
        ->and($document->url())->toBe('https://exemplo.gov.br/pagina');
});

test('a document with an uploaded PDF and no link reports the pdf icon and the storage url', function () {
    Storage::fake('public');
    actingAsRoleForDocuments('Administrador');
    $document = Document::factory()->create(['file_path' => 'documents/edital.pdf', 'link' => null]);
    Storage::disk('public')->put($document->file_path, 'fake');

    expect($document->isExternalLink())->toBeFalse()
        ->and($document->icon())->toBe('file-pdf')
        ->and($document->url())->toBe(Storage::url($document->file_path));
});

test('a Defensor cannot publish a document', function () {
    actingAsRoleForDocuments('Defensor');
    $document = Document::factory()->create(['is_published' => false]);

    Livewire::test(DocumentIndex::class)->call('togglePublish', $document->id);

    expect($document->refresh()->is_published)->toBeFalse();
});

test('an Administrador can publish a document', function () {
    actingAsRoleForDocuments('Administrador');
    $document = Document::factory()->create(['is_published' => false]);

    Livewire::test(DocumentIndex::class)->call('togglePublish', $document->id);

    expect($document->refresh()->is_published)->toBeTrue();
});

test('a Defensor cannot delete a document', function () {
    actingAsRoleForDocuments('Defensor');
    $document = Document::factory()->create();

    Livewire::test(DocumentIndex::class)->call('delete', $document->id);

    expect(Document::find($document->id))->not->toBeNull();
});

test('an Administrador can delete a document', function () {
    actingAsRoleForDocuments('Administrador');
    $document = Document::factory()->create();

    Livewire::test(DocumentIndex::class)->call('delete', $document->id);

    expect(Document::find($document->id))->toBeNull();
});

test('bulkPublish publishes only the selected draft documents and leaves others untouched', function () {
    actingAsRoleForDocuments('Administrador');
    $draft1 = Document::factory()->create(['is_published' => false]);
    $draft2 = Document::factory()->create(['is_published' => false]);
    $notSelected = Document::factory()->create(['is_published' => false]);

    Livewire::test(DocumentIndex::class)
        ->set('selected', [$draft1->id, $draft2->id])
        ->call('bulkPublish');

    expect($draft1->refresh()->is_published)->toBeTrue()
        ->and($draft2->refresh()->is_published)->toBeTrue()
        ->and($notSelected->refresh()->is_published)->toBeFalse();
});

test('bulkUnpublish unpublishes only the selected published documents', function () {
    actingAsRoleForDocuments('Administrador');
    $published = Document::factory()->create(['is_published' => true]);
    $notSelected = Document::factory()->create(['is_published' => true]);

    Livewire::test(DocumentIndex::class)
        ->set('selected', [$published->id])
        ->call('bulkUnpublish');

    expect($published->refresh()->is_published)->toBeFalse()
        ->and($notSelected->refresh()->is_published)->toBeTrue();
});

test('a Defensor cannot bulk publish or bulk unpublish documents', function () {
    actingAsRoleForDocuments('Defensor');
    $draft = Document::factory()->create(['is_published' => false]);
    $published = Document::factory()->create(['is_published' => true]);

    Livewire::test(DocumentIndex::class)->set('selected', [$draft->id])->call('bulkPublish');
    Livewire::test(DocumentIndex::class)->set('selected', [$published->id])->call('bulkUnpublish');

    expect($draft->refresh()->is_published)->toBeFalse()
        ->and($published->refresh()->is_published)->toBeTrue();
});

test('bulkDelete removes every selected document and clears the selection', function () {
    actingAsRoleForDocuments('Administrador');
    $document1 = Document::factory()->create();
    $document2 = Document::factory()->create();

    $component = Livewire::test(DocumentIndex::class)
        ->set('selected', [$document1->id, $document2->id])
        ->call('bulkDelete');

    expect(Document::find($document1->id))->toBeNull()
        ->and(Document::find($document2->id))->toBeNull();
    $component->assertSet('selected', []);
});

test('a Defensor cannot bulk delete documents', function () {
    actingAsRoleForDocuments('Defensor');
    $document = Document::factory()->create();

    Livewire::test(DocumentIndex::class)->set('selected', [$document->id])->call('bulkDelete');

    expect(Document::find($document->id))->not->toBeNull();
});

test('toggleSelectAllOnPage selects then deselects every document on the current page', function () {
    actingAsRoleForDocuments('Administrador');
    Document::factory()->count(3)->create();

    $component = Livewire::test(DocumentIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(3);

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});

test('searching filters the documents table by title', function () {
    actingAsRoleForDocuments('Administrador');
    Document::factory()->create(['title' => 'Edital de Licitação']);
    Document::factory()->create(['title' => 'Outro Assunto']);

    Livewire::test(DocumentIndex::class)
        ->set('search', 'Licitação')
        ->assertSee('Edital de Licitação')
        ->assertDontSee('Outro Assunto');
});
