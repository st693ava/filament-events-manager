<?php

namespace St693ava\FilamentEventsManager\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use St693ava\FilamentEventsManager\Models\EventLog;

class RecentTriggersWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 2;
    protected static ?string $heading = 'Triggers Recentes';

    public function table(Table $table): Table
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30);
        $endDate = $this->pageFilters['endDate'] ?? now();
        $ruleId = $this->pageFilters['ruleId'] ?? null;
        $triggerType = $this->pageFilters['triggerType'] ?? null;
        $status = $this->pageFilters['status'] ?? null;

        return $table
            ->query(
                EventLog::query()
                    ->with(['eventRule'])
                    ->when($startDate, fn (Builder $query) => $query->whereDate('triggered_at', '>=', $startDate))
                    ->when($endDate, fn (Builder $query) => $query->whereDate('triggered_at', '<=', $endDate))
                    ->when($ruleId, fn (Builder $query) => $query->where('event_rule_id', $ruleId))
                    ->when($triggerType, fn (Builder $query) => $query->where('trigger_type', $triggerType))
                    ->when($status, function (Builder $query) use ($status) {
                        if ($status === 'success') {
                            return $query->successful();
                        } elseif ($status === 'failed') {
                            return $query->failed();
                        }
                        return $query;
                    })
                    ->latest('triggered_at')
                    ->limit(50)
            )
            ->columns([
                TextColumn::make('eventRule.name')
                    ->label('Regra')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('event_name')
                    ->label('Evento')
                    ->limit(25)
                    ->searchable(),

                TextColumn::make('model_type')
                    ->label('Modelo')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '-';
                        }
                        $parts = explode('\\', $state);
                        return end($parts);
                    }),

                IconColumn::make('status')
                    ->label('Estado')
                    ->getStateUsing(function (EventLog $record): string {
                        $actions = $record->actions_executed;
                        if (empty($actions)) {
                            return 'unknown';
                        }

                        $failed = collect($actions)->where('status', 'failed')->count();
                        return $failed === 0 ? 'success' : 'failed';
                    })
                    ->icons([
                        'success' => Heroicon::OutlinedCheckCircle,
                        'failed' => Heroicon::OutlinedXCircle,
                        'unknown' => Heroicon::OutlinedQuestionMarkCircle,
                    ])
                    ->colors([
                        'success' => 'success',
                        'danger' => 'failed',
                        'gray' => 'unknown',
                    ]),

                TextColumn::make('execution_time_ms')
                    ->label('Tempo')
                    ->suffix(' ms')
                    ->color(fn (int $state): string => match (true) {
                        $state < 100 => 'success',
                        $state < 500 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('triggered_at')
                    ->label('Disparado em')
                    ->dateTime('H:i:s')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Ver Detalhes')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (EventLog $record): string =>
                        route('filament.admin.resources.event-logs.view', $record)
                    ),
            ])
            ->defaultSort('triggered_at', 'desc')
            ->poll('10s');
    }
}