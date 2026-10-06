<?php

namespace App\Models;

use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
