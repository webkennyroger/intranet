<?php

use App\Livewire\Admin\Council\CouncilMemberForm;
use App\Livewire\Admin\Council\CouncilMemberIndex;
use App\Models\CouncilMember;
use App\Models\CouncilProcess;
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

    Livewire::test(CouncilMemberIndex::class)->call('deleteMember', $member->id);

    expect(CouncilMember::find($member->id))->toBeNull();
});

test('bulkActivate activates only the selected inactive members and leaves others untouched', function () {
    actingAsRoleForCouncil('Defensor');
    $inactive1 = CouncilMember::factory()->create(['is_active' => false]);
    $inactive2 = CouncilMember::factory()->create(['is_active' => false]);
    $notSelected = CouncilMember::factory()->create(['is_active' => false]);

    Livewire::test(CouncilMemberIndex::class)
        ->set('selectedMembers', [$inactive1->id, $inactive2->id])
        ->call('bulkActivate');

    expect($inactive1->refresh()->is_active)->toBeTrue()
        ->and($inactive2->refresh()->is_active)->toBeTrue()
        ->and($notSelected->refresh()->is_active)->toBeFalse();
});

test('bulkDeactivate deactivates only the selected active members', function () {
    actingAsRoleForCouncil('Defensor');
    $active = CouncilMember::factory()->create(['is_active' => true]);
    $notSelected = CouncilMember::factory()->create(['is_active' => true]);

    Livewire::test(CouncilMemberIndex::class)
        ->set('selectedMembers', [$active->id])
        ->call('bulkDeactivate');

    expect($active->refresh()->is_active)->toBeFalse()
        ->and($notSelected->refresh()->is_active)->toBeTrue();
});

test('an Administrador (no council permission) cannot bulk activate, deactivate or delete council members', function () {
    actingAsRoleForCouncil('Administrador');
    $member = CouncilMember::factory()->create(['is_active' => false]);

    Livewire::test(CouncilMemberIndex::class)->set('selectedMembers', [$member->id])->call('bulkActivate');
    expect($member->refresh()->is_active)->toBeFalse();

    Livewire::test(CouncilMemberIndex::class)->set('selectedMembers', [$member->id])->call('bulkDeleteMembers');
    expect(CouncilMember::find($member->id))->not->toBeNull();
});

test('bulkDeleteMembers removes every selected council member and clears the selection', function () {
    actingAsRoleForCouncil('Defensor');
    $member1 = CouncilMember::factory()->create();
    $member2 = CouncilMember::factory()->create();

    $component = Livewire::test(CouncilMemberIndex::class)
        ->set('selectedMembers', [$member1->id, $member2->id])
        ->call('bulkDeleteMembers');

    expect(CouncilMember::find($member1->id))->toBeNull()
        ->and(CouncilMember::find($member2->id))->toBeNull();
    $component->assertSet('selectedMembers', []);
});

test('toggleSelectAllMembersOnPage selects then deselects every council member', function () {
    actingAsRoleForCouncil('Defensor');
    CouncilMember::factory()->count(3)->create();

    $component = Livewire::test(CouncilMemberIndex::class)->call('toggleSelectAllMembersOnPage');
    expect($component->get('selectedMembers'))->toHaveCount(3);

    $component->call('toggleSelectAllMembersOnPage');
    expect($component->get('selectedMembers'))->toHaveCount(0);
});

test('the council admin page only shows the processes section, members management stays hidden', function () {
    actingAsRoleForCouncil('Defensor');
    CouncilMember::factory()->create(['name' => 'Membro Teste']);
    CouncilProcess::factory()->create(['subject' => 'Processo Teste']);

    Livewire::test(CouncilMemberIndex::class)
        ->assertDontSee('Membro Teste')
        ->assertSee('Processo Teste');
});
