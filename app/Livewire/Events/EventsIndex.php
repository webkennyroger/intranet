<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Página de eventos/agenda: calendário mensal com os eventos publicados,
 * navegável entre meses, com uma lista dos próximos eventos abaixo.
 */
class EventsIndex extends Component
{
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

    /**
     * Monta a grade do mês em semanas completas (domingo a sábado),
     * incluindo os dias do mês anterior/seguinte que completam a primeira
     * e a última semana — cada dia já vem com seus eventos publicados.
     *
     * @return Collection<int, Collection<int, array>>
     */
    private function buildWeeks(Carbon $firstOfMonth, Collection $eventsByDay): Collection
    {
        $startOfGrid = $firstOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfGrid = $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = collect();
        $cursor = $startOfGrid->copy();

        while ($cursor->lte($endOfGrid)) {
            $week = collect();

            for ($i = 0; $i < 7; $i++) {
                $week->push([
                    'date' => $cursor->copy(),
                    'inMonth' => $cursor->month === $firstOfMonth->month,
                    'isToday' => $cursor->isToday(),
                    'events' => $eventsByDay->get($cursor->format('Y-m-d'), collect()),
                ]);
                $cursor->addDay();
            }

            $weeks->push($week);
        }

        return $weeks;
    }

    public function render()
    {
        $firstOfMonth = Carbon::create($this->year, $this->month, 1);

        $monthEvents = Event::where('is_published', true)
            ->whereBetween('starts_at', [
                $firstOfMonth->copy()->startOfWeek(Carbon::SUNDAY),
                $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Event $event) => $event->starts_at->format('Y-m-d'));

        $upcomingEvents = Event::where('is_published', true)
            ->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at')
            ->take(5)
            ->get();

        return view('livewire.events.events-index', [
            'weeks' => $this->buildWeeks($firstOfMonth, $monthEvents),
            'monthLabel' => $firstOfMonth->translatedFormat('F \d\e Y'),
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
