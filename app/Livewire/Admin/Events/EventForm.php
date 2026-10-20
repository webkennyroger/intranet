<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulário admin de criação/edição de eventos da agenda (título, local,
 * imagem de capa e datas de início/fim).
 */
class EventForm extends Component
{
    use WithFileUploads;

    public ?Event $event = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('nullable|string|max:255')]
    public string $location = '';

    #[Validate('nullable|url|max:2048')]
    public string $link = '';

    #[Validate('required|date')]
    public string $startsAt = '';

    #[Validate('nullable|date|after_or_equal:startsAt')]
    public string $endsAt = '';

    public $coverImage = null;

    public ?string $existingCoverImage = null;

    public $file = null;

    public ?string $existingFilePath = null;

    public function mount(?Event $event = null): void
    {
        if ($event) {
            $this->event = $event;
            $this->title = $event->title;
            $this->description = (string) $event->description;
            $this->location = (string) $event->location;
            $this->link = (string) $event->link;
            $this->startsAt = $event->starts_at->format('Y-m-d\TH:i');
            $this->endsAt = $event->ends_at?->format('Y-m-d\TH:i') ?? '';
            $this->existingCoverImage = $event->cover_image;
            $this->existingFilePath = $event->file_path;
        }
    }

    public function removeCoverImage(): void
    {
        $this->existingCoverImage = null;
        $this->coverImage = null;
    }

    public function removeFile(): void
    {
        $this->existingFilePath = null;
        $this->file = null;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->event ? 'events.update' : 'events.create'), 403);

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'link' => 'nullable|url|max:2048',
            'startsAt' => 'required|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
            'coverImage' => 'nullable|image|max:4096',
            'file' => 'nullable|file|max:10240',
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'location' => $this->location !== '' ? $this->location : null,
            'link' => $this->link !== '' ? $this->link : null,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt !== '' ? $this->endsAt : null,
        ];

        if ($this->coverImage) {
            $data['cover_image'] = $this->coverImage->store('events', 'public');
        } elseif ($this->existingCoverImage === null) {
            $data['cover_image'] = null;
        }

        if ($this->file) {
            $data['file_path'] = $this->file->store('events', 'public');
        } elseif ($this->existingFilePath === null) {
            $data['file_path'] = null;
        }

        if ($this->event) {
            $changes = ActivityLogger::diff($this->event, $data);
            $this->event->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->event, $changes);
            }
            $this->dispatch('toast', title: 'Evento', message: 'Evento atualizado com sucesso.', variant: 'success');
        } else {
            // Evento novo entra publicado — despublicar é uma ação separada
            // na tabela (togglePublish), não um campo deste formulário.
            $data['order'] = (Event::max('order') ?? 0) + 1;
            $event = Event::create($data + ['is_published' => true]);
            ActivityLogger::created($event);
            $this->dispatch('toast', title: 'Evento', message: 'Evento criado com sucesso.', variant: 'success');
        }

        $this->dispatch('event-saved');
        $this->reset(['title', 'description', 'location', 'link', 'startsAt', 'endsAt', 'coverImage', 'existingCoverImage', 'file', 'existingFilePath']);
    }

    public function render()
    {
        return view('livewire.admin.events.event-form');
    }
}
