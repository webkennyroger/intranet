<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Arquivo enviado direto na Biblioteca de Mídia, sem pertencer a nenhum
 * artigo/documento/foto específico (upload "solto").
 */
class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = ['name', 'path', 'source', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
