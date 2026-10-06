<?php

use App\Livewire\Admin\Trainings\TrainingForm;
use App\Livewire\Admin\Trainings\TrainingIndex;
use App\Models\Training;
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

test('creating a training saves its external link and workload', function () {
    actingAsRoleForTrainings('Administrador');

    Livewire::test(TrainingForm::class)
        ->set('title', 'LGPD na Prática')
        ->set('externalUrl', 'https://cursos.exemplo.com/lgpd')
        ->set('workloadHours', '20')
        ->call('save')
        ->assertHasNoErrors();

    $training = Training::where('title', 'LGPD na Prática')->first();
    expect($training)->not->toBeNull()
        ->and($training->external_url)->toBe('https://cursos.exemplo.com/lgpd')
        ->and($training->workload_hours)->toBe(20);
});

test('creating a training without an external link is rejected', function () {
    actingAsRoleForTrainings('Administrador');

    Livewire::test(TrainingForm::class)
        ->set('title', 'Sem Link')
        ->set('externalUrl', '')
        ->call('save')
        ->assertHasErrors(['externalUrl']);

    expect(Training::where('title', 'Sem Link')->exists())->toBeFalse();
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
