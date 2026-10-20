<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Calcula a série acumulada dos últimos 7 dias de uma query (quantos
 * registros já existiam até cada dia) e a variação percentual entre o
 * primeiro e o último dia — usada nos mini-gráficos (sparkline) dos cards
 * de estatística do admin.
 */
trait ComputesWeeklyTrend
{
    /**
     * @return array{values: array<int, int>, changePercent: float}
     */
    private function weeklyTrend(Builder|QueryBuilder $query, string $dateColumn): array
    {
        $days = collect(range(6, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->startOfDay());
        $windowStart = $days->first();

        $baseCount = (clone $query)->where($dateColumn, '<', $windowStart)->count();

        $dailyCounts = (clone $query)
            ->where($dateColumn, '>=', $windowStart)
            ->selectRaw('DATE('.$dateColumn.') as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $running = $baseCount;
        $values = $days->map(function ($day) use (&$running, $dailyCounts) {
            $running += (int) ($dailyCounts[$day->toDateString()] ?? 0);

            return $running;
        })->all();

        $first = $values[0];
        $last = $values[array_key_last($values)];
        $changePercent = $first > 0 ? round(($last - $first) / $first * 100, 1) : ($last > 0 ? 100.0 : 0.0);

        return ['values' => $values, 'changePercent' => $changePercent];
    }

    /**
     * @return array{values: array<int, int>, changePercent: float}
     */
    private function emptyWeeklyTrend(): array
    {
        return ['values' => [], 'changePercent' => 0];
    }
}
