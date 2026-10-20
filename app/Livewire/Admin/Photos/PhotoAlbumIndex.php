<?php

namespace App\Livewire\Admin\Photos;

use App\Livewire\Concerns\ComputesWeeklyTrend;
use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Services\ActivityLogger;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Fotos": lista os álbuns de fotos cadastrados, permitindo
 * editar, publicar/despublicar e excluir cada um.
 */
class PhotoAlbumIndex extends Component
{
    use ComputesWeeklyTrend, WithPagination;

    public ?int $editingAlbumId = null;

    public ?int $viewingAlbumId = null;

    public string $search = '';

    /** @var array<int, int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

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

    public function view(int $albumId): void
    {
        $this->viewingAlbumId = $albumId;
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

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->albums->pluck('id')->all();

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

    /**
     * Isolada num island (ver photo-album-index.blade.php): precisa estar
     * acessível via $this-> porque islands não têm acesso a variáveis de
     * view vindas de fora deles, só a propriedades/métodos do componente.
     */
    #[Computed]
    public function albums()
    {
        return PhotoAlbum::withCount('photos')
            ->with('photos')
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->latest('event_date')->paginate(10);
    }

    #[Computed]
    public function allSelectedOnPage(): bool
    {
        return $this->albums->isNotEmpty() && array_diff($this->albums->pluck('id')->all(), $this->selected) === [];
    }

    /**
     * O modal de edição mora dentro do island (ver photo-album-index.blade.php)
     * pra receber o álbum certo quando "Editar"/"Novo álbum" são clicados —
     * precisa estar acessível via $this-> pelo mesmo motivo de albums()/
     * allSelectedOnPage() acima.
     */
    #[Computed]
    public function editingAlbum(): ?PhotoAlbum
    {
        return $this->editingAlbumId ? PhotoAlbum::find($this->editingAlbumId) : null;
    }

    /**
     * O modal "Ver fotos" (grade com rolagem) também mora dentro do
     * island, pelo mesmo motivo de editingAlbum() acima.
     */
    #[Computed]
    public function viewingAlbum(): ?PhotoAlbum
    {
        return $this->viewingAlbumId ? PhotoAlbum::find($this->viewingAlbumId) : null;
    }

    public function render()
    {
        return view('livewire.admin.photos.photo-album-index', [
            'totalAlbums' => PhotoAlbum::count(),
            'totalAlbumsTrend' => $this->weeklyTrend(PhotoAlbum::query(), 'created_at'),
            'publishedAlbums' => PhotoAlbum::where('is_published', true)->count(),
            'publishedAlbumsTrend' => $this->weeklyTrend(PhotoAlbum::query()->where('is_published', true), 'created_at'),
            'totalPhotos' => Photo::count(),
            'totalPhotosTrend' => $this->weeklyTrend(Photo::query(), 'created_at'),
        ])->layout('layouts.admin', ['title' => 'Fotos']);
    }
}
