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
