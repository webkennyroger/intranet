<?php

namespace App\Livewire\Site\Events;

use App\Livewire\Concerns\BuildsCalendarGrid;
use App\Models\Event;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Página de eventos/agenda: calendário mensal com os eventos publicados,
 * navegável entre meses.
 */
class EventsIndex extends Component
{
    use BuildsCalendarGrid;

    public int $month;

    public int $year;

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->month = $date->month;
        $this->year = $date->year;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->month = $date->month;
        $this->year = $date->year;
    }

    public function goToToday(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function render()
    {
        $firstOfMonth = Carbon::create($this->year, $this->month, 1);

        $monthEvents = Event::where('is_published', true)
            ->whereBetween('starts_at', [
                $firstOfMonth->copy()->startOfWeek(Carbon::SUNDAY),
                $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY),
            ])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Event $event) => $event->starts_at->format('Y-m-d'));

        $upcomingEvents = Event::where('is_published', true)
            ->whereDate('starts_at', '>=', today())
            ->orderBy('starts_at')
            ->limit(6)
            ->get();

        return view('livewire.site.events.events-index', [
            'weeks' => $this->buildWeeks($firstOfMonth, $monthEvents),
            'monthLabel' => $firstOfMonth->translatedFormat('F \d\e Y'),
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
