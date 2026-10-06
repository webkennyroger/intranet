<?php

namespace App\Livewire\Admin\Photos;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tela admin "Fotos": lista os álbuns de fotos cadastrados, permitindo
 * editar, publicar/despublicar e excluir cada um.
 */
class PhotoAlbumIndex extends Component
{
    public ?int $editingAlbumId = null;

    #[On('photo-album-saved')]
    public function refreshList(): void
    {
        $this->editingAlbumId = null;
    }

    public function edit(int $albumId): void
    {
        $this->editingAlbumId = $albumId;
    }

    public function closeForm(): void
    {
        $this->editingAlbumId = null;
    }

    public function togglePublish(int $albumId): void
    {
        abort_unless(auth()->user()->can('content.publish'), 403);

        $album = PhotoAlbum::findOrFail($albumId);
        $willBePublished = ! $album->is_published;
        $album->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($album) : ActivityLogger::deactivated($album);
    }

    public function delete(int $albumId): void
    {
        abort_unless(auth()->user()->can('content.delete'), 403);

        $album = PhotoAlbum::findOrFail($albumId);
        $album->delete();
        ActivityLogger::deleted($album);
    }

    public function render()
    {
        return view('livewire.admin.photos.photo-album-index', [
            'albums' => PhotoAlbum::withCount('photos')->latest('event_date')->paginate(10),
            'editingAlbum' => $this->editingAlbumId ? PhotoAlbum::find($this->editingAlbumId) : null,
            'totalAlbums' => PhotoAlbum::count(),
            'publishedAlbums' => PhotoAlbum::where('is_published', true)->count(),
            'totalPhotos' => Photo::count(),
        ])->layout('layouts.admin', ['title' => 'Fotos']);
    }
}
