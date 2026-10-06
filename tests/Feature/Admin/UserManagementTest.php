<?php

use App\Livewire\Admin\Users\UserIndex;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

test('a Super Administrador can view the user management page', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertStatus(200)
        ->assertSeeLivewire(UserIndex::class);
});

test('a Defensor is forbidden from the user management page', function () {
    $this->seed(AclSeeder::class);

    $defensor = User::factory()->create();
    $defensor->assignRole('Defensor');

    $this->actingAs($defensor)
        ->get(route('admin.users.index'))
        ->assertStatus(403);
});

test('an Administrador is forbidden from the user management page', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertStatus(403);
});

test('the list shows every user with their role badge', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create(['name' => 'Carla Souza']);
    $admin->assignRole('Super Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->assertSee('Carla Souza');
});

test('deleting the sole Super Administrador is blocked', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('delete', $admin->id)
        ->assertHasErrors();

    expect(User::find($admin->id))->not->toBeNull();
});

test('an admin cannot delete their own account', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $second = User::factory()->create();
    $second->assignRole('Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('delete', $admin->id)
        ->assertHasErrors();

    expect(User::find($admin->id))->not->toBeNull();
});

test('an admin can delete a different, non-last-super-admin user', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $editor = User::factory()->create();
    $editor->assignRole('Defensor');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)->call('delete', $editor->id);

    expect(User::find($editor->id))->toBeNull();
});

test('blocking the sole Super Administrador is blocked', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('toggleBlock', $admin->id)
        ->assertHasErrors();

    expect($admin->refresh()->isActive())->toBeTrue();
});

test('an Administrador has no permission to delete users at all', function () {
    $this->seed(AclSeeder::class);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Administrador');

    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('delete', $superAdmin->id)
        ->assertForbidden();

    expect(User::find($superAdmin->id))->not->toBeNull();
});

test('clicking edit on a user loads their data into the form', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $target = User::factory()->create(['name' => 'Fernanda Costa']);
    $target->assignRole('Defensor');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('edit', $target->id)
        ->assertSee('Fernanda Costa');
});

test('an Administrador has no permission to block users at all', function () {
    $this->seed(AclSeeder::class);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Administrador');

    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('toggleBlock', $superAdmin->id)
        ->assertForbidden();

    expect($superAdmin->refresh()->isActive())->toBeTrue();
});

test('search filters the user list by name or email', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    User::factory()->create(['name' => 'Fernanda Costa', 'email' => 'fernanda@defensoria.am.gov.br']);
    User::factory()->create(['name' => 'Marcos Lima', 'email' => 'marcos@defensoria.am.gov.br']);

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->set('search', 'Fernanda')
        ->assertSee('Fernanda Costa')
        ->assertDontSee('Marcos Lima');
});

test('changing the role via the inline select updates the user role', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $editor = User::factory()->create();
    $editor->assignRole('Defensor');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->set("roleChoice.{$editor->id}", 'Administrador')
        ->call('changeRole', $editor->id)
        ->assertHasNoErrors();

    expect($editor->refresh()->hasRole('Administrador'))->toBeTrue();
});

test('changing the role to Defensor via the inline select updates the user role', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $target = User::factory()->create();
    $target->assignRole('Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->set("roleChoice.{$target->id}", 'Defensor')
        ->call('changeRole', $target->id)
        ->assertHasNoErrors();

    expect($target->refresh()->hasRole('Defensor'))->toBeTrue();
});

test('demoting the sole Super Administrador via the inline select is blocked', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');

    // Delete the default Super Administrador so only our target remains
    User::where('email', 'admin@defensoria.am.gov.br')->delete();

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->set("roleChoice.{$admin->id}", 'Administrador')
        ->call('changeRole', $admin->id)
        ->assertHasErrors(['role']);

    expect($admin->refresh()->hasRole('Super Administrador'))->toBeTrue();
});
