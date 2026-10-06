<?php

use App\Models\Article;
use App\Models\User;
use App\Services\ActivityLogger;

test('created logs the action with the authenticated user and a default description', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create(['title' => 'Primeira Notícia']);

    $log = ActivityLogger::created($article);

    expect($log->action)->toBe('created')
        ->and($log->subject_type)->toBe(Article::class)
        ->and($log->subject_id)->toBe($article->id)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->description)->toBe('Mural "Primeira Notícia" criado(a)');
});

test('created accepts a custom description', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create();

    $log = ActivityLogger::created($article, 'Descrição customizada');

    expect($log->description)->toBe('Descrição customizada');
});

test('updated stores the field-level changes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create();

    $log = ActivityLogger::updated($article, ['title' => ['old' => 'Antigo', 'new' => 'Novo']], 'Mural "Novo" atualizado');

    expect($log->action)->toBe('updated')
        ->and($log->changes)->toBe(['title' => ['old' => 'Antigo', 'new' => 'Novo']])
        ->and($log->description)->toBe('Mural "Novo" atualizado');
});

test('activated and deactivated log the correct action', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create(['title' => 'Nota']);

    $activated = ActivityLogger::activated($article);
    $deactivated = ActivityLogger::deactivated($article);

    expect($activated->action)->toBe('activated')
        ->and($deactivated->action)->toBe('deactivated');
});

test('deleted logs the action without needing the subject to still exist', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create(['title' => 'Removida']);
    $articleId = $article->id;
    $article->delete();

    $log = ActivityLogger::deleted($article);

    expect($log->action)->toBe('deleted')
        ->and($log->subject_id)->toBe($articleId)
        ->and($log->description)->toBe('Mural "Removida" excluído(a)');
});

test('diff only includes fields that actually changed', function () {
    $article = Article::factory()->create(['title' => 'Antigo', 'excerpt' => 'Resumo']);

    $changes = ActivityLogger::diff($article, ['title' => 'Novo', 'excerpt' => 'Resumo']);

    expect($changes)->toBe(['title' => ['old' => 'Antigo', 'new' => 'Novo']]);
});

test('diff returns an empty array when nothing changed', function () {
    $article = Article::factory()->create(['title' => 'Igual']);

    $changes = ActivityLogger::diff($article, ['title' => 'Igual']);

    expect($changes)->toBe([]);
});

test('logging without an authenticated user records a null user_id', function () {
    $article = Article::factory()->create();

    $log = ActivityLogger::created($article);

    expect($log->user_id)->toBeNull();
});
