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
        ->set('file', UploadedFile::fake()->create('regimento.pdf', 100))
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
        ->assertHasErrors(['file']);

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
