<?php

namespace App\Livewire\Admin\Videos;

use App\Models\Video;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Vídeos": lista os vídeos cadastrados, permitindo editar,
 * publicar/despublicar e excluir cada um.
 */
class VideoIndex extends Component
{
    use WithPagination;

    public ?int $editingVideoId = null;

    public string $search = '';

    /** @var array<int, int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    #[On('video-saved')]
    public function refreshList(): void
    {
        $this->editingVideoId = null;
        $this->dispatch('close-modal', 'video-form');
    }

    public function edit(int $videoId): void
    {
        $this->editingVideoId = $videoId;
    }

    public function closeForm(): void
    {
        $this->editingVideoId = null;
    }

    public function togglePublish(int $videoId): void
    {
        abort_unless(auth()->user()->can('videos.update'), 403);

        $video = Video::findOrFail($videoId);
        $willBePublished = ! $video->is_published;
        $video->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($video) : ActivityLogger::deactivated($video);
        $this->dispatch('toast', title: 'Vídeo', message: $willBePublished ? 'Vídeo publicado com sucesso.' : 'Vídeo despublicado.', variant: $willBePublished ? 'success' : 'warning');
    }

    public function delete(int $videoId): void
    {
        abort_unless(auth()->user()->can('videos.delete'), 403);

        $video = Video::findOrFail($videoId);
        $video->delete();
        ActivityLogger::deleted($video);
        $this->dispatch('toast', title: 'Vídeo', message: 'Vídeo excluído com sucesso.', variant: 'danger');
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = Video::when($this->search !== '', fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->latest()->paginate(10)->pluck('id')->all();

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
        abort_unless(auth()->user()->can('videos.update'), 403);

        $videos = Video::whereIn('id', $this->selected)->where('is_published', false)->get();

        foreach ($videos as $video) {
            $video->update(['is_published' => true]);
            ActivityLogger::activated($video);
        }

        $this->selected = [];
        $message = $videos->isNotEmpty() ? $videos->count().' vídeo(s) publicado(s) com sucesso.' : 'Nenhum rascunho selecionado para publicar.';
        $this->dispatch('toast', title: 'Vídeos', message: $message, variant: $videos->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkUnpublish(): void
    {
        abort_unless(auth()->user()->can('videos.update'), 403);

        $videos = Video::whereIn('id', $this->selected)->where('is_published', true)->get();

        foreach ($videos as $video) {
            $video->update(['is_published' => false]);
            ActivityLogger::deactivated($video);
        }

        $this->selected = [];
        $message = $videos->isNotEmpty() ? $videos->count().' vídeo(s) despublicado(s).' : 'Nenhum vídeo publicado selecionado.';
        $this->dispatch('toast', title: 'Vídeos', message: $message, variant: 'warning');
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('videos.delete'), 403);

        $videos = Video::whereIn('id', $this->selected)->get();

        foreach ($videos as $video) {
            $video->delete();
            ActivityLogger::deleted($video);
        }

        $this->selected = [];
        $this->dispatch('toast', title: 'Vídeos', message: $videos->count().' vídeo(s) excluído(s) com sucesso.', variant: 'danger');
    }

    public function render()
    {
        $videos = Video::when($this->search !== '', fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->latest()->paginate(10);

        return view('livewire.admin.videos.video-index', [
            'videos' => $videos,
            'allSelectedOnPage' => $videos->isNotEmpty() && array_diff($videos->pluck('id')->all(), $this->selected) === [],
            'editingVideo' => $this->editingVideoId ? Video::find($this->editingVideoId) : null,
            'totalVideos' => Video::count(),
            'publishedVideos' => Video::where('is_published', true)->count(),
        ])->layout('layouts.admin', ['title' => 'Vídeos']);
    }
}
