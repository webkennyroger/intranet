<?php

namespace App\Models;

use Database\Factories\ForumTopicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tópico do fórum interno aberto por um usuário. Carrega as respostas
 * (`replies`) já em ordem cronológica para montar a thread de discussão.
 */
class ForumTopic extends Model
{
    /** @use HasFactory<ForumTopicFactory> */
    use HasFactory;

    protected $fillable = ['author_id', 'title', 'body'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class)->orderBy('created_at');
    }
}
