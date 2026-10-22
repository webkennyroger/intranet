<?php

namespace App\Livewire\Admin\Events;

use App\Livewire\Concerns\BuildsCalendarGrid;
use App\Models\Event;
use App\Models\EventCategory;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tela admin "Eventos": calendário com visões Ano/Mês/Semana/Dia/Lista,
 * navegação entre períodos, criação rápida arrastando um tipo da barra
 * lateral pro calendário (ou clicando num dia vazio), e edição/exclusão
 * por evento a partir do modal de visualização.
 */
class EventIndex extends Component
{
    use BuildsCalendarGrid;

    private const VIEWS = ['year', 'month', 'week', 'day', 'list'];

    #[Url]
    public string $calendarView = 'month';

    #[Url]
    public string $cursor = '';

    public ?int $editingEventId = null;

    public ?int $selectedEventId = null;

    public ?string $prefillStartsAt = null;

    public ?int $editingCategoryId = null;

    public function mount(): void
    {
        if ($this->cursor === '') {
            $this->cursor = now()->format('Y-m-d');
        }
    }

    #[On('event-saved')]
    public function refreshList(): void
    {
        $this->editingEventId = null;
        $this->prefillStartsAt = null;
        $this->dispatch('close-modal', 'event-form');
    }

    #[On('event-category-saved')]
    public function refreshCategories(): void
    {
        $this->editingCategoryId = null;
        $this->dispatch('close-modal', 'event-category-form');
    }

    public function editCategory(int $categoryId): void
    {
        $this->editingCategoryId = $categoryId;
    }

    public function closeCategoryForm(): void
    {
        $this->editingCategoryId = null;
    }

    public function deleteCategory(int $categoryId): void
    {
        abort_unless(auth()->user()->can('events.delete'), 403);

        $category = EventCategory::findOrFail($categoryId);
        $category->delete();
        $this->dispatch('toast', title: 'Categoria', message: 'Categoria excluída com sucesso.', variant: 'danger');
    }

    public function setView(string $view): void
    {
        abort_unless(in_array($view, self::VIEWS, true), 404);

        $this->calendarView = $view;
    }

    public function goToToday(): void
    {
        $this->cursor = now()->format('Y-m-d');
    }

    public function goToMonth(string $date): void
    {
        validator(['date' => $date], ['date' => 'required|date'])->validate();

        $this->cursor = $date;
        $this->calendarView = 'month';
    }

    public function previous(): void
    {
        $this->cursor = match ($this->calendarView) {
            'year' => Carbon::parse($this->cursor)->subYear()->format('Y-m-d'),
            'week' => Carbon::parse($this->cursor)->subWeek()->format('Y-m-d'),
            'day' => Carbon::parse($this->cursor)->subDay()->format('Y-m-d'),
            default => Carbon::parse($this->cursor)->subMonth()->format('Y-m-d'),
        };
    }

    public function next(): void
    {
        $this->cursor = match ($this->calendarView) {
            'year' => Carbon::parse($this->cursor)->addYear()->format('Y-m-d'),
            'week' => Carbon::parse($this->cursor)->addWeek()->format('Y-m-d'),
            'day' => Carbon::parse($this->cursor)->addDay()->format('Y-m-d'),
            default => Carbon::parse($this->cursor)->addMonth()->format('Y-m-d'),
        };
    }

    public function edit(int $eventId): void
    {
        $this->editingEventId = $eventId;
        $this->prefillStartsAt = null;
    }

    public function closeForm(): void
    {
        $this->editingEventId = null;
        $this->prefillStartsAt = null;
    }

    public function viewEvent(int $eventId): void
    {
        $this->selectedEventId = $eventId;
    }

    public function closeView(): void
    {
        $this->selectedEventId = null;
    }

    public function createOnDate(string $date): void
    {
        validator(['date' => $date], ['date' => 'required|date'])->validate();

        $this->editingEventId = null;
        $this->prefillStartsAt = $date;
        $this->dispatch('open-modal', 'event-form');
    }

    public function quickCreate(int $categoryId, string $label, string $date): void
    {
        abort_unless(auth()->user()->can('events.create'), 403);

        validator(
            ['categoryId' => $categoryId, 'label' => $label, 'date' => $date],
            [
                'categoryId' => 'required|exists:categories,id,type,event',
                'label' => 'required|string|max:255',
                'date' => 'required|date',
            ]
        )->validate();

        $event = Event::create([
            'title' => $label,
            'event_category_id' => $categoryId,
            'starts_at' => $date.' 09:00:00',
            'is_published' => true,
            'order' => (Event::max('order') ?? 0) + 1,
        ]);

        ActivityLogger::created($event);

        $this->editingEventId = $event->id;
        $this->prefillStartsAt = null;
        $this->dispatch('open-modal', 'event-form');
    }

    /**
     * Arrastar um evento já existente pra outro dia: mantém o horário e a
     * duração originais, só muda a data.
     */
    public function rescheduleEvent(int $eventId, string $date): void
    {
        abort_unless(auth()->user()->can('events.update'), 403);

        validator(['date' => $date], ['date' => 'required|date'])->validate();

        $event = Event::findOrFail($eventId);
        $newStartsAt = Carbon::parse($date)->setTime(
            $event->starts_at->hour,
            $event->starts_at->minute,
            $event->starts_at->second,
        );

        $data = ['starts_at' => $newStartsAt];

        if ($event->ends_at) {
            $durationSeconds = $event->ends_at->getTimestamp() - $event->starts_at->getTimestamp();
            $data['ends_at'] = $newStartsAt->copy()->addSeconds($durationSeconds);
        }

        $changes = ActivityLogger::diff($event, $data);
        $event->update($data);
        if ($changes !== []) {
            ActivityLogger::updated($event, $changes);
        }

        $this->dispatch('toast', title: 'Evento', message: 'Evento reagendado com sucesso.', variant: 'success');
    }

    public function delete(int $eventId): void
    {
        abort_unless(auth()->user()->can('events.delete'), 403);

        $event = Event::findOrFail($eventId);
        $event->delete();
        ActivityLogger::deleted($event);
        $this->selectedEventId = null;
        $this->dispatch('close-modal', 'event-view');
        $this->dispatch('toast', title: 'Evento', message: 'Evento excluído com sucesso.', variant: 'danger');
    }

    #[Computed]
    public function editingEvent(): ?Event
    {
        return $this->editingEventId ? Event::with('category')->find($this->editingEventId) : null;
    }

    #[Computed]
    public function selectedEvent(): ?Event
    {
        return $this->selectedEventId ? Event::with('category')->find($this->selectedEventId) : null;
    }

    #[Computed]
    public function editingCategory(): ?EventCategory
    {
        return $this->editingCategoryId ? EventCategory::find($this->editingCategoryId) : null;
    }

    /**
     * @return Collection<int, EventCategory>
     */
    #[Computed]
    public function eventCategories(): Collection
    {
        return EventCategory::orderBy('order')->get();
    }

    #[Computed]
    public function periodLabel(): string
    {
        $date = Carbon::parse($this->cursor);

        return match ($this->calendarView) {
            'year' => $date->format('Y'),
            'week' => $date->copy()->startOfWeek(Carbon::SUNDAY)->translatedFormat('d \d\e M').' – '.$date->copy()->endOfWeek(Carbon::SATURDAY)->translatedFormat('d \d\e M \d\e Y'),
            'day' => $date->translatedFormat('d \d\e F \d\e Y'),
            default => $date->translatedFormat('F \d\e Y'),
        };
    }

    /**
     * @return Collection<int, Collection<int, array>>
     */
    #[Computed]
    public function monthWeeks(): Collection
    {
        $firstOfMonth = Carbon::parse($this->cursor)->startOfMonth();

        $events = Event::with('category')->whereBetween('starts_at', [
            $firstOfMonth->copy()->startOfWeek(Carbon::SUNDAY),
            $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY),
        ])->orderBy('starts_at')->get()->groupBy(fn (Event $event) => $event->starts_at->format('Y-m-d'));

        return $this->buildWeeks($firstOfMonth, $events);
    }

    /**
     * 12 mini-grades (uma por mês do ano do cursor), cada uma no mesmo
     * formato de buildWeeks() — uma única consulta cobrindo o ano inteiro,
     * fatiada em memória por mês, em vez de 12 consultas separadas.
     *
     * @return Collection<int, array{label: string, weeks: Collection}>
     */
    #[Computed]
    public function yearMonths(): Collection
    {
        $year = (int) Carbon::parse($this->cursor)->format('Y');

        $events = Event::with('category')->whereBetween('starts_at', [
            Carbon::create($year, 1, 1)->startOfWeek(Carbon::SUNDAY),
            Carbon::create($year, 12, 31)->endOfWeek(Carbon::SATURDAY),
        ])->orderBy('starts_at')->get()->groupBy(fn (Event $event) => $event->starts_at->format('Y-m-d'));

        return collect(range(1, 12))->map(function (int $month) use ($year, $events) {
            $firstOfMonth = Carbon::create($year, $month, 1);

            return [
                'label' => $firstOfMonth->translatedFormat('F'),
                'weeks' => $this->buildWeeks($firstOfMonth, $events),
            ];
        });
    }

    /**
     * @return Collection<int, array{date: Carbon, isToday: bool, events: Collection<int, Event>}>
     */
    #[Computed]
    public function weekDays(): Collection
    {
        $startOfWeek = Carbon::parse($this->cursor)->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SATURDAY);

        $events = Event::with('category')->whereBetween('starts_at', [$startOfWeek, $endOfWeek])->orderBy('starts_at')->get();

        return collect(range(0, 6))->map(function (int $i) use ($startOfWeek, $events) {
            $date = $startOfWeek->copy()->addDays($i);

            return [
                'date' => $date,
                'isToday' => $date->isToday(),
                'events' => $events->filter(fn (Event $event) => $event->starts_at->isSameDay($date))->values(),
            ];
        });
    }

    /**
     * @return Collection<int, Event>
     */
    #[Computed]
    public function dayEvents(): Collection
    {
        return Event::with('category')->whereDate('starts_at', Carbon::parse($this->cursor))->orderBy('starts_at')->get();
    }

    /**
     * @return Collection<string, Collection<int, Event>>
     */
    #[Computed]
    public function listGroups(): Collection
    {
        $firstOfMonth = Carbon::parse($this->cursor)->startOfMonth();

        return Event::with('category')->whereBetween('starts_at', [$firstOfMonth, $firstOfMonth->copy()->endOfMonth()])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Event $event) => $event->starts_at->format('Y-m-d'));
    }

    /**
     * @return Collection<int, Event>
     */
    #[Computed]
    public function upcomingEvents(): Collection
    {
        return Event::with('category')->whereDate('starts_at', '>=', today())->orderBy('starts_at')->limit(5)->get();
    }

    public function render()
    {
        return view('livewire.admin.events.event-index')->layout('layouts.admin', ['title' => 'Eventos']);
    }
}
