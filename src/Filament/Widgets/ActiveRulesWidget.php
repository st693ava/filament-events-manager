<?php

namespace St693ava\FilamentEventsManager\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use St693ava\FilamentEventsManager\Models\EventRule;

class ActiveRulesWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 1;
    protected static ?string $heading = 'Regras Ativas';

    public function table(Table $table): Table
    {
        $triggerType = $this->pageFilters['triggerType'] ?? null;

        return $table
            ->query(
                EventRule::query()
                    ->where('is_active', true)
                    ->withCount('conditions', 'actions')
                    ->when($triggerType, fn (Builder $query) => $query->where('trigger_type', $triggerType))
                    ->orderBy('priority', 'desc')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->limit(20)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('trigger_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'eloquent' => 'success',
                        'query' => 'warning',
                        'custom' => 'info',
                        'schedule' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'eloquent' => 'Eloquent',
                        'query' => 'Consulta',
                        'custom' => 'Custom',
                        'schedule' => 'Horário',
                        default => $state,
                    }),

                TextColumn::make('conditions_count')
                    ->label('Condições')
                    ->alignCenter()
                    ->color('info'),

                TextColumn::make('actions_count')
                    ->label('Ações')
                    ->alignCenter()
                    ->color('warning'),

                TextColumn::make('priority')
                    ->label('Prioridade')
                    ->alignCenter()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Editar')
                    ->icon(Heroicon::OutlinedPencil)
                    ->url(fn (EventRule $record): string =>
                        route('filament.admin.resources.event-rules.edit', $record)
                    ),

                Action::make('view_logs')
                    ->label('Ver Logs')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn (EventRule $record): string =>
                        route('filament.admin.resources.event-logs.index', [
                            'tableFilters[event_rule_id][value]' => $record->id,
                        ])
                    ),
            ])
            ->emptyStateHeading('Nenhuma regra ativa')
            ->emptyStateDescription('Crie algumas regras de eventos para começar a monitorizar.')
            ->emptyStateIcon(Heroicon::OutlinedBolt)
            ->poll('30s');
    }
}