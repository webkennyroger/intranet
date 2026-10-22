<?php

use App\Livewire\Admin\Users\UserForm;
use App\Livewire\Admin\Users\UserIndex;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

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

test('a user can be assigned to a custom role created in Papéis & Permissões', function () {
    $this->seed(AclSeeder::class);
    Role::create(['name' => 'Editor de Conteúdo', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $target = User::factory()->create();
    $target->assignRole('Defensor');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->set("roleChoice.{$target->id}", 'Editor de Conteúdo')
        ->call('changeRole', $target->id)
        ->assertHasNoErrors();

    expect($target->refresh()->hasRole('Editor de Conteúdo'))->toBeTrue();
});

test('creating a user through the form can assign a custom role', function () {
    $this->seed(AclSeeder::class);
    Role::create(['name' => 'Editor de Conteúdo', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $this->actingAs($admin);

    Livewire::test(UserForm::class)
        ->set('name', 'Novo Editor')
        ->set('username', 'novoeditor')
        ->set('email', 'novoeditor@defensoria.am.gov.br')
        ->set('password', 'password123')
        ->set('role', 'Editor de Conteúdo')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'novoeditor@defensoria.am.gov.br')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('Editor de Conteúdo'))->toBeTrue();
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

test('bulkDelete removes selected users but skips the acting admin and the sole Super Administrador', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $editor1 = User::factory()->create();
    $editor1->assignRole('Defensor');
    $editor2 = User::factory()->create();
    $editor2->assignRole('Defensor');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->set('selected', [$admin->id, $editor1->id, $editor2->id])
        ->call('bulkDelete');

    expect(User::find($admin->id))->not->toBeNull()
        ->and(User::find($editor1->id))->toBeNull()
        ->and(User::find($editor2->id))->toBeNull();
});

test('bulkBlock blocks selected users but skips the acting admin and the sole Super Administrador', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $editor = User::factory()->create();
    $editor->assignRole('Defensor');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->set('selected', [$admin->id, $editor->id])
        ->call('bulkBlock');

    expect($admin->refresh()->isActive())->toBeTrue()
        ->and($editor->refresh()->isActive())->toBeFalse();
});

test('bulkUnblock unblocks only the selected blocked users', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $this->actingAs($admin);

    $blocked = User::factory()->create();
    $blocked->assignRole('Defensor');
    Livewire::test(UserIndex::class)->call('toggleBlock', $blocked->id);
    expect($blocked->refresh()->isActive())->toBeFalse();

    Livewire::test(UserIndex::class)
        ->set('selected', [$blocked->id])
        ->call('bulkUnblock');

    expect($blocked->refresh()->isActive())->toBeTrue();
});

test('an Administrador has no permission to bulk block or bulk delete users', function () {
    $this->seed(AclSeeder::class);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Administrador');
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)->set('selected', [$superAdmin->id])->call('bulkBlock')->assertForbidden();
    Livewire::test(UserIndex::class)->set('selected', [$superAdmin->id])->call('bulkDelete')->assertForbidden();

    expect(User::find($superAdmin->id))->not->toBeNull()
        ->and($superAdmin->refresh()->isActive())->toBeTrue();
});

test('an Administrador cannot create or update a user via UserForm, even by setting role to Super Administrador', function () {
    $this->seed(AclSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Administrador');
    $this->actingAs($admin);

    Livewire::test(UserForm::class)
        ->set('name', 'Invasor')
        ->set('username', 'invasor')
        ->set('email', 'invasor@example.com')
        ->set('password', 'password123')
        ->set('role', 'Super Administrador')
        ->call('save')
        ->assertForbidden();

    expect(User::where('email', 'invasor@example.com')->exists())->toBeFalse();
});

test('toggleSelectAllOnPage selects then deselects every user on the current page', function () {
    $this->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $this->actingAs($admin);
    User::factory()->count(2)->create();

    $component = Livewire::test(UserIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(User::count());

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});
