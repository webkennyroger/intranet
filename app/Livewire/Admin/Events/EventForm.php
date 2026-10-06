<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Formulário admin de criação/edição de eventos da agenda (título, local,
 * datas de início/fim e status de publicação).
 */
class EventForm extends Component
{
    public ?Event $event = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('nullable|string|max:255')]
    public string $location = '';

    #[Validate('required|date')]
    public string $startsAt = '';

    #[Validate('nullable|date|after_or_equal:startsAt')]
    public string $endsAt = '';

    public bool $isPublished = true;

    public function mount(?Event $event = null): void
    {
        if ($event) {
            $this->event = $event;
            $this->title = $event->title;
            $this->description = (string) $event->description;
            $this->location = (string) $event->location;
            $this->startsAt = $event->starts_at->format('Y-m-d\TH:i');
            $this->endsAt = $event->ends_at?->format('Y-m-d\TH:i') ?? '';
            $this->isPublished = $event->is_published;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->event ? 'content.update' : 'content.create'), 403);

        $this->validate();

        $data = [
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'location' => $this->location !== '' ? $this->location : null,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt !== '' ? $this->endsAt : null,
            'is_published' => $this->isPublished,
        ];

        if ($this->event) {
            $changes = ActivityLogger::diff($this->event, $data);
            $this->event->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->event, $changes);
            }
        } else {
            $event = Event::create($data);
            ActivityLogger::created($event);
        }

        $this->dispatch('event-saved');
        $this->reset(['title', 'description', 'location', 'startsAt', 'endsAt']);
        $this->isPublished = true;
    }

    public function render()
    {
        return view('livewire.admin.events.event-form');
    }
}
