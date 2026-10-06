<?php

use App\Livewire\Admin\Council\CouncilMemberForm;
use App\Livewire\Admin\Council\CouncilMemberIndex;
use App\Models\CouncilMember;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForCouncil(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('a Defensor can view the council members management page', function () {
    actingAsRoleForCouncil('Defensor');

    $this->get(route('admin.council.index'))->assertStatus(200);
});

test('an Administrador is forbidden from the council members management page', function () {
    actingAsRoleForCouncil('Administrador');

    $this->get(route('admin.council.index'))->assertStatus(403);
});

test('creating a council member assigns it the next display order', function () {
    actingAsRoleForCouncil('Defensor');
    CouncilMember::factory()->create(['order' => 3]);

    Livewire::test(CouncilMemberForm::class)
        ->set('name', 'Maria Silva')
        ->set('role', 'Presidente')
        ->call('save')
        ->assertHasNoErrors();

    $member = CouncilMember::where('name', 'Maria Silva')->first();
    expect($member)->not->toBeNull()
        ->and($member->order)->toBe(4);
});

test('creating a council member without a name is rejected', function () {
    actingAsRoleForCouncil('Defensor');

    Livewire::test(CouncilMemberForm::class)
        ->set('name', '')
        ->set('role', 'Presidente')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('reordering council members updates each order to match the given sequence', function () {
    actingAsRoleForCouncil('Defensor');
    $first = CouncilMember::factory()->create(['order' => 1]);
    $second = CouncilMember::factory()->create(['order' => 2]);

    Livewire::test(CouncilMemberIndex::class)->call('reorder', [$second->id, $first->id]);

    expect($second->refresh()->order)->toBe(0)
        ->and($first->refresh()->order)->toBe(1);
});

test('a Defensor can delete a council member', function () {
    actingAsRoleForCouncil('Defensor');
    $member = CouncilMember::factory()->create();

    Livewire::test(CouncilMemberIndex::class)->call('delete', $member->id);

    expect(CouncilMember::find($member->id))->toBeNull();
});
