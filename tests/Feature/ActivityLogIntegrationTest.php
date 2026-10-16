<?php

use App\Livewire\Admin\Articles\ArticleForm;
use App\Livewire\Admin\Articles\ArticleIndex;
use App\Livewire\Admin\Council\CouncilMemberForm;
use App\Livewire\Admin\Council\CouncilMemberIndex;
use App\Livewire\Admin\Documents\DocumentForm;
use App\Livewire\Admin\Documents\DocumentIndex;
use App\Livewire\Admin\Events\EventForm;
use App\Livewire\Admin\Events\EventIndex;
use App\Livewire\Admin\Forum\AdminForumIndex;
use App\Livewire\Admin\Photos\PhotoAlbumForm;
use App\Livewire\Admin\Photos\PhotoAlbumIndex;
use App\Livewire\Admin\Services\ServiceLinkForm;
use App\Livewire\Admin\Services\ServiceLinkIndex;
use App\Livewire\Admin\Trainings\TrainingForm;
use App\Livewire\Admin\Trainings\TrainingIndex;
use App\Livewire\Admin\Users\UserForm;
use App\Livewire\Admin\Users\UserIndex;
use App\Livewire\Admin\Videos\VideoForm;
use App\Livewire\Admin\Videos\VideoIndex;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\CouncilMember;
use App\Models\Document;
use App\Models\Event;
use App\Models\ForumTopic;
use App\Models\PhotoAlbum;
use App\Models\ServiceLink;
use App\Models\Training;
use App\Models\User;
use App\Models\Video;
use Database\Seeders\AclSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

function actingAsSuperAdminForLogging(): User
{
    test()->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    test()->actingAs($admin);

    return $admin;
}

test('creating, unpublishing and republishing an article logs each action', function () {
    actingAsSuperAdminForLogging();

    Livewire::test(ArticleForm::class)
        ->set('title', 'Nota de Imprensa')
        ->set('excerpt', 'Resumo')
        ->set('body', '<p>Corpo</p>')
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save');

    // Artigo novo já entra publicado, então o log de criação já basta aqui.
    $article = Article::where('title', 'Nota de Imprensa')->first();
    expect($article->is_published)->toBeTrue()
        ->and(ActivityLog::where('action', 'created')->where('subject_id', $article->id)->exists())->toBeTrue();

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);
    expect(ActivityLog::where('action', 'deactivated')->where('subject_id', $article->id)->exists())->toBeTrue();

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);
    expect(ActivityLog::where('action', 'activated')->where('subject_id', $article->id)->exists())->toBeTrue();

    Livewire::test(ArticleIndex::class)->call('delete', $article->id);
    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $article->id)->exists())->toBeTrue();
});

test('updating an article logs the changed fields', function () {
    actingAsSuperAdminForLogging();
    $article = Article::factory()->create(['title' => 'Antigo']);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->set('title', 'Novo')
        ->call('save');

    $log = ActivityLog::where('action', 'updated')->where('subject_id', $article->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->changes['title'])->toBe(['old' => 'Antigo', 'new' => 'Novo']);
});

test('document create, publish toggle and delete are logged', function () {
    actingAsSuperAdminForLogging();

    Livewire::test(DocumentForm::class)
        ->set('title', 'Edital')
        ->set('files', [UploadedFile::fake()->create('edital.pdf', 10)])
        ->call('save');

    $document = Document::where('title', 'Edital')->first();
    expect(ActivityLog::where('action', 'created')->where('subject_id', $document->id)->exists())->toBeTrue();

    Livewire::test(DocumentIndex::class)->call('togglePublish', $document->id);
    expect(ActivityLog::whereIn('action', ['activated', 'deactivated'])->where('subject_id', $document->id)->exists())->toBeTrue();

    Livewire::test(DocumentIndex::class)->call('delete', $document->id);
    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $document->id)->exists())->toBeTrue();
});

test('council member create, activation toggle and delete are logged', function () {
    actingAsSuperAdminForLogging();

    Livewire::test(CouncilMemberForm::class)
        ->set('name', 'Maria Silva')
        ->set('role', 'Presidente')
        ->call('save');

    $member = CouncilMember::where('name', 'Maria Silva')->first();
    expect(ActivityLog::where('action', 'created')->where('subject_id', $member->id)->exists())->toBeTrue();

    Livewire::test(CouncilMemberIndex::class)->call('toggleActive', $member->id);
    expect(ActivityLog::where('action', 'deactivated')->where('subject_id', $member->id)->exists())->toBeTrue();

    Livewire::test(CouncilMemberIndex::class)->call('delete', $member->id);
    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $member->id)->exists())->toBeTrue();
});

test('service link create, activation toggle and delete are logged', function () {
    actingAsSuperAdminForLogging();

    Livewire::test(ServiceLinkForm::class)
        ->set('title', 'Vade Mecum')
        ->set('icon', 'book-open')
        ->set('url', 'https://example.com')
        ->call('save');

    $link = ServiceLink::where('title', 'Vade Mecum')->first();
    expect(ActivityLog::where('action', 'created')->where('subject_id', $link->id)->exists())->toBeTrue();

    Livewire::test(ServiceLinkIndex::class)->call('toggleActive', $link->id);
    expect(ActivityLog::where('action', 'deactivated')->where('subject_id', $link->id)->exists())->toBeTrue();

    Livewire::test(ServiceLinkIndex::class)->call('delete', $link->id);
    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $link->id)->exists())->toBeTrue();
});

test('photo album create, publish toggle and delete are logged', function () {
    actingAsSuperAdminForLogging();

    Livewire::test(PhotoAlbumForm::class)
        ->set('title', 'Confraternização')
        ->call('save');

    $album = PhotoAlbum::where('title', 'Confraternização')->first();
    expect(ActivityLog::where('action', 'created')->where('subject_id', $album->id)->exists())->toBeTrue();

    Livewire::test(PhotoAlbumIndex::class)->call('togglePublish', $album->id);
    expect(ActivityLog::whereIn('action', ['activated', 'deactivated'])->where('subject_id', $album->id)->exists())->toBeTrue();

    Livewire::test(PhotoAlbumIndex::class)->call('delete', $album->id);
    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $album->id)->exists())->toBeTrue();
});

test('video create, publish toggle and delete are logged', function () {
    actingAsSuperAdminForLogging();

    Livewire::test(VideoForm::class)
        ->set('title', 'Boas Práticas')
        ->set('url', 'https://www.youtube.com/watch?v=abc123')
        ->call('save');

    $video = Video::where('title', 'Boas Práticas')->first();
    expect(ActivityLog::where('action', 'created')->where('subject_id', $video->id)->exists())->toBeTrue();

    Livewire::test(VideoIndex::class)->call('togglePublish', $video->id);
    expect(ActivityLog::whereIn('action', ['activated', 'deactivated'])->where('subject_id', $video->id)->exists())->toBeTrue();

    Livewire::test(VideoIndex::class)->call('delete', $video->id);
    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $video->id)->exists())->toBeTrue();
});

test('event create, publish toggle and delete are logged', function () {
    actingAsSuperAdminForLogging();

    Livewire::test(EventForm::class)
        ->set('title', 'Semana da Defensoria')
        ->set('startsAt', '2026-11-10T09:00')
        ->call('save');

    $event = Event::where('title', 'Semana da Defensoria')->first();
    expect(ActivityLog::where('action', 'created')->where('subject_id', $event->id)->exists())->toBeTrue();

    Livewire::test(EventIndex::class)->call('togglePublish', $event->id);
    expect(ActivityLog::whereIn('action', ['activated', 'deactivated'])->where('subject_id', $event->id)->exists())->toBeTrue();

    Livewire::test(EventIndex::class)->call('delete', $event->id);
    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $event->id)->exists())->toBeTrue();
});

test('training create, publish toggle and delete are logged', function () {
    actingAsSuperAdminForLogging();

    Livewire::test(TrainingForm::class)
        ->set('title', 'LGPD na Prática')
        ->set('links.0.url', 'https://cursos.exemplo.com/lgpd')
        ->call('save');

    $training = Training::where('title', 'LGPD na Prática')->first();
    expect(ActivityLog::where('action', 'created')->where('subject_id', $training->id)->exists())->toBeTrue();

    Livewire::test(TrainingIndex::class)->call('togglePublish', $training->id);
    expect(ActivityLog::whereIn('action', ['activated', 'deactivated'])->where('subject_id', $training->id)->exists())->toBeTrue();

    Livewire::test(TrainingIndex::class)->call('delete', $training->id);
    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $training->id)->exists())->toBeTrue();
});

test('creating a user, changing their role, blocking and deleting them are logged', function () {
    actingAsSuperAdminForLogging();

    Livewire::test(UserForm::class)
        ->set('name', 'Novo Usuário')
        ->set('username', 'novo.usuario')
        ->set('email', 'novo@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Administrador')
        ->call('save');

    $user = User::where('email', 'novo@defensoria.am.gov.br')->first();
    expect(ActivityLog::where('action', 'created')->where('subject_id', $user->id)->exists())->toBeTrue();

    Livewire::test(UserIndex::class)
        ->set("roleChoice.{$user->id}", 'Defensor')
        ->call('changeRole', $user->id);
    $roleLog = ActivityLog::where('action', 'updated')->where('subject_id', $user->id)->first();
    expect($roleLog)->not->toBeNull()
        ->and($roleLog->changes['role'])->toBe(['old' => 'Administrador', 'new' => 'Defensor']);

    Livewire::test(UserIndex::class)->call('toggleBlock', $user->id);
    expect(ActivityLog::where('action', 'deactivated')->where('subject_id', $user->id)->exists())->toBeTrue();

    Livewire::test(UserIndex::class)->call('delete', $user->id);
    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $user->id)->exists())->toBeTrue();
});

test('a user password is never recorded in the activity log changes', function () {
    actingAsSuperAdminForLogging();
    $user = User::factory()->create();

    Livewire::test(UserForm::class, ['user' => $user])
        ->set('password', 'nova-senha-123')
        ->call('save');

    $log = ActivityLog::where('subject_id', $user->id)->where('subject_type', User::class)->latest()->first();
    expect($log?->changes ?? [])->not->toHaveKey('password');
});

test('deleting a forum topic from the admin moderation page is logged', function () {
    actingAsSuperAdminForLogging();
    $topic = ForumTopic::factory()->create(['title' => 'Tópico Teste']);

    Livewire::test(AdminForumIndex::class)->call('delete', $topic->id);

    expect(ActivityLog::where('action', 'deleted')->where('subject_id', $topic->id)->exists())->toBeTrue();
});
