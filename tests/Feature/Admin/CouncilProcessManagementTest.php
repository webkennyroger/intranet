<?php

use App\Livewire\Admin\Council\CouncilMemberIndex;
use App\Livewire\Admin\Council\CouncilProcessForm;
use App\Models\CouncilProcess;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

test('creating a council process stores it without a link by default', function () {
    actingAsRoleForCouncil('Defensor');

    Livewire::test(CouncilProcessForm::class)
        ->set('seiNumber', '2026.0.000000001-0')
        ->set('subject', 'Assunto de teste')
        ->set('assignedTo', 'Fulano de Tal')
        ->set('interestedParty', 'Interessado de Teste')
        ->call('save')
        ->assertHasNoErrors();

    $process = CouncilProcess::where('sei_number', '2026.0.000000001-0')->first();
    expect($process)->not->toBeNull()
        ->and($process->subject)->toBe('Assunto de teste')
        ->and($process->external_url)->toBeNull()
        ->and($process->is_published)->toBeTrue();
});

test('creating a council process with a link stores it', function () {
    actingAsRoleForCouncil('Defensor');

    Livewire::test(CouncilProcessForm::class)
        ->set('seiNumber', '2026.0.000000002-1')
        ->set('subject', 'Assunto com link')
        ->set('assignedTo', 'Fulano de Tal')
        ->set('interestedParty', 'Interessado de Teste')
        ->set('externalUrl', 'https://sei.mt.gov.br/processo/123')
        ->call('save')
        ->assertHasNoErrors();

    $process = CouncilProcess::where('sei_number', '2026.0.000000002-1')->first();
    expect($process->external_url)->toBe('https://sei.mt.gov.br/processo/123');
});

test('an invalid external url is rejected', function () {
    actingAsRoleForCouncil('Defensor');

    Livewire::test(CouncilProcessForm::class)
        ->set('seiNumber', '2026.0.000000003-2')
        ->set('subject', 'Assunto')
        ->set('assignedTo', 'Fulano')
        ->set('interestedParty', 'Interessado')
        ->set('externalUrl', 'isso não é um link')
        ->call('save')
        ->assertHasErrors(['externalUrl']);

    expect(CouncilProcess::where('sei_number', '2026.0.000000003-2')->exists())->toBeFalse();
});

test('creating a council process without the required fields is rejected', function () {
    actingAsRoleForCouncil('Defensor');

    Livewire::test(CouncilProcessForm::class)
        ->set('seiNumber', '')
        ->call('save')
        ->assertHasErrors(['seiNumber', 'subject', 'assignedTo', 'interestedParty']);
});

test('editing an existing council process updates it', function () {
    actingAsRoleForCouncil('Defensor');
    $process = CouncilProcess::factory()->create(['subject' => 'Assunto Antigo']);

    Livewire::test(CouncilProcessForm::class, ['process' => $process])
        ->set('subject', 'Assunto Novo')
        ->call('save')
        ->assertHasNoErrors();

    expect($process->refresh()->subject)->toBe('Assunto Novo');
});

test('a non-Defensor cannot create or update a council process directly', function () {
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Administrador');
    test()->actingAs($user);

    Livewire::test(CouncilProcessForm::class)
        ->set('seiNumber', '2026.0.000000004-3')
        ->set('subject', 'Assunto')
        ->set('assignedTo', 'Fulano')
        ->set('interestedParty', 'Interessado')
        ->call('save')
        ->assertStatus(403);
});

test('toggling publish status flips a council process between published and draft', function () {
    actingAsRoleForCouncil('Defensor');
    $process = CouncilProcess::factory()->create(['is_published' => true]);

    Livewire::test(CouncilMemberIndex::class)->call('togglePublish', $process->id);
    expect($process->refresh()->is_published)->toBeFalse();

    Livewire::test(CouncilMemberIndex::class)->call('togglePublish', $process->id);
    expect($process->refresh()->is_published)->toBeTrue();
});

test('deleting a council process removes it', function () {
    actingAsRoleForCouncil('Defensor');
    $process = CouncilProcess::factory()->create();

    Livewire::test(CouncilMemberIndex::class)->call('deleteProcess', $process->id);

    expect(CouncilProcess::find($process->id))->toBeNull();
});

test('bulkPublish publishes only the selected draft processes and leaves others untouched', function () {
    actingAsRoleForCouncil('Defensor');
    $draft1 = CouncilProcess::factory()->create(['is_published' => false]);
    $draft2 = CouncilProcess::factory()->create(['is_published' => false]);
    $notSelected = CouncilProcess::factory()->create(['is_published' => false]);

    Livewire::test(CouncilMemberIndex::class)
        ->set('selectedProcesses', [$draft1->id, $draft2->id])
        ->call('bulkPublish');

    expect($draft1->refresh()->is_published)->toBeTrue()
        ->and($draft2->refresh()->is_published)->toBeTrue()
        ->and($notSelected->refresh()->is_published)->toBeFalse();
});

test('bulkDeleteProcesses removes every selected process and clears the selection', function () {
    actingAsRoleForCouncil('Defensor');
    $process1 = CouncilProcess::factory()->create();
    $process2 = CouncilProcess::factory()->create();

    $component = Livewire::test(CouncilMemberIndex::class)
        ->set('selectedProcesses', [$process1->id, $process2->id])
        ->call('bulkDeleteProcesses');

    expect(CouncilProcess::find($process1->id))->toBeNull()
        ->and(CouncilProcess::find($process2->id))->toBeNull();
    $component->assertSet('selectedProcesses', []);
});

test('toggleSelectAllProcessesOnPage selects then deselects every process on the current page', function () {
    actingAsRoleForCouncil('Defensor');
    CouncilProcess::factory()->count(3)->create();

    $component = Livewire::test(CouncilMemberIndex::class)->call('toggleSelectAllProcessesOnPage');
    expect($component->get('selectedProcesses'))->toHaveCount(3);

    $component->call('toggleSelectAllProcessesOnPage');
    expect($component->get('selectedProcesses'))->toHaveCount(0);
});

test('searching filters the council processes table by sei number, subject, assigned-to or interested party', function () {
    actingAsRoleForCouncil('Defensor');
    CouncilProcess::factory()->create(['sei_number' => '2026.0.000000005-4', 'subject' => 'Processo Especial']);
    CouncilProcess::factory()->create(['sei_number' => '2026.0.000000006-5', 'subject' => 'Outro Assunto']);

    Livewire::test(CouncilMemberIndex::class)
        ->set('processSearch', 'Especial')
        ->assertSee('Processo Especial')
        ->assertDontSee('Outro Assunto');
});

test('the council processes table is ordered by sei number, newest first', function () {
    actingAsRoleForCouncil('Defensor');
    CouncilProcess::factory()->create(['sei_number' => '2024.0.000000001-0']);
    CouncilProcess::factory()->create(['sei_number' => '2025.0.000000002-0']);

    $component = Livewire::test(CouncilMemberIndex::class);

    expect($component->instance()->processes->pluck('sei_number')->all())
        ->toBe(['2025.0.000000002-0', '2024.0.000000001-0']);
});
