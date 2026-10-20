<?php

use App\Livewire\Site\Mural\ArticleShow;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

test('a published article is viewable', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['title' => 'Artigo Publicado', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertStatus(200)->assertSee('Artigo Publicado');
});

test('the featured image opens the lightbox when clicked', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now(), 'featured_image' => 'articles/capa.jpg']);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertSee('open-lightbox', false)->assertSee('x-cloak', false);
});

test('visiting an article show page increments its views count', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now(), 'views_count' => 0]);

    $this->actingAs($user)->get(route('mural.show', $article));
    $this->actingAs($user)->get(route('mural.show', $article));

    expect($article->refresh()->views_count)->toBe(2);
});

test('a draft article returns 404 on the public show page', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['title' => 'Rascunho', 'is_published' => false]);

    $this->actingAs($user)->get(route('mural.show', $article))->assertStatus(404);
});

test('a guest is redirected to login from the article show page', function () {
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    $this->get(route('mural.show', $article))->assertRedirect(route('login'));
});

test('liking an article from the show page updates the like count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(ArticleShow::class, ['article' => $article])->call('toggleLike');

    expect($article->refresh()->isLikedBy($user))->toBeTrue();
});

test('a texto custom field whose value is a link renders its label as the clickable link, with a file-type icon', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create([
        'is_published' => true,
        'published_at' => now(),
        'custom_fields' => [
            ['type' => 'texto', 'label' => 'Manual explicativo diárias', 'value' => 'https://intranet.test/documentos/310-MANUAL.pdf'],
        ],
    ]);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertSee('href="https://intranet.test/documentos/310-MANUAL.pdf"', false)
        ->assertSee('fa-file-pdf', false)
        ->assertSee('Manual explicativo diárias')
        ->assertDontSeeText('https://intranet.test/documentos/310-MANUAL.pdf');
});

test('a plain texto custom field without a link value still renders as plain text', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create([
        'is_published' => true,
        'published_at' => now(),
        'custom_fields' => [
            ['type' => 'texto', 'label' => 'Observação', 'value' => 'Entregar até o dia 10.'],
        ],
    ]);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertSee('Entregar até o dia 10.')->assertDontSee('fa-file-pdf', false);
});

test('a logged-in user can comment on an article using their own identity', function () {
    $user = User::factory()->create();
    test()->actingAs($user);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(ArticleShow::class, ['article' => $article])
        ->set('newComment', 'Ótima notícia!')
        ->call('addComment')
        ->assertHasNoErrors();

    expect($article->comments)->toHaveCount(1)
        ->and($article->comments->first()->body)->toBe('Ótima notícia!')
        ->and($article->comments->first()->author_id)->toBe($user->id);
});

test('commenting without a body is rejected', function () {
    test()->actingAs(User::factory()->create());
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(ArticleShow::class, ['article' => $article])
        ->set('newComment', '')
        ->call('addComment')
        ->assertHasErrors(['newComment']);

    expect($article->comments)->toHaveCount(0);
});

test('a user can reply to a comment and it is nested under its parent', function () {
    $user = User::factory()->create();
    test()->actingAs($user);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);
    $comment = Comment::factory()->create(['article_id' => $article->id]);

    Livewire::test(ArticleShow::class, ['article' => $article])
        ->call('startReply', $comment->id)
        ->set('newReply', 'Concordo plenamente.')
        ->call('addReply')
        ->assertHasNoErrors();

    expect($comment->refresh()->replies)->toHaveCount(1)
        ->and($comment->replies->first()->body)->toBe('Concordo plenamente.')
        ->and($comment->replies->first()->author_id)->toBe($user->id);
});

test('a user can delete their own comment', function () {
    $user = User::factory()->create();
    test()->actingAs($user);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);
    $comment = Comment::factory()->create(['article_id' => $article->id, 'author_id' => $user->id]);

    Livewire::test(ArticleShow::class, ['article' => $article])->call('deleteComment', $comment->id);

    expect(Comment::find($comment->id))->toBeNull();
});

test('a super administrador can delete a comment left by someone else', function () {
    test()->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    test()->actingAs($admin);

    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);
    $comment = Comment::factory()->create(['article_id' => $article->id, 'author_id' => User::factory()->create()->id]);

    Livewire::test(ArticleShow::class, ['article' => $article])->call('deleteComment', $comment->id);

    expect(Comment::find($comment->id))->toBeNull();
});

test('an administrator (not super administrador) cannot delete a comment left by someone else', function () {
    test()->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');
    test()->actingAs($admin);

    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);
    $comment = Comment::factory()->create(['article_id' => $article->id, 'author_id' => User::factory()->create()->id]);

    Livewire::test(ArticleShow::class, ['article' => $article])
        ->call('deleteComment', $comment->id)
        ->assertStatus(403);

    expect(Comment::find($comment->id))->not->toBeNull();
});

test('the article spans the full width when there are no related articles or tags to show in the sidebar', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertDontSee('lg:col-span-2', false)->assertDontSee('Leia também')->assertDontSee('Explore por tags');
});

test('the sidebar tags widget links to the tag page and only lists tags from published articles', function () {
    $user = User::factory()->create();
    $concurso = Category::factory()->create(['name' => 'Concurso']);
    $rascunho = Category::factory()->create(['name' => 'Só Rascunho']);
    Article::factory()->hasAttached($concurso)->create(['is_published' => true, 'published_at' => now()]);
    Article::factory()->hasAttached($rascunho)->create(['is_published' => false]);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertSee('href="'.route('mural.tag', 'Concurso').'"', false)
        ->assertDontSee('Só Rascunho');
});

test('a regular user cannot delete a comment left by someone else', function () {
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Defensor');
    test()->actingAs($user);

    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);
    $comment = Comment::factory()->create(['article_id' => $article->id, 'author_id' => User::factory()->create()->id]);

    Livewire::test(ArticleShow::class, ['article' => $article])
        ->call('deleteComment', $comment->id)
        ->assertStatus(403);

    expect(Comment::find($comment->id))->not->toBeNull();
});
