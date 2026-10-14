<?php

use App\Livewire\Admin\Documents\DocumentIndex;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForDocumentCategories(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can create a document category', function () {
    actingAsRoleForDocumentCategories('Administrador');

    Livewire::test(DocumentIndex::class)
        ->set('categoryName', 'Portarias')
        ->set('categoryIcon', 'gavel')
        ->set('categoryColor', '#2563EB')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $category = DocumentCategory::where('name', 'Portarias')->first();
    expect($category)->not->toBeNull()
        ->and($category->slug)->toBe('portarias')
        ->and($category->icon)->toBe('gavel')
        ->and($category->color)->toBe('#2563EB');
});

test('creating a category with a name that already exists is rejected', function () {
    actingAsRoleForDocumentCategories('Administrador');
    DocumentCategory::factory()->create(['name' => 'Portarias', 'slug' => 'portarias']);

    Livewire::test(DocumentIndex::class)
        ->set('categoryName', 'Portarias')
        ->call('saveCategory')
        ->assertHasErrors(['categoryName']);

    expect(DocumentCategory::where('name', 'Portarias')->count())->toBe(1);
});

test('an Administrador can edit a document category', function () {
    actingAsRoleForDocumentCategories('Administrador');
    $category = DocumentCategory::factory()->create(['name' => 'Antigo', 'slug' => 'antigo', 'color' => '#000000']);

    Livewire::test(DocumentIndex::class)
        ->call('editCategory', $category->id)
        ->assertSet('categoryName', 'Antigo')
        ->set('categoryName', 'Novo Nome')
        ->set('categoryColor', '#FF0000')
        ->call('saveCategory')
        ->assertHasNoErrors();

    expect($category->refresh()->name)->toBe('Novo Nome')
        ->and($category->slug)->toBe('novo-nome')
        ->and($category->color)->toBe('#FF0000');
});

test('an Administrador can delete a document category and its documents become unassigned', function () {
    actingAsRoleForDocumentCategories('Administrador');
    $category = DocumentCategory::factory()->create();
    $document = Document::factory()->create(['document_category_id' => $category->id]);

    Livewire::test(DocumentIndex::class)->call('deleteCategory', $category->id);

    expect(DocumentCategory::find($category->id))->toBeNull()
        ->and($document->refresh()->document_category_id)->toBeNull();
});

test('a Defensor cannot create, edit or delete document categories', function () {
    actingAsRoleForDocumentCategories('Defensor');
    $category = DocumentCategory::factory()->create(['name' => 'Original']);

    Livewire::test(DocumentIndex::class)
        ->set('categoryName', 'Nova Pasta')
        ->call('saveCategory');

    expect(DocumentCategory::where('name', 'Nova Pasta')->exists())->toBeFalse();

    Livewire::test(DocumentIndex::class)->call('deleteCategory', $category->id);
    expect(DocumentCategory::find($category->id))->not->toBeNull();
});
