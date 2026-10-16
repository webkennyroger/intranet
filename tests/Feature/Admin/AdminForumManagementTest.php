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

test('bulkDelete removes every selected topic and clears the selection', function () {
    actingAsRoleForAdminForum('Administrador');
    $topic1 = ForumTopic::factory()->create();
    $topic2 = ForumTopic::factory()->create();

    $component = Livewire::test(AdminForumIndex::class)
        ->set('selected', [$topic1->id, $topic2->id])
        ->call('bulkDelete');

    expect(ForumTopic::find($topic1->id))->toBeNull()
        ->and(ForumTopic::find($topic2->id))->toBeNull();
    $component->assertSet('selected', []);
});

test('toggleSelectAllOnPage selects then deselects every topic on the current page', function () {
    actingAsRoleForAdminForum('Administrador');
    ForumTopic::factory()->count(3)->create();

    $component = Livewire::test(AdminForumIndex::class)->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(3);

    $component->call('toggleSelectAllOnPage');
    expect($component->get('selected'))->toHaveCount(0);
});

test('searching filters the forum topics table by title', function () {
    actingAsRoleForAdminForum('Administrador');
    ForumTopic::factory()->create(['title' => 'Dúvida sobre férias']);
    ForumTopic::factory()->create(['title' => 'Outro Assunto']);

    Livewire::test(AdminForumIndex::class)
        ->set('search', 'férias')
        ->assertSee('Dúvida sobre férias')
        ->assertDontSee('Outro Assunto');
});
