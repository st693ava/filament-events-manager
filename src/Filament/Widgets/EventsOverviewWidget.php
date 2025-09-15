<?php

namespace St693ava\FilamentEventsManager\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use St693ava\FilamentEventsManager\Models\EventLog;
use St693ava\FilamentEventsManager\Models\EventRule;

class EventsOverviewWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30);
        $endDate = $this->pageFilters['endDate'] ?? now();
        $ruleId = $this->pageFilters['ruleId'] ?? null;
        $triggerType = $this->pageFilters['triggerType'] ?? null;
        $status = $this->pageFilters['status'] ?? null;

        // Base query for event logs
        $logsQuery = EventLog::query()
            ->when($startDate, fn (Builder $query) => $query->whereDate('triggered_at', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('triggered_at', '<=', $endDate))
            ->when($ruleId, fn (Builder $query) => $query->where('event_rule_id', $ruleId))
            ->when($triggerType, fn (Builder $query) => $query->where('trigger_type', $triggerType));

        // Total events triggered
        $totalEvents = (clone $logsQuery)->count();

        // Success/failure statistics
        $successfulEvents = (clone $logsQuery)->successful()->count();
        $failedEvents = (clone $logsQuery)->failed()->count();

        // Calculate success rate
        $successRate = $totalEvents > 0 ? round(($successfulEvents / $totalEvents) * 100, 1) : 0;

        // Active rules count
        $activeRules = EventRule::where('is_active', true)->count();

        // Average execution time
        $avgExecutionTime = (clone $logsQuery)->avg('execution_time_ms') ?? 0;

        // Events today
        $eventsToday = (clone $logsQuery)
            ->whereDate('triggered_at', now()->toDateString())
            ->count();

        return [
            Stat::make('Total de Eventos', number_format($totalEvents))
                ->description('Eventos disparados no período')
                ->descriptionIcon(Heroicon::OutlinedChartBar)
                ->color('primary'),

            Stat::make('Taxa de Sucesso', $successRate . '%')
                ->description($successfulEvents . ' sucessos de ' . $totalEvents . ' total')
                ->descriptionIcon($successRate >= 95 ? Heroicon::OutlinedCheckCircle : ($successRate >= 80 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedXCircle))
                ->color($successRate >= 95 ? 'success' : ($successRate >= 80 ? 'warning' : 'danger')),

            Stat::make('Regras Ativas', number_format($activeRules))
                ->description('Regras configuradas e ativas')
                ->descriptionIcon(Heroicon::OutlinedBolt)
                ->color('info'),

            Stat::make('Tempo Médio', round($avgExecutionTime, 2) . ' ms')
                ->description('Tempo médio de execução')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($avgExecutionTime < 100 ? 'success' : ($avgExecutionTime < 500 ? 'warning' : 'danger')),

            Stat::make('Eventos Hoje', number_format($eventsToday))
                ->description('Disparados hoje')
                ->descriptionIcon(Heroicon::OutlinedCalendar)
                ->color('primary'),

            Stat::make('Eventos Falharam', number_format($failedEvents))
                ->description('Requerem atenção')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color($failedEvents > 0 ? 'danger' : 'success'),
        ];
    }
}