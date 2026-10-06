<?php

namespace App\Livewire\Admin\Videos;

use App\Models\Video;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Formulário admin de criação/edição de vídeos (título, descrição, URL do
 * vídeo e status de publicação).
 */
class VideoForm extends Component
{
    public ?Video $video = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|url|max:2048')]
    public string $url = '';

    public bool $isPublished = true;

    public function mount(?Video $video = null): void
    {
        if ($video) {
            $this->video = $video;
            $this->title = $video->title;
            $this->description = (string) $video->description;
            $this->url = $video->url;
            $this->isPublished = $video->is_published;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->video ? 'content.update' : 'content.create'), 403);

        $this->validate();

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'url' => $this->url,
            'is_published' => $this->isPublished,
        ];

        if ($this->video) {
            $changes = ActivityLogger::diff($this->video, $data);
            $this->video->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->video, $changes);
            }
        } else {
            $video = Video::create($data);
            ActivityLogger::created($video);
        }

        $this->dispatch('video-saved');
        $this->reset(['title', 'description', 'url']);
        $this->isPublished = true;
    }

    public function render()
    {
        return view('livewire.admin.videos.video-form');
    }
}
