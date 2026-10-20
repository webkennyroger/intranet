<?php

namespace App\Livewire\Admin\Events;

use App\Livewire\Concerns\ComputesWeeklyTrend;
use App\Models\Event;
use App\Services\ActivityLogger;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tela admin "Eventos": lista os eventos cadastrados, permitindo
 * reordenar por arrastar-e-soltar, editar, publicar/despublicar e
 * excluir cada um. A reordenação só fica disponível com a busca vazia,
 * já que ela precisa do conjunto completo pra não embaralhar a ordem.
 */
class EventIndex extends Component
{
    use ComputesWeeklyTrend;

    public ?int $editingEventId = null;

    public string $search = '';

    /** @var array<int, int> */
    public array $selected = [];

    public function updatedSearch(): void
    {
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
        $pageIds = $this->filteredQuery()->orderBy('order')->pluck('id')->all();

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

    /**
     * @param  array<int, int>  $ids
     */
    public function reorder(array $ids): void
    {
        abort_unless(auth()->user()->can('events.update'), 403);

        $existingCount = Event::whereIn('id', $ids)->count();

        // A lista arrastada pelo navegador pode não bater com o banco (ids
        // duplicados, removidos por outra aba, etc.) — essa checagem garante
        // que $ids é exatamente o conjunto atual de eventos antes de aplicar
        // a nova ordem, evitando corromper o campo "order". Só é possível
        // chegar aqui com a busca vazia (ver sortable no Blade), então o
        // conjunto arrastado já é sempre o total.
        abort_if($existingCount !== count(array_unique($ids)) || $existingCount !== Event::count(), 403);

        foreach ($ids as $position => $id) {
            Event::whereKey($id)->update(['order' => $position]);
        }
    }

    public function render()
    {
        $events = $this->filteredQuery()->orderBy('order')->get();

        return view('livewire.admin.events.event-index', [
            'events' => $events,
            'allSelectedOnPage' => $events->isNotEmpty() && array_diff($events->pluck('id')->all(), $this->selected) === [],
            'editingEvent' => $this->editingEventId ? Event::find($this->editingEventId) : null,
            'totalEvents' => Event::count(),
            'totalEventsTrend' => $this->weeklyTrend(Event::query(), 'created_at'),
            'upcomingEvents' => Event::where('starts_at', '>=', now())->count(),
            'upcomingEventsTrend' => $this->weeklyTrend(Event::query()->where('starts_at', '>=', now()), 'created_at'),
        ])->layout('layouts.admin', ['title' => 'Eventos']);
    }
}
