<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * Artigo/notícia publicado no Mural. Controla curtidas por usuário e
 * expõe o scope `published` para esconder rascunhos do público.
 */
class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id', 'title', 'excerpt', 'body',
        'cover_color', 'cover_icon', 'cover_label', 'featured_image',
        'custom_fields', 'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'custom_fields' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredImageUrl(): ?string
    {
        return $this->featured_image ? Storage::url($this->featured_image) : null;
    }

    /**
     * Tempo estimado de leitura (minutos), a 200 palavras por minuto,
     * usado nos cards de notícia da página Início.
     */
    public function readingTimeMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->body)) / 200));
    }

    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'article_likes')->withTimestamps();
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likedByUsers()->where('user_id', $user->id)->exists();
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('published_at', '<=', now());
    }

    /**
     * Verdadeiro quando o artigo está marcado como publicado mas a data
     * de publicação ainda não chegou (post agendado para o futuro).
     */
    public function isScheduled(): bool
    {
        return $this->is_published && $this->published_at !== null && $this->published_at->isFuture();
    }

    /**
     * Ícone Font Awesome (sem o prefixo "fa-") correspondente à extensão de
     * um caminho/URL de arquivo — usado nos campos personalizados do tipo
     * "texto" quando o valor é um link para um documento.
     */
    public static function fileIconForPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf' => 'file-pdf',
            'doc', 'docx' => 'file-word',
            'ppt', 'pptx' => 'file-powerpoint',
            'xls', 'xlsx', 'csv' => 'file-excel',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'file-image',
            'mp4', 'mov', 'webm', 'avi' => 'file-video',
            'mp3', 'wav', 'ogg' => 'file-audio',
            default => 'file',
        };
    }
}
