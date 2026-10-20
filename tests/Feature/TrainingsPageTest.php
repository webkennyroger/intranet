<?php

use App\Livewire\Site\Trainings\TrainingsIndex;
use App\Models\Training;
use App\Models\TrainingCategory;
use App\Models\User;
use Livewire\Livewire;

test('a guest is redirected to login from the trainings page', function () {
    $this->get(route('trainings.index'))->assertRedirect(route('login'));
});

test('a single link shows its custom label instead of the generic "Acessar"', function () {
    $user = User::factory()->create();
    Training::factory()->create([
        'title' => 'Curso com Rótulo',
        'is_published' => true,
        'links' => [['label' => 'Acessar o Sistema SEI', 'url' => 'https://example.com/sei']],
    ]);

    $this->actingAs($user)->get(route('trainings.index'))->assertSee('Acessar o Sistema SEI');
});

test('a single link without a label falls back to "Acessar"', function () {
    $user = User::factory()->create();
    Training::factory()->create([
        'title' => 'Curso sem Rótulo',
        'is_published' => true,
        'links' => [['label' => null, 'url' => 'https://example.com/sem-rotulo']],
    ]);

    $this->actingAs($user)->get(route('trainings.index'))->assertSee('Acessar');
});

test('only published trainings appear on the trainings page', function () {
    $user = User::factory()->create();
    Training::factory()->create(['title' => 'Publicado', 'is_published' => true]);
    Training::factory()->create(['title' => 'Rascunho', 'is_published' => false]);

    $response = $this->actingAs($user)->get(route('trainings.index'));

    $response->assertSee('Publicado')->assertDontSee('Rascunho');
});

test('searching by keyword filters the trainings list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Training::factory()->create(['title' => 'LGPD na Prática', 'is_published' => true]);
    Training::factory()->create(['title' => 'Outro Assunto', 'is_published' => true]);

    Livewire::test(TrainingsIndex::class)
        ->set('search', 'LGPD')
        ->assertSee('LGPD na Prática')
        ->assertDontSee('Outro Assunto');
});

test('filtering by category only shows trainings from that folder', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $treinamento = TrainingCategory::factory()->create(['name' => 'Treinamento']);
    $curso = TrainingCategory::factory()->create(['name' => 'Curso']);
    Training::factory()->create(['title' => 'Treinamento 01', 'is_published' => true, 'training_category_id' => $treinamento->id]);
    Training::factory()->create(['title' => 'Curso 02', 'is_published' => true, 'training_category_id' => $curso->id]);

    Livewire::test(TrainingsIndex::class)
        ->call('selectCategory', $treinamento->id)
        ->assertSee('Treinamento 01')
        ->assertDontSee('Curso 02');
});

test('selecting the same category again clears the category filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = TrainingCategory::factory()->create();

    Livewire::test(TrainingsIndex::class)
        ->call('selectCategory', $category->id)
        ->assertSet('categoryId', (string) $category->id)
        ->call('selectCategory', $category->id)
        ->assertSet('categoryId', '');
});

test('filtering by the last 7 days excludes older trainings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Training::factory()->create(['title' => 'Recente', 'is_published' => true, 'created_at' => now()->subDays(2)]);
    Training::factory()->create(['title' => 'Antigo', 'is_published' => true, 'created_at' => now()->subDays(20)]);

    Livewire::test(TrainingsIndex::class)
        ->set('period', '7d')
        ->assertSee('Recente')
        ->assertDontSee('Antigo');
});

test('clearing filters resets search, category and period', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = TrainingCategory::factory()->create();

    Livewire::test(TrainingsIndex::class)
        ->set('search', 'algo')
        ->set('period', '7d')
        ->call('selectCategory', $category->id)
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('period', '')
        ->assertSet('categoryId', '');
});

test('the trainings list paginates at 10 per page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Training::factory()->count(12)->sequence(fn ($sequence) => ['title' => "Capacitação {$sequence->index}"])->create(['is_published' => true]);

    $component = Livewire::test(TrainingsIndex::class);

    expect($component->instance()->trainings)
        ->toHaveCount(10)
        ->and($component->instance()->trainings->hasMorePages())->toBeTrue();
});

test('each folder shows the real count of published trainings inside it', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = TrainingCategory::factory()->create(['name' => 'Treinamento']);
    Training::factory()->create(['is_published' => true, 'training_category_id' => $category->id]);
    Training::factory()->create(['is_published' => true, 'training_category_id' => $category->id]);
    Training::factory()->create(['is_published' => false, 'training_category_id' => $category->id]);

    $response = $this->actingAs($user)->get(route('trainings.index'));

    $response->assertSee('Treinamento')->assertSee('2 capacitações');
});
