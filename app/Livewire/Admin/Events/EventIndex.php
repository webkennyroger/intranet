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

    public string $search = '';

    /** @var array<int, int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    private function filteredQuery()
    {
        return Event::when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
            ->where('title', 'like', "%{$this->search}%")
            ->orWhere('location', 'like', "%{$this->search}%")));
    }

    #[On('event-saved')]
    public function refreshList(): void
    {
        $this->editingEventId = null;
        $this->dispatch('close-modal', 'event-form');
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
        abort_unless(auth()->user()->can('events.update'), 403);

        $event = Event::findOrFail($eventId);
        $willBePublished = ! $event->is_published;
        $event->update(['is_published' => $willBePublished]);
        $willBePublished ? ActivityLogger::activated($event) : ActivityLogger::deactivated($event);
        $this->dispatch('toast', title: 'Evento', message: $willBePublished ? 'Evento publicado com sucesso.' : 'Evento despublicado.', variant: $willBePublished ? 'success' : 'warning');
    }

    public function delete(int $eventId): void
    {
        abort_unless(auth()->user()->can('events.delete'), 403);

        $event = Event::findOrFail($eventId);
        $event->delete();
        ActivityLogger::deleted($event);
        $this->dispatch('toast', title: 'Evento', message: 'Evento excluído com sucesso.', variant: 'danger');
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->filteredQuery()->orderBy('starts_at')->paginate(10)->pluck('id')->all();

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
        abort_unless(auth()->user()->can('events.update'), 403);

        $events = Event::whereIn('id', $this->selected)->where('is_published', false)->get();

        foreach ($events as $event) {
            $event->update(['is_published' => true]);
            ActivityLogger::activated($event);
        }

        $this->selected = [];
        $message = $events->isNotEmpty() ? $events->count().' evento(s) publicado(s) com sucesso.' : 'Nenhum rascunho selecionado para publicar.';
        $this->dispatch('toast', title: 'Eventos', message: $message, variant: $events->isNotEmpty() ? 'success' : 'warning');
    }

    public function bulkUnpublish(): void
    {
        abort_unless(auth()->user()->can('events.update'), 403);

        $events = Event::whereIn('id', $this->selected)->where('is_published', true)->get();

        foreach ($events as $event) {
            $event->update(['is_published' => false]);
            ActivityLogger::deactivated($event);
        }

        $this->selected = [];
        $message = $events->isNotEmpty() ? $events->count().' evento(s) despublicado(s).' : 'Nenhum evento publicado selecionado.';
        $this->dispatch('toast', title: 'Eventos', message: $message, variant: 'warning');
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('events.delete'), 403);

        $events = Event::whereIn('id', $this->selected)->get();

        foreach ($events as $event) {
            $event->delete();
            ActivityLogger::deleted($event);
        }

        $this->selected = [];
        $this->dispatch('toast', title: 'Eventos', message: $events->count().' evento(s) excluído(s) com sucesso.', variant: 'danger');
    }

    public function render()
    {
        $events = $this->filteredQuery()->orderBy('starts_at')->paginate(10);

        return view('livewire.admin.events.event-index', [
            'events' => $events,
            'allSelectedOnPage' => $events->isNotEmpty() && array_diff($events->pluck('id')->all(), $this->selected) === [],
            'editingEvent' => $this->editingEventId ? Event::find($this->editingEventId) : null,
            'totalEvents' => Event::count(),
            'upcomingEvents' => Event::where('starts_at', '>=', now())->count(),
        ])->layout('layouts.admin', ['title' => 'Eventos']);
    }
}
