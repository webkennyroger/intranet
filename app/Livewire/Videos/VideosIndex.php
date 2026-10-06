<?php

namespace App\Livewire\Videos;

use App\Models\Video;
use Livewire\Component;

/**
 * Página de vídeos: lista os vídeos publicados, mais recentes
 * primeiro.
 */
class VideosIndex extends Component
{
    public function render()
    {
        return view('livewire.videos.videos-index', [
            'videos' => Video::where('is_published', true)->latest()->get(),
        ]);
    }
}
