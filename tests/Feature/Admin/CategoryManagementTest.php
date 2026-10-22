<?php

use App\Livewire\Admin\Categories\CategoryIndex;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForCategories(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can view the categories management page', function () {
    actingAsRoleForCategories('Administrador');

    $this->get(route('admin.categories.index'))->assertStatus(200);
});

test('creating a category auto-generates the slug from the name', function () {
    actingAsRoleForCategories('Administrador');

    Livewire::test(CategoryIndex::class)
        ->set('name', 'Comunicados Importantes')
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::where('name', 'Comunicados Importantes')->first();
    expect($category)->not->toBeNull()
        ->and($category->slug)->toBe('comunicados-importantes');
});

test('a Defensor (no categories permission) cannot create, update or delete categories', function () {
    actingAsRoleForCategories('Defensor');
    $category = Category::factory()->create();

    Livewire::test(CategoryIndex::class)
        ->set('name', 'Tentativa Sem Permissão')
        ->call('save')
        ->assertForbidden();
    expect(Category::where('name', 'Tentativa Sem Permissão')->exists())->toBeFalse();

    Livewire::test(CategoryIndex::class)->call('delete', $category->id)->assertForbidden();
    expect(Category::find($category->id))->not->toBeNull();
});

test('manually editing the slug stops it from following the name', function () {
    actingAsRoleForCategories('Administrador');

    Livewire::test(CategoryIndex::class)
        ->set('name', 'Avisos')
        ->set('slug', 'avisos-customizado')
        ->set('name', 'Avisos Gerais')
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::where('slug', 'avisos-customizado')->first();
    expect($category)->not->toBeNull()
        ->and($category->name)->toBe('Avisos Gerais');
});

test('creating a category with a duplicate slug is rejected', function () {
    actingAsRoleForCategories('Administrador');
    Category::factory()->create(['slug' => 'duplicada']);

    Livewire::test(CategoryIndex::class)
        ->set('name', 'Outra')
        ->set('slug', 'duplicada')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('editing an existing category updates it and keeps its own slug valid', function () {
    actingAsRoleForCategories('Administrador');
    $category = Category::factory()->create(['name' => 'Antiga', 'slug' => 'antiga']);

    Livewire::test(CategoryIndex::class)
        ->call('edit', $category->id)
        ->assertSet('name', 'Antiga')
        ->set('name', 'Nova')
        ->set('description', 'Resumo novo')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->refresh()->name)->toBe('Nova')
        ->and($category->slug)->toBe('antiga')
        ->and($category->description)->toBe('Resumo novo');
});

test('deleting a category detaches it from its articles', function () {
    actingAsRoleForCategories('Administrador');
    $category = Category::factory()->create();
    $article = Article::factory()->hasAttached($category)->create();

    Livewire::test(CategoryIndex::class)->call('delete', $category->id);

    expect(Category::find($category->id))->toBeNull()
        ->and($article->refresh()->categories)->toHaveCount(0);
});

test('an article keeps its other categories when only one of them is deleted', function () {
    actingAsRoleForCategories('Administrador');
    $deleted = Category::factory()->create();
    $kept = Category::factory()->create();
    $article = Article::factory()->create();
    $article->categories()->attach([$deleted->id, $kept->id]);

    Livewire::test(CategoryIndex::class)->call('delete', $deleted->id);

    expect($article->refresh()->categories->pluck('id')->all())->toBe([$kept->id]);
});

test('the category index shows the real article count per category', function () {
    actingAsRoleForCategories('Administrador');
    $category = Category::factory()->create();
    Article::factory()->count(3)->hasAttached($category)->create();

    $response = $this->get(route('admin.categories.index'));

    $response->assertSee($category->name)->assertSee('3 posts');
});

test('creating a category stores the chosen color', function () {
    actingAsRoleForCategories('Administrador');

    Livewire::test(CategoryIndex::class)
        ->set('name', 'Comunicados')
        ->set('color', '#2563EB')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::where('name', 'Comunicados')->first()->color)->toBe('#2563EB');
});

test('an invalid color is rejected', function () {
    actingAsRoleForCategories('Administrador');

    Livewire::test(CategoryIndex::class)
        ->set('name', 'Comunicados')
        ->set('color', 'not-a-color')
        ->call('save')
        ->assertHasErrors(['color']);
});

test('editing a category loads its existing color', function () {
    actingAsRoleForCategories('Administrador');
    $category = Category::factory()->create(['color' => '#059669']);

    Livewire::test(CategoryIndex::class)
        ->call('edit', $category->id)
        ->assertSet('color', '#059669');
});

test('the category index page does not render the slug field or value', function () {
    actingAsRoleForCategories('Administrador');
    $category = Category::factory()->create(['slug' => 'identificador-unico-de-teste']);

    $response = $this->get(route('admin.categories.index'));

    $response->assertDontSee('identificador-unico-de-teste');
});
