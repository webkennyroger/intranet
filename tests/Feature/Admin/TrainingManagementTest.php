<?php

use App\Livewire\Admin\Trainings\TrainingForm;
use App\Livewire\Admin\Trainings\TrainingIndex;
use App\Models\Training;
use App\Models\TrainingCategory;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForTrainings(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can view the trainings management page', function () {
    actingAsRoleForTrainings('Administrador');

    $this->get(route('admin.trainings.index'))->assertStatus(200);
});

test('creating a training saves its link and workload', function () {
    actingAsRoleForTrainings('Administrador');

    Livewire::test(TrainingForm::class)
        ->set('title', 'LGPD na Prática')
        ->set('links.0.url', 'https://cursos.exemplo.com/lgpd')
        ->set('workloadHours', '20')
        ->call('save')
        ->assertHasNoErrors();

    $training = Training::where('title', 'LGPD na Prática')->first();
    expect($training)->not->toBeNull()
        ->and($training->links)->toBe([['label' => null, 'url' => 'https://cursos.exemplo.com/lgpd']])
        ->and($training->workload_hours)->toBe(20);
});

test('creating a training without any link is rejected', function () {
    actingAsRoleForTrainings('Administrador');

    Livewire::test(TrainingForm::class)
        ->set('title', 'Sem Link')
        ->set('links.0.url', '')
        ->call('save')
        ->assertHasErrors(['links']);

    expect(Training::where('title', 'Sem Link')->exists())->toBeFalse();
});

test('creating a training with multiple links saves each one with its label', function () {
    actingAsRoleForTrainings('Administrador');

    Livewire::test(TrainingForm::class)
        ->set('title', 'Curso com Etapas')
        ->set('links.0.label', 'Módulo 1')
        ->set('links.0.url', 'https://cursos.exemplo.com/modulo-1')
        ->call('addLink')
        ->set('links.1.label', 'Módulo 2')
        ->set('links.1.url', 'https://cursos.exemplo.com/modulo-2')
        ->call('save')
        ->assertHasNoErrors();

    $training = Training::where('title', 'Curso com Etapas')->first();
    expect($training->links)->toBe([
        ['label' => 'Módulo 1', 'url' => 'https://cursos.exemplo.com/modulo-1'],
        ['label' => 'Módulo 2', 'url' => 'https://cursos.exemplo.com/modulo-2'],
    ]);
});

test('removeLink discards the link row and blank rows are ignored on save', function () {
    actingAsRoleForTrainings('Administrador');

    Livewire::test(TrainingForm::class)
        ->set('title', 'Curso com Linha Vazia')
        ->set('links.0.url', 'https://cursos.exemplo.com/unico')
        ->call('addLink')
        ->call('removeLink', 1)
        ->call('save')
        ->assertHasNoErrors();

    $training = Training::where('title', 'Curso com Linha Vazia')->first();
    expect($training->links)->toBe([['label' => null, 'url' => 'https://cursos.exemplo.com/unico']]);
});

test('creating a training with a non-existent category is rejected', function () {
    actingAsRoleForTrainings('Administrador');

    Livewire::test(TrainingForm::class)
        ->set('title', 'Capacitação Qualquer')
        ->set('links.0.url', 'https://cursos.exemplo.com/qualquer')
        ->set('trainingCategoryId', '999999')
        ->call('save')
        ->assertHasErrors(['trainingCategoryId']);

    expect(Training::where('title', 'Capacitação Qualquer')->exists())->toBeFalse();
});

test('creating a training with a valid category saves it', function () {
    actingAsRoleForTrainings('Administrador');
    $category = TrainingCategory::factory()->create();

    Livewire::test(TrainingForm::class)
        ->set('title', 'Capacitação com Pasta')
        ->set('links.0.url', 'https://cursos.exemplo.com/pasta')
        ->set('trainingCategoryId', (string) $category->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Training::where('title', 'Capacitação com Pasta')->first()->training_category_id)->toBe($category->id);
});

test('a Defensor cannot publish a training', function () {
    actingAsRoleForTrainings('Defensor');
    $training = Training::factory()->create(['is_published' => false]);

    Livewire::test(TrainingIndex::class)->call('togglePublish', $training->id);

    expect($training->refresh()->is_published)->toBeFalse();
});

test('an Administrador can delete a training', function () {
    actingAsRoleForTrainings('Administrador');
    $training = Training::factory()->create();

    Livewire::test(TrainingIndex::class)->call('delete', $training->id);

    expect(Training::find($training->id))->toBeNull();
});

test('bulkPublish publishes only the selected draft trainings and leaves others untouched', function () {
    actingAsRoleForTrainings('Administrador');
    $draft1 = Training::factory()->create(['is_published' => false]);
    $draft2 = Training::factory()->create(['is_published' => false]);
    $notSelected = Training::factory()->create(['is_published' => false]);

    Livewire::test(TrainingIndex::class)
        ->set('selected', [$draft1->id, $draft2->id])
        ->call('bulkPublish');

    expect($draft1->refresh()->is_published)->toBeTrue()
        ->and($draft2->refresh()->is_published)->toBeTrue()
        ->and($notSelected->refresh()->is_published)->toBeFalse();
});

test('bulkUnpublish unpublishes only the selected published trainings', function () {
    actingAsRoleForTrainings('Administrador');
    $published = Training::factory()->create(['is_published' => true]);
    $notSelected = Training::factory()->create(['is_published' => true]);

    Livewire::test(TrainingIndex::class)
        ->set('selected', [$published->id])
        ->call('bulkUnpublish');

    expect($published->refresh()->is_published)->toBeFalse()
        ->and($notSelected->refresh()->is_published)->toBeTrue();
});

test('a Defensor cannot bulk publish or bulk unpublish trainings', function () {
    actingAsRoleForTrainings('Defensor');
    $draft = Training::factory()->create(['is_published' => false]);
    $published = Training::factory()->create(['is_published' => true]);

    Livewire::test(TrainingIndex::class)->set('selected', [$draft->id])->call('bulkPublish');
    Livewire::test(TrainingIndex::class)->set('selected', [$published->id])->call('bulkUnpublish');

    expect($draft->refresh()->is_published)->toBeFalse()
        ->and($published->refresh()->is_published)->toBeTrue();
});

test('bulkDelete removes every selected training and clears the selection', function () {
    actingAsRoleForTrainings('Administrador');
    $training1 = Training::factory()->create();
    $training2 = Training::factory()->create();

    $component = Livewire::test(TrainingIndex::class)
        ->set('selected', [$training1->id, $training2->id])
        ->call('bulkDelete');

    expect(Training::find($training1->id))->toBeNull()
        ->and(Training::find($training2->id))->toBeNull();
    $component->assertSet('selected', []);
});

test('a Defensor cannot bulk delete trainings', function () {
    actingAsRoleForTrainings('Defensor');
    $training = Training::factory()->create();

    Livewire::test(TrainingIndex::class)->set('selected', [$training->id])->call('bulkDelete');

    expect(Training::find($training->id))->not->toBeNull();
});

test('toggleSelectAllOnPage selects then deselects every training on the current page', function () {
    actingAsRoleForTrainings('Administrador');
    Training::factory()->count(3)->create();

    $component = Livewire::test(TrainingIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(3);

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});

test('searching filters the trainings table by title', function () {
    actingAsRoleForTrainings('Administrador');
    Training::factory()->create(['title' => 'Capacitação em LGPD']);
    Training::factory()->create(['title' => 'Outro Assunto']);

    Livewire::test(TrainingIndex::class)
        ->set('search', 'LGPD')
        ->assertSee('Capacitação em LGPD')
        ->assertDontSee('Outro Assunto');
});
