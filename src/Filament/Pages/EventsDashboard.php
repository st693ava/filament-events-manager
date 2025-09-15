<?php

namespace St693ava\FilamentEventsManager\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use St693ava\FilamentEventsManager\Filament\Widgets\EventsOverviewWidget;
use St693ava\FilamentEventsManager\Filament\Widgets\RecentTriggersWidget;
use St693ava\FilamentEventsManager\Filament\Widgets\ActiveRulesWidget;
use St693ava\FilamentEventsManager\Filament\Widgets\PerformanceWidget;

class EventsDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/events-dashboard';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;
    protected static ?string $navigationLabel = 'Dashboard de Eventos';
    protected static ?string $title = 'Dashboard de Eventos';
    protected static ?int $navigationSort = 1;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filtros de Período')
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Data de Início')
                            ->default(now()->subDays(30)),

                        DatePicker::make('endDate')
                            ->label('Data de Fim')
                            ->default(now()),

                        Select::make('ruleId')
                            ->label('Regra Específica')
                            ->placeholder('Todas as regras')
                            ->options(function () {
                                return \St693ava\FilamentEventsManager\Models\EventRule::pluck('name', 'id');
                            })
                            ->searchable(),

                        Select::make('triggerType')
                            ->label('Tipo de Trigger')
                            ->placeholder('Todos os tipos')
                            ->options([
                                'eloquent' => 'Eloquent',
                                'query' => 'Consulta BD',
                                'custom' => 'Personalizado',
                                'schedule' => 'Horário',
                            ]),

                        Select::make('status')
                            ->label('Estado')
                            ->placeholder('Todos os estados')
                            ->options([
                                'success' => 'Sucesso',
                                'failed' => 'Falha',
                                'partial' => 'Parcial',
                            ]),
                    ])
                    ->columns(3),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            EventsOverviewWidget::class,
            RecentTriggersWidget::class,
            ActiveRulesWidget::class,
            PerformanceWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-events-manager.filament.navigation_group', 'Gestão de Eventos');
    }

    public static function canAccess(): bool
    {
        return true; // Adjust based on your authorization needs
    }
}
