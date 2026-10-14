<?php

namespace App\Models;

use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * Vídeo institucional divulgado aos usuários, cuja URL original (YouTube
 * ou Vimeo) é convertida para um link de embed via embedUrl().
 */
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'url', 'thumbnail_path', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * Converte a URL "normal" do YouTube/Vimeo salva no registro para o
     * formato de embed exigido pelo player em <iframe>; URLs de outras
     * origens são devolvidas sem alteração.
     */
    public function embedUrl(): string
    {
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/watch\?v=)([A-Za-z0-9_-]+)/', $this->url, $matches)) {
            return 'https://www.youtube.com/embed/'.$matches[1];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $this->url, $matches)) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        return $this->url;
    }

    /**
     * Capa pra exibir nos cards de listagem: usa o arquivo enviado em
     * `thumbnail_path`, se houver; senão tenta a miniatura automática do
     * YouTube (gratuita, previsível pela URL) — vídeos de outras origens
     * sem thumbnail enviado ficam sem capa (o card mostra um ícone no lugar).
     */
    public function thumbnailUrl(): ?string
    {
        if ($this->thumbnail_path) {
            return Storage::url($this->thumbnail_path);
        }

        if (preg_match('/(?:youtu\.be\/|youtube\.com\/watch\?v=)([A-Za-z0-9_-]+)/', $this->url, $matches)) {
            return "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
        }

        return null;
    }

    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'video_likes')->withTimestamps();
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likedByUsers()->where('user_id', $user->id)->exists();
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
