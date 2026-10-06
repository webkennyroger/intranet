<?php

use App\Livewire\Admin\Forum\AdminForumIndex;
use App\Models\ForumTopic;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForAdminForum(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Administrador can view the admin forum moderation page', function () {
    actingAsRoleForAdminForum('Administrador');

    $this->get(route('admin.forum.index'))->assertOk();
});

test('a Defensor cannot view the admin forum moderation page', function () {
    actingAsRoleForAdminForum('Defensor');

    $this->get(route('admin.forum.index'))->assertForbidden();
});

test('an Administrador can delete any topic from the moderation page', function () {
    actingAsRoleForAdminForum('Administrador');
    $topic = ForumTopic::factory()->create();

    Livewire::test(AdminForumIndex::class)->call('delete', $topic->id);

    expect(ForumTopic::find($topic->id))->toBeNull();
});
