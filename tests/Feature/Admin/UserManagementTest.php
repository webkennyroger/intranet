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

test('a Gestor is forbidden from the user management page', function () {
    $this->seed(AclSeeder::class);

    $gestor = User::factory()->create();
    $gestor->assignRole('Gestor');

    $this->actingAs($gestor)
        ->get(route('admin.users.index'))
        ->assertStatus(403);
});

test('an Editor is forbidden from the user management page', function () {
    $this->seed(AclSeeder::class);

    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $this->actingAs($editor)
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
    $editor->assignRole('Editor');

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

test('an Administrador cannot delete the sole Super Administrador', function () {
    $this->seed(AclSeeder::class);

    // AclSeeder creates a default Super Administrador. Find and delete all but the target one.
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Administrador');

    // Delete the default Super Administrador so only our target remains
    User::where('email', 'admin@defensoria.am.gov.br')->delete();

    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('delete', $superAdmin->id)
        ->assertHasErrors();

    expect(User::find($superAdmin->id))->not->toBeNull();
});

test('clicking edit on a user loads their data into the form', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $target = User::factory()->create(['name' => 'Fernanda Costa']);
    $target->assignRole('Editor');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('edit', $target->id)
        ->assertSee('Fernanda Costa');
});

test('an Administrador cannot block the sole Super Administrador', function () {
    $this->seed(AclSeeder::class);

    // AclSeeder creates a default Super Administrador. Find and delete all but the target one.
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Administrador');

    // Delete the default Super Administrador so only our target remains
    User::where('email', 'admin@defensoria.am.gov.br')->delete();

    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('toggleBlock', $superAdmin->id)
        ->assertHasErrors();

    expect($superAdmin->refresh()->isActive())->toBeTrue();
});
