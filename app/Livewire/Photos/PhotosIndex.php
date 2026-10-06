<?php

namespace App\Livewire\Photos;

use App\Models\PhotoAlbum;
use Livewire\Component;

/**
 * Página de álbuns de fotos: lista os álbuns publicados, do evento
 * mais recente para o mais antigo, com a contagem de fotos de cada um.
 */
class PhotosIndex extends Component
{
    public function render()
    {
        return view('livewire.photos.photos-index', [
            'albums' => PhotoAlbum::where('is_published', true)->withCount('photos')->latest('event_date')->get(),
        ]);
    }
}
