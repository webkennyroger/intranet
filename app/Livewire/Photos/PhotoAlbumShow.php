<?php

namespace App\Livewire\Photos;

use App\Models\PhotoAlbum;
use Livewire\Component;

/**
 * Página de detalhe de um álbum de fotos: exibe as fotos de um álbum
 * publicado.
 */
class PhotoAlbumShow extends Component
{
    public PhotoAlbum $album;

    public function mount(PhotoAlbum $album): void
    {
        abort_unless($album->is_published, 404);

        $album->incrementViews();

        $this->album = $album;
    }

    public function render()
    {
        return view('livewire.photos.photo-album-show', [
            'photos' => $this->album->photos,
        ]);
    }
}
