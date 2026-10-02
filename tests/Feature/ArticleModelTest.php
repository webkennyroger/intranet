<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\QueryException;

test('an article can be created with the expected fields', function () {
    $category = Category::factory()->create();
    $author = User::factory()->create();

    $article = Article::create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Teste',
        'excerpt' => 'Resumo',
        'body' => '<p>Conteúdo</p>',
        'cover_color' => '#064e3b',
        'cover_icon' => 'newspaper',
        'is_published' => false,
    ]);

    expect($article->title)->toBe('Teste')
        ->and($article->category->id)->toBe($category->id)
        ->and($article->author->id)->toBe($author->id);
});

test('the published scope only returns published articles', function () {
    Article::factory()->create(['is_published' => true]);
    Article::factory()->create(['is_published' => false]);

    expect(Article::published()->count())->toBe(1);
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
