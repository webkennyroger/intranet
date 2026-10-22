<?php

namespace App\Livewire\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Monta a grade de um mês em semanas completas (domingo a sábado),
 * incluindo os dias do mês anterior/seguinte que completam a primeira
 * e a última semana — usada tanto pela agenda pública quanto pelo
 * calendário admin de Eventos.
 */
trait BuildsCalendarGrid
{
    /**
     * @return Collection<int, Collection<int, array>>
     */
    private function buildWeeks(Carbon $firstOfMonth, Collection $eventsByDay): Collection
    {
        $startOfGrid = $firstOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfGrid = $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

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
}
