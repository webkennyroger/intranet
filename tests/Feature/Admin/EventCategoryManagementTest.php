<?php

use App\Livewire\Admin\Events\EventCategoryForm;
use App\Livewire\Admin\Events\EventIndex;
use App\Models\EventCategory;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForEventCategories(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('creating a category saves its name and color', function () {
    actingAsRoleForEventCategories('Administrador');

    Livewire::test(EventCategoryForm::class)
        ->set('name', 'Audiência')
        ->set('color', '#9333ea')
        ->call('save')
        ->assertHasNoErrors();

    $category = EventCategory::where('name', 'Audiência')->first();
    expect($category)->not->toBeNull()
        ->and($category->color)->toBe('#9333ea');
});

test('creating a category with an invalid color is rejected', function () {
    actingAsRoleForEventCategories('Administrador');

    Livewire::test(EventCategoryForm::class)
        ->set('name', 'Audiência')
        ->set('color', 'nao-e-um-hex')
        ->call('save')
        ->assertHasErrors(['color']);

    expect(EventCategory::where('name', 'Audiência')->exists())->toBeFalse();
});

test('editing a category updates its name and color', function () {
    actingAsRoleForEventCategories('Administrador');
    $category = EventCategory::factory()->create(['name' => 'Antiga', 'color' => '#111111']);

    Livewire::test(EventCategoryForm::class, ['category' => $category])
        ->set('name', 'Nova')
        ->set('color', '#222222')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->refresh()->name)->toBe('Nova')
        ->and($category->color)->toBe('#222222');
});

test('a Defensor cannot create or edit a category', function () {
    actingAsRoleForEventCategories('Defensor');

    Livewire::test(EventCategoryForm::class)
        ->set('name', 'Audiência')
        ->set('color', '#9333ea')
        ->call('save')
        ->assertStatus(403);

    expect(EventCategory::where('name', 'Audiência')->exists())->toBeFalse();
});

test('an Administrador can delete a category', function () {
    actingAsRoleForEventCategories('Administrador');
    $category = EventCategory::factory()->create();

    Livewire::test(EventIndex::class)->call('deleteCategory', $category->id);

    expect(EventCategory::find($category->id))->toBeNull();
});

test('a Defensor cannot delete a category', function () {
    actingAsRoleForEventCategories('Defensor');
    $category = EventCategory::factory()->create();

    Livewire::test(EventIndex::class)->call('deleteCategory', $category->id)->assertStatus(403);

    expect(EventCategory::find($category->id))->not->toBeNull();
});
