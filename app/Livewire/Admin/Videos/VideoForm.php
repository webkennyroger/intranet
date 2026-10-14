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

    public function mount(?Video $video = null): void
    {
        if ($video) {
            $this->video = $video;
            $this->title = $video->title;
            $this->description = (string) $video->description;
            $this->url = $video->url;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->video ? 'videos.update' : 'videos.create'), 403);

        $this->validate();

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'url' => $this->url,
        ];

        if ($this->video) {
            $changes = ActivityLogger::diff($this->video, $data);
            $this->video->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->video, $changes);
            }
            $this->dispatch('toast', title: 'Vídeo', message: 'Vídeo atualizado com sucesso.', variant: 'success');
        } else {
            // Vídeo novo entra publicado — despublicar é uma ação separada
            // na tabela (togglePublish), não um campo deste formulário.
            $video = Video::create($data + ['is_published' => true]);
            ActivityLogger::created($video);
            $this->dispatch('toast', title: 'Vídeo', message: 'Vídeo criado com sucesso.', variant: 'success');
        }

        $this->dispatch('video-saved');
        $this->reset(['title', 'description', 'url']);
    }

    public function render()
    {
        return view('livewire.admin.videos.video-form');
    }
}
