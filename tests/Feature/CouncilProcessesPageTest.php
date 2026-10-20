<?php

use App\Livewire\Site\Council\CouncilIndex;
use App\Models\CouncilProcess;
use App\Models\User;
use Livewire\Livewire;

test('the processes section only shows published processes', function () {
    $user = User::factory()->create();
    CouncilProcess::factory()->create(['sei_number' => '2025.0.000000001-0', 'subject' => 'Processo Publicado', 'is_published' => true]);
    CouncilProcess::factory()->create(['sei_number' => '2025.0.000000002-1', 'subject' => 'Processo Rascunho', 'is_published' => false]);

    test()->actingAs($user);
    Livewire::test(CouncilIndex::class)
        ->assertSee('Processo Publicado')
        ->assertDontSee('Processo Rascunho');
});

test('a process with an external url renders the sei number as a link that opens in a new tab', function () {
    $user = User::factory()->create();
    CouncilProcess::factory()->create([
        'sei_number' => '2025.0.000000003-2',
        'external_url' => 'https://sei.mt.gov.br/processo/abc',
        'is_published' => true,
    ]);

    test()->actingAs($user);
    Livewire::test(CouncilIndex::class)
        ->assertSee('href="https://sei.mt.gov.br/processo/abc"', false)
        ->assertSee('target="_blank"', false);
});

test('a process without an external url renders its sei number as plain text, not a link', function () {
    $user = User::factory()->create();
    CouncilProcess::factory()->create([
        'sei_number' => '2025.0.000000004-3',
        'external_url' => null,
        'is_published' => true,
    ]);

    test()->actingAs($user);
    Livewire::test(CouncilIndex::class)
        ->assertSee('2025.0.000000004-3')
        ->assertDontSee('href="https://sei.mt.gov.br"', false);
});

test('the processes section shows the empty state when there are none', function () {
    $user = User::factory()->create();

    test()->actingAs($user);
    Livewire::test(CouncilIndex::class)->assertSee('Nenhum processo cadastrado por enquanto.');
});

test('searching filters the public processes table by sei number, subject, assigned-to or interested party', function () {
    $user = User::factory()->create();
    CouncilProcess::factory()->create(['sei_number' => '2025.0.000000005-6', 'subject' => 'Processo Especial', 'is_published' => true]);
    CouncilProcess::factory()->create(['sei_number' => '2025.0.000000006-7', 'subject' => 'Outro Assunto', 'is_published' => true]);

    test()->actingAs($user);
    Livewire::test(CouncilIndex::class)
        ->set('search', 'Especial')
        ->assertSee('Processo Especial')
        ->assertDontSee('Outro Assunto');
});

test('searching with no matches shows the search-specific empty state', function () {
    $user = User::factory()->create();

    test()->actingAs($user);
    Livewire::test(CouncilIndex::class)
        ->set('search', 'inexistente')
        ->assertSee('Nenhum processo encontrado para essa busca.');
});

test('clearing the filter resets the search field', function () {
    $user = User::factory()->create();

    test()->actingAs($user);
    Livewire::test(CouncilIndex::class)
        ->set('search', 'algo')
        ->call('clearFilters')
        ->assertSet('search', '');
});

test('the council page only shows the processes section, with no members section', function () {
    $user = User::factory()->create();

    test()->actingAs($user);
    Livewire::test(CouncilIndex::class)
        ->assertDontSee('Membros')
        ->assertSee('Processos');
});
