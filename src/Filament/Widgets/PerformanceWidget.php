<?php

namespace St693ava\FilamentEventsManager\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use St693ava\FilamentEventsManager\Models\EventLog;

class PerformanceWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Métricas de Performance';
    protected int|string|array $columnSpan = 1;

    public function getDescription(): ?string
    {
        return 'Eventos disparados nos últimos dias';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30);
        $endDate = $this->pageFilters['endDate'] ?? now();
        $ruleId = $this->pageFilters['ruleId'] ?? null;
        $triggerType = $this->pageFilters['triggerType'] ?? null;

        // Generate last 14 days of data
        $days = collect();
        $current = now()->subDays(13);

        for ($i = 0; $i < 14; $i++) {
            $date = $current->copy()->addDays($i);

            $eventsCount = EventLog::query()
                ->whereDate('triggered_at', $date->toDateString())
                ->when($ruleId, fn (Builder $query) => $query->where('event_rule_id', $ruleId))
                ->when($triggerType, fn (Builder $query) => $query->where('trigger_type', $triggerType))
                ->count();

            $successCount = EventLog::query()
                ->whereDate('triggered_at', $date->toDateString())
                ->when($ruleId, fn (Builder $query) => $query->where('event_rule_id', $ruleId))
                ->when($triggerType, fn (Builder $query) => $query->where('trigger_type', $triggerType))
                ->successful()
                ->count();

            $failureCount = EventLog::query()
                ->whereDate('triggered_at', $date->toDateString())
                ->when($ruleId, fn (Builder $query) => $query->where('event_rule_id', $ruleId))
                ->when($triggerType, fn (Builder $query) => $query->where('trigger_type', $triggerType))
                ->failed()
                ->count();

            $avgExecutionTime = EventLog::query()
                ->whereDate('triggered_at', $date->toDateString())
                ->when($ruleId, fn (Builder $query) => $query->where('event_rule_id', $ruleId))
                ->when($triggerType, fn (Builder $query) => $query->where('trigger_type', $triggerType))
                ->avg('execution_time_ms') ?? 0;

            $days->push([
                'date' => $date->format('d/m'),
                'total' => $eventsCount,
                'success' => $successCount,
                'failure' => $failureCount,
                'avg_time' => round($avgExecutionTime, 2),
            ]);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total de Eventos',
                    'data' => $days->pluck('total')->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Sucessos',
                    'data' => $days->pluck('success')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
                [
                    'label' => 'Falhas',
                    'data' => $days->pluck('failure')->toArray(),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
            ],
            'labels' => $days->pluck('date')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'elements' => [
                'point' => [
                    'radius' => 3,
                    'hoverRadius' => 6,
                ],
                'line' => [
                    'tension' => 0.3,
                ],
            ],
        ];
    }
}