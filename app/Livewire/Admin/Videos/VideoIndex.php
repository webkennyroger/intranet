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

    #[On('video-saved')]
    public function refreshList(): void
    {
        $this->editingVideoId = null;
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
        abort_unless(auth()->user()->can('content.publish'), 403);

        $video = Video::findOrFail($videoId);
        $willBePublished = ! $video->is_published;
        $video->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($video) : ActivityLogger::deactivated($video);
    }

    public function delete(int $videoId): void
    {
        abort_unless(auth()->user()->can('content.delete'), 403);

        $video = Video::findOrFail($videoId);
        $video->delete();
        ActivityLogger::deleted($video);
    }

    public function render()
    {
        return view('livewire.admin.videos.video-index', [
            'videos' => Video::latest()->paginate(10),
            'editingVideo' => $this->editingVideoId ? Video::find($this->editingVideoId) : null,
            'totalVideos' => Video::count(),
            'publishedVideos' => Video::where('is_published', true)->count(),
        ])->layout('layouts.admin', ['title' => 'Vídeos']);
    }
}
