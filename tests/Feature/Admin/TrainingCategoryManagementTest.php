<?php

use App\Livewire\Admin\Trainings\TrainingIndex;
use App\Models\Training;
use App\Models\TrainingCategory;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForTrainingCategories(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can create a training category', function () {
    actingAsRoleForTrainingCategories('Administrador');

    Livewire::test(TrainingIndex::class)
        ->set('categoryName', 'Treinamento')
        ->set('categoryIcon', 'chalkboard-user')
        ->set('categoryColor', '#2563EB')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $category = TrainingCategory::where('name', 'Treinamento')->first();
    expect($category)->not->toBeNull()
        ->and($category->slug)->toBe('treinamento')
        ->and($category->icon)->toBe('chalkboard-user')
        ->and($category->color)->toBe('#2563EB');
});

test('creating a category with a name that already exists is rejected', function () {
    actingAsRoleForTrainingCategories('Administrador');
    TrainingCategory::factory()->create(['name' => 'Curso', 'slug' => 'curso']);

    Livewire::test(TrainingIndex::class)
        ->set('categoryName', 'Curso')
        ->call('saveCategory')
        ->assertHasErrors(['categoryName']);

    expect(TrainingCategory::where('name', 'Curso')->count())->toBe(1);
});

test('an Administrador can edit a training category', function () {
    actingAsRoleForTrainingCategories('Administrador');
    $category = TrainingCategory::factory()->create(['name' => 'Antigo', 'slug' => 'antigo', 'color' => '#000000']);

    Livewire::test(TrainingIndex::class)
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

test('an Administrador can delete a training category and its trainings become unassigned', function () {
    actingAsRoleForTrainingCategories('Administrador');
    $category = TrainingCategory::factory()->create();
    $training = Training::factory()->create(['training_category_id' => $category->id]);

    Livewire::test(TrainingIndex::class)->call('deleteCategory', $category->id);

    expect(TrainingCategory::find($category->id))->toBeNull()
        ->and($training->refresh()->training_category_id)->toBeNull();
});

test('a Defensor cannot create, edit or delete training categories', function () {
    actingAsRoleForTrainingCategories('Defensor');
    $category = TrainingCategory::factory()->create(['name' => 'Original']);

    Livewire::test(TrainingIndex::class)
        ->set('categoryName', 'Nova Pasta')
        ->call('saveCategory');

    expect(TrainingCategory::where('name', 'Nova Pasta')->exists())->toBeFalse();

    Livewire::test(TrainingIndex::class)->call('deleteCategory', $category->id);
    expect(TrainingCategory::find($category->id))->not->toBeNull();
});
