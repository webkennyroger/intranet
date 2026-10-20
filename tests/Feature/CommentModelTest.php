<?php

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;

test('a comment belongs to an article and an author', function () {
    $article = Article::factory()->create();
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['article_id' => $article->id, 'author_id' => $author->id]);

    expect($comment->article->id)->toBe($article->id)
        ->and($comment->author->id)->toBe($author->id);
});

test('a reply belongs to its parent comment and shows up in replies', function () {
    $parent = Comment::factory()->create();
    $reply = Comment::factory()->create(['article_id' => $parent->article_id, 'parent_id' => $parent->id]);

    expect($reply->parent->id)->toBe($parent->id)
        ->and($parent->replies->pluck('id')->all())->toBe([$reply->id]);
});

test("an article's comments relation only returns top-level comments, ordered by creation", function () {
    $article = Article::factory()->create();
    $first = Comment::factory()->create(['article_id' => $article->id, 'created_at' => now()->subMinutes(5)]);
    $second = Comment::factory()->create(['article_id' => $article->id, 'created_at' => now()->subMinute()]);
    $reply = Comment::factory()->create(['article_id' => $article->id, 'parent_id' => $first->id]);

    expect($article->comments->pluck('id')->all())->toBe([$first->id, $second->id]);
});
