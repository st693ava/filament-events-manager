<?php

namespace St693ava\FilamentEventsManager\Filament\Resources;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use St693ava\FilamentEventsManager\Filament\Resources\EventRuleResource\Pages;
use St693ava\FilamentEventsManager\Models\EventRule;

class EventRuleResource extends Resource
{
    protected static ?string $model = EventRule::class;

    protected static bool $hasTitleCaseModelLabel = false;


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Regras de Eventos';

    protected static ?string $modelLabel = 'Regra de Evento';

    protected static ?string $pluralModelLabel = 'Regras de Eventos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Ativa')
                                    ->default(true),

                                Forms\Components\TextInput::make('priority')
                                    ->label('Prioridade')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Maior número = maior prioridade'),
                            ]),
                    ]),

                Section::make('Configuração do Trigger')
                    ->schema([
                        Forms\Components\Select::make('trigger_type')
                            ->label('Tipo de Trigger')
                            ->options([
                                'eloquent' => 'Eventos Eloquent',
                                'query' => 'Consultas de Base de Dados',
                                'custom' => 'Eventos Personalizados',
                                'schedule' => 'Baseado em Horário',
                            ])
                            ->default('eloquent')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('trigger_config', [])),

                        Group::make()
                            ->schema(fn($get): array => match ($get('trigger_type')) {
                                'eloquent' => static::getEloquentTriggerFields(),
                                default => [],
                            }),
                    ]),

                Section::make('Condições')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Placeholder::make('condition_helper')
                            ->label('Como Funciona')
                            ->content('As condições são avaliadas em sequência conforme a prioridade e operadores lógicos definidos.')
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('conditions')
                            ->label('Condições da Regra')
                            ->relationship('conditions')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('field_path')
                                            ->label('Campo')
                                            ->placeholder('ex: email, user.name, order.total')
                                            ->required()
                                            ->columnSpan(2)
                                            ->datalist([
                                                'id',
                                                'created_at',
                                                'updated_at',
                                                'user.id',
                                                'user.name',
                                                'user.email',
                                            ]),

                                        Forms\Components\Select::make('operator')
                                            ->label('Operador')
                                            ->options([
                                                '=' => 'Igual a',
                                                '!=' => 'Diferente de',
                                                '>' => 'Maior que',
                                                '<' => 'Menor que',
                                                '>=' => 'Maior ou igual a',
                                                '<=' => 'Menor ou igual a',
                                                'contains' => 'Contém',
                                                'starts_with' => 'Começa com',
                                                'ends_with' => 'Termina com',
                                                'in' => 'Está em (lista)',
                                                'not_in' => 'Não está em (lista)',
                                                'changed' => 'Foi alterado',
                                                'was' => 'Era (valor anterior)',
                                            ])
                                            ->required()
                                            ->live()
                                            ->columnSpan(1),

                                        Group::make()
                                            ->schema(fn($get): array => match ($get('operator')) {
                                                'in', 'not_in' => [
                                                    Forms\Components\TagsInput::make('value')
                                                        ->label('Lista de Valores')
                                                        ->placeholder('Digite valores e pressione Enter')
                                                        ->required(),
                                                ],
                                                'changed' => [
                                                    Forms\Components\Placeholder::make('no_value')
                                                        ->label('Valor')
                                                        ->content('Não requer valor'),
                                                ],
                                                default => [
                                                    Forms\Components\TextInput::make('value')
                                                        ->label('Valor')
                                                        ->placeholder('Valor para comparação')
                                                        ->required(),
                                                ],
                                            })
                                            ->columnSpan(1),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('logical_operator')
                                            ->label('Operador Lógico')
                                            ->options([
                                                'AND' => 'E',
                                                'OR' => 'OU',
                                            ])
                                            ->default('AND')
                                            ->helperText('Como esta condição se relaciona com a próxima'),

                                        Forms\Components\TextInput::make('priority')
                                            ->label('Prioridade')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Ordem de avaliação (maior = primeiro)'),
                                    ]),
                            ])
                            ->collapsible()
                            ->collapsed(false)
                            ->addActionLabel('Adicionar Condição')
                            ->reorderable()
                            ->helperText('Se não adicionar condições, a regra será sempre executada. Use prioridades e operadores lógicos para controlar a avaliação.'),
                    ]),

                Section::make('Ações')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Repeater::make('actions')
                            ->label('Ações a Executar')
                            ->relationship('actions')
                            ->schema([
                                Forms\Components\Select::make('action_type')
                                    ->label('Tipo de Ação')
                                    ->options([
                                        'email' => 'Enviar Email',
                                        'activity_log' => 'Registo de Atividade',
                                        'webhook' => 'Webhook HTTP',
                                        'notification' => 'Notificação do Sistema',
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn($set) => $set('action_config', [])),

                                Group::make()
                                    ->schema(fn($get): array => match ($get('action_type')) {
                                        'email' => static::getEmailActionFields(),
                                        'activity_log' => static::getActivityLogActionFields(),
                                        'webhook' => static::getWebhookActionFields(),
                                        'notification' => static::getNotificationActionFields(),
                                        default => [],
                                    }),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Ação Ativa')
                                    ->default(true),
                            ])
                            ->collapsible()
                            ->collapsed(false)
                            ->addActionLabel('Adicionar Ação')
                            ->minItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trigger_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'eloquent' => 'success',
                        'query' => 'warning',
                        'custom' => 'info',
                        'schedule' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'eloquent' => 'Eloquent',
                        'query' => 'Consulta BD',
                        'custom' => 'Personalizado',
                        'schedule' => 'Horário',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('conditions_count')
                    ->label('Condições')
                    ->counts('conditions')
                    ->suffix(' condições'),

                Tables\Columns\TextColumn::make('actions_count')
                    ->label('Ações')
                    ->counts('actions')
                    ->suffix(' ações'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridade')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_type')
                    ->label('Tipo de Trigger')
                    ->options([
                        'eloquent' => 'Eloquent',
                        'query' => 'Consulta BD',
                        'custom' => 'Personalizado',
                        'schedule' => 'Horário',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Apenas ativas')
                    ->falseLabel('Apenas inativas')
                    ->native(false),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->icon(Heroicon::OutlinedPencil),
                Actions\DeleteAction::make()
                    ->icon(Heroicon::OutlinedTrash),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->icon(Heroicon::OutlinedTrash),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventRules::route('/'),
            'create' => Pages\CreateEventRule::route('/create'),
            'edit' => Pages\EditEventRule::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentTriggerFields(): array
    {
        return [
            Forms\Components\TextInput::make('trigger_config.model')
                ->label('Classe do Modelo')
                ->placeholder('App\\Models\\User')
                ->helperText('Nome completo da classe do modelo')
                ->required(),

            Forms\Components\CheckboxList::make('trigger_config.events')
                ->label('Eventos a Monitorizar')
                ->options([
                    'created' => 'Criado',
                    'updated' => 'Atualizado',
                    'deleted' => 'Eliminado',
                    'saved' => 'Guardado',
                    'restored' => 'Restaurado',
                ])
                ->required()
                ->columns(2),
        ];
    }

    protected static function getEmailActionFields(): array
    {
        return [
            Forms\Components\TextInput::make('action_config.to')
                ->label('Para (Email)')
                ->email()
                ->required()
                ->helperText('Usar {user.email} para email do utilizador'),

            Forms\Components\TextInput::make('action_config.subject')
                ->label('Assunto')
                ->required()
                ->helperText('Usar {model.name} para dados do modelo'),

            Forms\Components\Textarea::make('action_config.body')
                ->label('Corpo do Email')
                ->rows(4)
                ->required()
                ->helperText('HTML suportado. Usar {model.field} para placeholders'),

            Forms\Components\TextInput::make('action_config.cc')
                ->label('CC (Cópia)')
                ->email(),

            Forms\Components\TextInput::make('action_config.bcc')
                ->label('BCC (Cópia Oculta)')
                ->email(),
        ];
    }

    protected static function getActivityLogActionFields(): array
    {
        return [
            Forms\Components\TextInput::make('action_config.description')
                ->label('Descrição')
                ->required()
                ->helperText('Usar {model.field} para dados do modelo'),

            Forms\Components\TextInput::make('action_config.log_name')
                ->label('Nome do Log')
                ->default('events_manager')
                ->helperText('Agrupamento dos logs'),

            Forms\Components\KeyValue::make('action_config.properties')
                ->label('Propriedades Personalizadas')
                ->keyLabel('Chave')
                ->valueLabel('Valor'),
        ];
    }

    protected static function getWebhookActionFields(): array
    {
        return [
            Forms\Components\TextInput::make('action_config.url')
                ->label('URL do Webhook')
                ->url()
                ->required()
                ->helperText('Usar {{model.campo}} para placeholders dinâmicos'),

            Forms\Components\Select::make('action_config.method')
                ->label('Método HTTP')
                ->options([
                    'GET' => 'GET',
                    'POST' => 'POST',
                    'PUT' => 'PUT',
                    'PATCH' => 'PATCH',
                    'DELETE' => 'DELETE',
                ])
                ->default('POST')
                ->required(),

            Forms\Components\KeyValue::make('action_config.headers')
                ->label('Headers HTTP')
                ->keyLabel('Nome do Header')
                ->valueLabel('Valor')
                ->helperText('Headers personalizados. Suporta placeholders.'),

            Forms\Components\KeyValue::make('action_config.payload')
                ->label('Payload Personalizado')
                ->keyLabel('Chave')
                ->valueLabel('Valor')
                ->helperText('Se vazio, enviará dados do evento automaticamente.'),

            Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('action_config.timeout')
                        ->label('Timeout (segundos)')
                        ->numeric()
                        ->default(30)
                        ->minValue(1)
                        ->maxValue(300),

                    Forms\Components\TextInput::make('action_config.retries')
                        ->label('Número de Tentativas')
                        ->numeric()
                        ->default(3)
                        ->minValue(0)
                        ->maxValue(10),
                ]),
        ];
    }

    protected static function getNotificationActionFields(): array
    {
        return [
            Forms\Components\TextInput::make('action_config.title')
                ->label('Título da Notificação')
                ->required()
                ->placeholder('Evento {{event.name}} disparado')
                ->helperText('Usar {{model.campo}} para placeholders'),

            Forms\Components\Textarea::make('action_config.message')
                ->label('Mensagem')
                ->required()
                ->rows(3)
                ->placeholder('O evento foi disparado com sucesso...')
                ->helperText('Suporta placeholders dinâmicos'),

            Forms\Components\TextInput::make('action_config.action_url')
                ->label('URL da Ação (opcional)')
                ->url()
                ->placeholder('https://app.exemplo.com/model/{{model.id}}')
                ->helperText('Link para ação na notificação'),

            Forms\Components\CheckboxList::make('action_config.channels')
                ->label('Canais de Notificação')
                ->options([
                    'database' => 'Base de Dados',
                    'mail' => 'Email',
                    'broadcast' => 'Broadcasting (tempo real)',
                ])
                ->default(['database'])
                ->required()
                ->columns(1),

            Forms\Components\Select::make('action_config.recipient_type')
                ->label('Tipo de Destinatário')
                ->options([
                    'users' => 'Utilizadores Específicos',
                    'emails' => 'Lista de Emails',
                    'dynamic' => 'Campo Dinâmico',
                    'event_user' => 'Utilizador do Evento',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(fn($set) => $set('action_config.user_ids', null)),

            Group::make()
                ->schema(fn($get): array => match ($get('action_config.recipient_type')) {
                    'users' => [
                        Forms\Components\TagsInput::make('action_config.user_ids')
                            ->label('IDs dos Utilizadores')
                            ->placeholder('1, 2, 3...')
                            ->helperText('Lista de IDs de utilizadores'),
                    ],
                    'emails' => [
                        Forms\Components\TagsInput::make('action_config.emails')
                            ->label('Lista de Emails')
                            ->placeholder('admin@exemplo.com')
                            ->helperText('Suporta placeholders: {{user.email}}'),
                    ],
                    'dynamic' => [
                        Forms\Components\TextInput::make('action_config.field_path')
                            ->label('Campo para Email')
                            ->placeholder('user.email')
                            ->helperText('Campo que contém o email do destinatário')
                            ->required(),
                    ],
                    default => [],
                }),
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

        return static::getModel()::where('is_active', true)->count();
    }
}
