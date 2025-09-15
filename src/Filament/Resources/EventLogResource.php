<?php

namespace St693ava\FilamentEventsManager\Filament\Resources;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use St693ava\FilamentEventsManager\Filament\Resources\EventLogResource\Pages;
use St693ava\FilamentEventsManager\Models\EventLog;

class EventLogResource extends Resource
{
    protected static ?string $model = EventLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Logs de Eventos';

    protected static bool $hasTitleCaseModelLabel = false;


    protected static ?string $modelLabel = 'Log de Evento';

    protected static ?string $pluralModelLabel = 'Logs de Eventos';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Evento')
                    ->schema([
                        Forms\Components\TextInput::make('eventRule.name')
                            ->label('Regra')
                            ->disabled(),

                        Forms\Components\TextInput::make('event_name')
                            ->label('Nome do Evento')
                            ->disabled(),

                        Forms\Components\TextInput::make('trigger_type')
                            ->label('Tipo de Trigger')
                            ->disabled(),

                        Forms\Components\TextInput::make('model_type')
                            ->label('Tipo de Modelo')
                            ->disabled(),

                        Forms\Components\TextInput::make('model_id')
                            ->label('ID do Modelo')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('triggered_at')
                            ->label('Executado em')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Contexto do Utilizador')
                    ->schema([
                        Forms\Components\TextInput::make('user_name')
                            ->label('Nome do Utilizador')
                            ->disabled(),

                        Forms\Components\TextInput::make('ip_address')
                            ->label('Endereço IP')
                            ->disabled(),

                        Forms\Components\TextInput::make('request_method')
                            ->label('Método HTTP')
                            ->disabled(),

                        Forms\Components\Textarea::make('request_url')
                            ->label('URL do Request')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Detalhes da Execução')
                    ->schema([
                        Forms\Components\TextInput::make('execution_time_ms')
                            ->label('Tempo de Execução (ms)')
                            ->disabled(),

                        Forms\Components\KeyValue::make('context')
                            ->label('Contexto Completo')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('actions_executed')
                            ->label('Ações Executadas')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('eventRule.name')
                    ->label('Regra')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_name')
                    ->label('Evento')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('model_type')
                    ->label('Modelo')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '-';
                        }

                        $parts = explode('\\', $state);

                        return end($parts);
                    }),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('Utilizador')
                    ->searchable()
                    ->placeholder('Sistema'),

                Tables\Columns\TextColumn::make('execution_time_ms')
                    ->label('Tempo (ms)')
                    ->sortable()
                    ->suffix(' ms')
                    ->color(fn(int $state): string => match (true) {
                        $state < 100 => 'success',
                        $state < 500 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('actions_executed')
                    ->label('Estado')
                    ->formatStateUsing(function (array $state): string {
                        $total = count($state);
                        $failed = count(array_filter($state, fn($action) => ($action['status'] ?? 'success') === 'failed'));

                        if ($failed === 0) {
                            return "✓ {$total} ações";
                        }

                        return "✗ {$failed}/{$total} falharam";
                    })
                    ->color(function (array $state): string {
                        $failed = count(array_filter($state, fn($action) => ($action['status'] ?? 'success') === 'failed'));

                        return $failed === 0 ? 'success' : 'danger';
                    }),

                Tables\Columns\TextColumn::make('triggered_at')
                    ->label('Data/Hora')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_rule_id')
                    ->label('Regra')
                    ->relationship('eventRule', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('trigger_type')
                    ->label('Tipo de Trigger')
                    ->options([
                        'eloquent' => 'Eloquent',
                        'query' => 'Consulta BD',
                        'custom' => 'Personalizado',
                        'schedule' => 'Horário',
                    ]),

                Tables\Filters\Filter::make('triggered_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('De'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('triggered_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('triggered_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('successful')
                    ->label('Apenas Sucessos')
                    ->query(fn(Builder $query): Builder => $query->successful()),

                Tables\Filters\Filter::make('failed')
                    ->label('Apenas Falhas')
                    ->query(fn(Builder $query): Builder => $query->failed()),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->icon(Heroicon::OutlinedEye),
            ])
            ->defaultSort('triggered_at', 'desc')
            ->poll('10s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventLogs::route('/'),
            'view' => Pages\ViewEventLog::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-events-manager.filament.navigation_group', 'Gestão de Eventos');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('filament-events-manager.filament.navigation_badge', true)) {
            return null;
        }

        return static::getModel()::where('triggered_at', '>=', now()->subDay())->count();
    }

    public static function canCreate(): bool
    {
        return false; // Logs não podem ser criados manualmente
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // Logs não podem ser editados
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // Logs não podem ser eliminados individualmente
    }
}
