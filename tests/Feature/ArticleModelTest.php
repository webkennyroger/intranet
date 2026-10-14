<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\QueryException;

test('an article can be created with the expected fields', function () {
    $category = Category::factory()->create();
    $author = User::factory()->create();

    $article = Article::create([
        'author_id' => $author->id,
        'title' => 'Teste',
        'excerpt' => 'Resumo',
        'body' => '<p>Conteúdo</p>',
        'cover_color' => '#064e3b',
        'cover_icon' => 'newspaper',
        'is_published' => false,
    ]);
    $article->categories()->attach($category);

    expect($article->title)->toBe('Teste')
        ->and($article->categories->first()->id)->toBe($category->id)
        ->and($article->author->id)->toBe($author->id);
});

test('an article can belong to more than one category', function () {
    $mural = Category::factory()->create(['name' => 'Mural']);
    $noticia = Category::factory()->create(['name' => 'Notícia']);
    $article = Article::factory()->create();

    $article->categories()->attach([$mural->id, $noticia->id]);

    expect($article->categories->pluck('id')->sort()->values()->all())
        ->toBe([$mural->id, $noticia->id]);
});

test('the published scope only returns published articles', function () {
    Article::factory()->create(['is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['is_published' => false]);

    expect(Article::published()->count())->toBe(1);
});

test('the published scope excludes articles scheduled for the future', function () {
    Article::factory()->create(['is_published' => true, 'published_at' => now()->addDays(3)]);
    Article::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);

    expect(Article::published()->count())->toBe(1);
});

test('isScheduled is true only for published articles with a future published_at', function () {
    $scheduled = Article::factory()->create(['is_published' => true, 'published_at' => now()->addDays(3)]);
    $published = Article::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);
    $draft = Article::factory()->create(['is_published' => false, 'published_at' => null]);

    expect($scheduled->isScheduled())->toBeTrue()
        ->and($published->isScheduled())->toBeFalse()
        ->and($draft->isScheduled())->toBeFalse();
});

test('fileIconForPath maps common file extensions to their Font Awesome icon', function () {
    expect(Article::fileIconForPath('docs/310-MANUAL.pdf'))->toBe('file-pdf')
        ->and(Article::fileIconForPath('docs/guia.docx'))->toBe('file-word')
        ->and(Article::fileIconForPath('fotos/capa.PNG'))->toBe('file-image')
        ->and(Article::fileIconForPath('videos/aula.mp4'))->toBe('file-video')
        ->and(Article::fileIconForPath('arquivos/dados.zip'))->toBe('file');
});

test('a user can like and unlike an article', function () {
    $article = Article::factory()->create();
    $user = User::factory()->create();

    expect($article->isLikedBy($user))->toBeFalse();

    $article->likedByUsers()->attach($user->id);
    expect($article->refresh()->isLikedBy($user))->toBeTrue()
        ->and($article->likedByUsers()->count())->toBe(1);

    $article->likedByUsers()->detach($user->id);
    expect($article->refresh()->isLikedBy($user))->toBeFalse();
});

test('a user cannot like the same article twice at the database level', function () {
    $article = Article::factory()->create();
    $user = User::factory()->create();

    $article->likedByUsers()->attach($user->id);

    expect(fn () => $article->likedByUsers()->attach($user->id))
        ->toThrow(QueryException::class);
});

test('soft-deleting an article author does not fail and the article keeps its author_id', function () {
    // User uses SoftDeletes, so UserIndex::delete()'s $user->delete() never
    // removes the row — no FK violation is possible. The article's
    // author_id column is untouched; only the author() relation returns
    // null afterward (default Eloquent behavior excludes soft-deleted
    // related records), which is harmless since no view displays the
    // author anywhere.
    $author = User::factory()->create();
    $article = Article::factory()->create(['author_id' => $author->id]);

    $author->delete();

    expect($article->refresh()->author_id)->toBe($author->id)
        ->and($article->author)->toBeNull();
});
