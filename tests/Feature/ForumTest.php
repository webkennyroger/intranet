<?php

use App\Livewire\Forum\ForumIndex;
use App\Livewire\Forum\ForumShow;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

test('any authenticated user can view the forum', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('forum.index'))->assertOk();
});

test('any authenticated user can create a topic', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(ForumIndex::class)
        ->set('title', 'Dúvida sobre férias')
        ->set('body', 'Como funciona o processo de solicitação de férias?')
        ->call('createTopic')
        ->assertHasNoErrors();

    expect(ForumTopic::where('title', 'Dúvida sobre férias')->first())
        ->not->toBeNull()
        ->author_id->toBe($user->id);
});

test('any authenticated user can reply to a topic', function () {
    $user = User::factory()->create();
    $topic = ForumTopic::factory()->create();

    Livewire::actingAs($user)->test(ForumShow::class, ['topic' => $topic])
        ->set('replyBody', 'Também tenho essa dúvida.')
        ->call('addReply')
        ->assertHasNoErrors();

    expect($topic->replies()->where('author_id', $user->id)->exists())->toBeTrue();
});

test('a user cannot edit another user\'s reply', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $topic = ForumTopic::factory()->create();
    $reply = ForumReply::factory()->for($topic, 'topic')->for($otherUser, 'author')->create();

    Livewire::actingAs($user)->test(ForumShow::class, ['topic' => $topic])
        ->call('editReply', $reply->id)
        ->assertSet('editingReplyId', null);
});

test('a user can delete their own topic', function () {
    $user = User::factory()->create();
    $topic = ForumTopic::factory()->for($user, 'author')->create();

    Livewire::actingAs($user)->test(ForumShow::class, ['topic' => $topic])
        ->call('deleteTopic');

    expect(ForumTopic::find($topic->id))->toBeNull();
});

test('a user cannot delete another user\'s topic', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $topic = ForumTopic::factory()->for($otherUser, 'author')->create();

    Livewire::actingAs($user)->test(ForumShow::class, ['topic' => $topic])
        ->call('deleteTopic');

    expect(ForumTopic::find($topic->id))->not->toBeNull();
});

test('an Administrador can delete any topic as a moderator', function () {
    $this->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');
    $otherUser = User::factory()->create();
    $topic = ForumTopic::factory()->for($otherUser, 'author')->create();

    Livewire::actingAs($admin)->test(ForumShow::class, ['topic' => $topic])
        ->call('deleteTopic');

    expect(ForumTopic::find($topic->id))->toBeNull();
});
