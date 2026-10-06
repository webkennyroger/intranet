<?php

use App\Livewire\Admin\ActivityLogIndex;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForActivityLog(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('a Super Administrador can view the activity log page', function () {
    actingAsRoleForActivityLog('Super Administrador');

    $this->get(route('admin.activity-log.index'))->assertStatus(200);
});

test('an Administrador is forbidden from the activity log page', function () {
    actingAsRoleForActivityLog('Administrador');

    $this->get(route('admin.activity-log.index'))->assertStatus(403);
});

test('a Defensor is forbidden from the activity log page', function () {
    actingAsRoleForActivityLog('Defensor');

    $this->get(route('admin.activity-log.index'))->assertStatus(403);
});

test('the list shows every log entry ordered by newest first', function () {
    $admin = actingAsRoleForActivityLog('Super Administrador');
    $article = Article::factory()->create();
    $older = ActivityLog::create(['action' => 'created', 'description' => 'Primeiro Registro', 'user_id' => $admin->id, 'subject_type' => Article::class, 'subject_id' => $article->id]);
    $older->forceFill(['created_at' => now()->subDay()])->saveQuietly();
    $newer = ActivityLog::create(['action' => 'updated', 'description' => 'Segundo Registro', 'user_id' => $admin->id, 'subject_type' => Article::class, 'subject_id' => $article->id]);
    $newer->forceFill(['created_at' => now()])->saveQuietly();

    Livewire::test(ActivityLogIndex::class)
        ->assertSeeInOrder(['Segundo Registro', 'Primeiro Registro']);
});

test('search filters by description', function () {
    $admin = actingAsRoleForActivityLog('Super Administrador');
    ActivityLog::create(['action' => 'created', 'description' => 'Mural "Boletim" criado(a)', 'user_id' => $admin->id]);
    ActivityLog::create(['action' => 'created', 'description' => 'Documento "Edital" criado(a)', 'user_id' => $admin->id]);

    Livewire::test(ActivityLogIndex::class)
        ->set('search', 'Boletim')
        ->assertSee('Boletim')
        ->assertDontSee('Edital');
});

test('action filter narrows the list to a single action', function () {
    $admin = actingAsRoleForActivityLog('Super Administrador');
    ActivityLog::create(['action' => 'created', 'description' => 'Criação aqui', 'user_id' => $admin->id]);
    ActivityLog::create(['action' => 'deleted', 'description' => 'Exclusão aqui', 'user_id' => $admin->id]);

    Livewire::test(ActivityLogIndex::class)
        ->call('setActionFilter', 'created')
        ->assertSee('Criação aqui')
        ->assertDontSee('Exclusão aqui');
});

test('type filter narrows the list to a single subject type', function () {
    $admin = actingAsRoleForActivityLog('Super Administrador');
    $article = Article::factory()->create();
    ActivityLog::create(['action' => 'created', 'description' => 'No mural', 'user_id' => $admin->id, 'subject_type' => Article::class, 'subject_id' => $article->id]);
    ActivityLog::create(['action' => 'created', 'description' => 'No usuario', 'user_id' => $admin->id, 'subject_type' => User::class, 'subject_id' => $admin->id]);

    Livewire::test(ActivityLogIndex::class)
        ->set('typeFilter', Article::class)
        ->assertSee('No mural')
        ->assertDontSee('No usuario');
});

test('opening a log detail exposes it to the view', function () {
    $admin = actingAsRoleForActivityLog('Super Administrador');
    $log = ActivityLog::create(['action' => 'created', 'description' => 'Detalhe aqui', 'user_id' => $admin->id]);

    Livewire::test(ActivityLogIndex::class)
        ->call('openDetail', $log->id)
        ->assertSet('selectedLogId', $log->id)
        ->assertSee('Detalhe aqui');
});
