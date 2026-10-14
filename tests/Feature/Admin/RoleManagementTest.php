<?php

use App\Livewire\Admin\Roles\RoleIndex;
use App\Models\User;
use App\Services\PermissionManager;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function actingAsRoleForRoles(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('a Super Administrador can view the roles management page', function () {
    actingAsRoleForRoles('Super Administrador');

    $this->get(route('admin.roles.index'))->assertStatus(200);
});

test('an Administrador cannot view the roles management page', function (string $role) {
    actingAsRoleForRoles($role);

    $this->get(route('admin.roles.index'))->assertStatus(403);
})->with(['Administrador', 'Defensor']);

test('selecting a role loads its current permissions into the matrix', function () {
    actingAsRoleForRoles('Super Administrador');
    $role = Role::where('name', 'Defensor')->first();

    Livewire::test(RoleIndex::class)
        ->call('selectRole', $role->id)
        ->assertSet('selectedPermissions', $role->permissions->pluck('name')->all());
});

test('saving the matrix syncs the selected permissions onto the role and shows a success message', function () {
    actingAsRoleForRoles('Super Administrador');
    $role = Role::where('name', 'Defensor')->first();

    Livewire::test(RoleIndex::class)
        ->call('selectRole', $role->id)
        ->set('selectedPermissions', ['council.view', 'articles.view'])
        ->call('savePermissions')
        ->assertDispatched('toast');

    expect($role->refresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['articles.view', 'council.view']);
});

test('deselect all clears the matrix and select all grants every permission', function () {
    actingAsRoleForRoles('Super Administrador');
    $role = Role::where('name', 'Defensor')->first();

    $component = Livewire::test(RoleIndex::class)->call('selectRole', $role->id);

    $component->call('deselectAll')->assertSet('selectedPermissions', []);

    $component->call('selectAll');
    expect($component->get('selectedPermissions'))->toContain('roles.manage', 'users.delete');
});

test('the Super Administrador matrix is locked and cannot be changed', function () {
    actingAsRoleForRoles('Super Administrador');
    $superAdmin = Role::where('name', 'Super Administrador')->first();

    Livewire::test(RoleIndex::class)
        ->call('selectRole', $superAdmin->id)
        ->assertSet('selectedPermissions', $superAdmin->permissions->pluck('name')->all())
        ->call('deselectAll')
        ->call('savePermissions');

    expect($superAdmin->refresh()->permissions->count())
        ->toBe(count(PermissionManager::inventory()));
});

test('savePermissions requires the roles.manage permission', function () {
    actingAsRoleForRoles('Administrador');
    $role = Role::where('name', 'Defensor')->first();
    $originalPermissions = $role->permissions->pluck('name')->sort()->values()->all();

    Livewire::test(RoleIndex::class)
        ->call('selectRole', $role->id)
        ->set('selectedPermissions', [])
        ->call('savePermissions');

    expect($role->refresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe($originalPermissions);
});

test('a Super Administrador can create a custom role', function () {
    actingAsRoleForRoles('Super Administrador');

    Livewire::test(RoleIndex::class)
        ->call('newRole')
        ->set('roleName', 'Editor de Conteúdo')
        ->set('roleDescription', 'Edita o Mural sem publicar.')
        ->call('saveRole')
        ->assertHasNoErrors();

    $role = Role::where('name', 'Editor de Conteúdo')->first();
    expect($role)->not->toBeNull()
        ->and($role->description)->toBe('Edita o Mural sem publicar.')
        ->and($role->permissions)->toHaveCount(0);
});

test('creating a role with a duplicate name is rejected', function () {
    actingAsRoleForRoles('Super Administrador');

    Livewire::test(RoleIndex::class)
        ->call('newRole')
        ->set('roleName', 'Administrador')
        ->call('saveRole')
        ->assertHasErrors(['roleName']);
});

test('editing a custom role updates its name and description', function () {
    actingAsRoleForRoles('Super Administrador');
    $role = Role::create(['name' => 'Revisor', 'guard_name' => 'web']);

    Livewire::test(RoleIndex::class)
        ->call('editRole', $role->id)
        ->assertSet('roleName', 'Revisor')
        ->set('roleName', 'Revisor Sênior')
        ->set('roleDescription', 'Revisa conteúdo antes da publicação.')
        ->call('saveRole')
        ->assertHasNoErrors();

    expect($role->refresh()->name)->toBe('Revisor Sênior')
        ->and($role->description)->toBe('Revisa conteúdo antes da publicação.');
});

test('a custom role with no members can be deleted', function () {
    actingAsRoleForRoles('Super Administrador');
    $role = Role::create(['name' => 'Temporário', 'guard_name' => 'web']);

    Livewire::test(RoleIndex::class)->call('deleteRole', $role->id);

    expect(Role::find($role->id))->toBeNull();
});

test('a built-in role cannot be deleted', function (string $roleName) {
    actingAsRoleForRoles('Super Administrador');
    $role = Role::where('name', $roleName)->first();

    Livewire::test(RoleIndex::class)
        ->call('deleteRole', $role->id)
        ->assertHasErrors(['delete']);

    expect(Role::find($role->id))->not->toBeNull();
})->with(['Super Administrador', 'Administrador', 'Defensor']);

test('a custom role with members assigned cannot be deleted', function () {
    actingAsRoleForRoles('Super Administrador');
    $role = Role::create(['name' => 'Com Membro', 'guard_name' => 'web']);
    User::factory()->create()->assignRole($role);

    Livewire::test(RoleIndex::class)
        ->call('deleteRole', $role->id)
        ->assertHasErrors(['delete']);

    expect(Role::find($role->id))->not->toBeNull();
});
