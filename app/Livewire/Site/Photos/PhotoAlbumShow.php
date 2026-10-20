<?php

namespace App\Livewire\Site\Photos;

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

    public function toggleLike(): void
    {
        $user = auth()->user();

        if ($this->album->isLikedBy($user)) {
            $this->album->likedByUsers()->detach($user->id);
        } else {
            $this->album->likedByUsers()->attach($user->id);
        }
    }

    public function render()
    {
        return view('livewire.site.photos.photo-album-show', [
            'photos' => $this->album->photos,
        ]);
    }
}
