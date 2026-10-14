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

    /** @var array<int, int> */
    public array $selected = [];

    #[On('photo-album-saved')]
    public function refreshList(): void
    {
        $this->editingAlbumId = null;
        $this->dispatch('close-modal', 'photo-album-form');
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
        abort_unless(auth()->user()->can('photos.update'), 403);

        $album = PhotoAlbum::findOrFail($albumId);
        $willBePublished = ! $album->is_published;
        $album->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($album) : ActivityLogger::deactivated($album);
        $this->dispatch('toast', title: 'Álbum de Fotos', message: $willBePublished ? 'Álbum publicado com sucesso.' : 'Álbum despublicado.', variant: $willBePublished ? 'success' : 'warning');
    }

    public function delete(int $albumId): void
    {
        abort_unless(auth()->user()->can('photos.delete'), 403);

        $album = PhotoAlbum::findOrFail($albumId);
        $album->delete();
        ActivityLogger::deleted($album);
        $this->dispatch('toast', title: 'Álbum de Fotos', message: 'Álbum excluído com sucesso.', variant: 'danger');
    }

    /**
     * IDs da página atual — usado tanto na listagem principal quanto para
     * "selecionar todos nesta página".
     */
    private function currentPageIds(): array
    {
        return PhotoAlbum::latest('event_date')->paginate(10)->pluck('id')->all();
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->currentPageIds();

        if (array_diff($pageIds, $this->selected) === []) {
            $this->selected = array_values(array_diff($this->selected, $pageIds));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $pageIds)));
        }
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function bulkPublish(): void
    {
        abort_unless(auth()->user()->can('photos.update'), 403);

        $albums = PhotoAlbum::whereIn('id', $this->selected)->where('is_published', false)->get();

        foreach ($albums as $album) {
            $album->update(['is_published' => true]);
            ActivityLogger::activated($album);
        }

        $this->selected = [];
        $message = $albums->isNotEmpty() ? $albums->count().' álbum(ns) publicado(s) com sucesso.' : 'Nenhum rascunho selecionado para publicar.';
        $this->dispatch('toast', title: 'Álbuns de Fotos', message: $message, variant: $albums->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkUnpublish(): void
    {
        abort_unless(auth()->user()->can('photos.update'), 403);

        $albums = PhotoAlbum::whereIn('id', $this->selected)->where('is_published', true)->get();

        foreach ($albums as $album) {
            $album->update(['is_published' => false]);
            ActivityLogger::deactivated($album);
        }

        $this->selected = [];
        $message = $albums->isNotEmpty() ? $albums->count().' álbum(ns) despublicado(s).' : 'Nenhum álbum publicado selecionado.';
        $this->dispatch('toast', title: 'Álbuns de Fotos', message: $message, variant: 'warning');
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('photos.delete'), 403);

        $albums = PhotoAlbum::whereIn('id', $this->selected)->get();

        foreach ($albums as $album) {
            $album->delete();
            ActivityLogger::deleted($album);
        }

        $this->selected = [];
        $this->dispatch('toast', title: 'Álbuns de Fotos', message: $albums->count().' álbum(ns) excluído(s) com sucesso.', variant: 'danger');
    }

    public function render()
    {
        $albums = PhotoAlbum::withCount('photos')->latest('event_date')->paginate(10);

        return view('livewire.admin.photos.photo-album-index', [
            'albums' => $albums,
            'allSelectedOnPage' => $albums->isNotEmpty() && array_diff($albums->pluck('id')->all(), $this->selected) === [],
            'editingAlbum' => $this->editingAlbumId ? PhotoAlbum::find($this->editingAlbumId) : null,
            'totalAlbums' => PhotoAlbum::count(),
            'publishedAlbums' => PhotoAlbum::where('is_published', true)->count(),
            'totalPhotos' => Photo::count(),
        ])->layout('layouts.admin', ['title' => 'Fotos']);
    }
}
