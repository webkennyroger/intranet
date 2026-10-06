<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Eventos": lista os eventos cadastrados, permitindo editar,
 * publicar/despublicar e excluir cada um.
 */
class EventIndex extends Component
{
    use WithPagination;

    public ?int $editingEventId = null;

    #[On('event-saved')]
    public function refreshList(): void
    {
        $this->editingEventId = null;
    }

    public function edit(int $eventId): void
    {
        $this->editingEventId = $eventId;
    }

    public function closeForm(): void
    {
        $this->editingEventId = null;
    }

    public function togglePublish(int $eventId): void
    {
        abort_unless(auth()->user()->can('content.publish'), 403);

        $event = Event::findOrFail($eventId);
        $willBePublished = ! $event->is_published;
        $event->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($event) : ActivityLogger::deactivated($event);
    }

    public function delete(int $eventId): void
    {
        abort_unless(auth()->user()->can('content.delete'), 403);

        $event = Event::findOrFail($eventId);
        $event->delete();
        ActivityLogger::deleted($event);
    }

    public function render()
    {
        return view('livewire.admin.events.event-index', [
            'events' => Event::orderBy('starts_at')->paginate(10),
            'editingEvent' => $this->editingEventId ? Event::find($this->editingEventId) : null,
            'totalEvents' => Event::count(),
            'upcomingEvents' => Event::where('starts_at', '>=', now())->count(),
        ])->layout('layouts.admin', ['title' => 'Eventos']);
    }
}
