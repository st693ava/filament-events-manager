# st693ava/filament-events-manager - Plano de Desenvolvimento

## Índice
1. [Visão Geral](#visão-geral)
2. [Arquitetura Técnica](#arquitetura-técnica)
3. [Especificações Funcionais](#especificações-funcionais)
4. [Design da Base de Dados](#design-da-base-de-dados)
5. [Componentes Filament](#componentes-filament)
6. [Sistema de Eventos](#sistema-de-eventos)
7. [Sistema de Ações](#sistema-de-ações)
8. [Integrações](#integrações)
9. [Interface de Utilizador](#interface-de-utilizador)
10. [Roadmap de Desenvolvimento](#roadmap-de-desenvolvimento)
11. [Testing Strategy](#testing-strategy)
12. [Documentação](#documentação)
13. [Considerações Técnicas](#considerações-técnicas)

---

## Visão Geral

### Objetivo
Criar um package Filament v4 que permita configurar dinamicamente triggers de eventos e ações automáticas através de uma interface gráfica, sem necessidade de escrever código.

### Problemas que Resolve
- **Automação Manual**: Elimina a necessidade de criar observers, listeners e jobs específicos para cada regra de negócio
- **Flexibilidade**: Permite configurar regras complexas através de UI em vez de código hardcoded
- **Auditoria**: Fornece rastreamento completo de quem executou que ações e quando
- **Manutenção**: Reduz a complexidade do código ao centralizar lógica de eventos
- **Compliance**: Facilita requisitos de auditoria e GDPR através de logging automático

### Público-Alvo
- Developers Laravel/Filament que precisam de automação de workflows
- Product managers que querem configurar regras sem envolver developers
- Empresas que precisam de compliance e auditoria
- Equipas que querem reduzir código boilerplate

### Valor Proposição
**"O Zapier/IFTTT da sua aplicação Laravel"** - Configure triggers e ações complexas através de interface visual, sem código.

---

## Arquitetura Técnica

### Estrutura do Package
```
st693ava/filament-events-manager/
├── config/
│   └── filament-events-manager.php
├── database/
│   └── migrations/
│       ├── create_event_rules_table.php
│       ├── create_event_rule_conditions_table.php
│       ├── create_event_rule_actions_table.php
│       └── create_event_logs_table.php
├── resources/
│   └── views/
│       ├── components/
│       └── pages/
├── src/
│   ├── Actions/
│   │   ├── Contracts/
│   │   │   └── ActionExecutor.php
│   │   ├── Executors/
│   │   │   ├── EmailAction.php
│   │   │   ├── WebhookAction.php
│   │   │   ├── ActivityLogAction.php
│   │   │   ├── NotificationAction.php
│   │   │   └── CustomCodeAction.php
│   │   └── ActionManager.php
│   ├── Commands/
│   │   └── TestEventRuleCommand.php
│   ├── Conditions/
│   │   ├── ConditionEvaluator.php
│   │   ├── QueryConditionParser.php
│   │   └── ModelConditionChecker.php
│   ├── Events/
│   │   ├── EventRuleTriggered.php
│   │   ├── ActionExecuted.php
│   │   └── RuleValidationFailed.php
│   ├── Facades/
│   │   └── EventsManager.php
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── EventRuleResource/
│   │   │   │   ├── Pages/
│   │   │   │   └── RelationManagers/
│   │   │   └── EventLogResource/
│   │   ├── Widgets/
│   │   │   ├── EventsOverviewWidget.php
│   │   │   ├── RecentTriggersWidget.php
│   │   │   └── ActiveRulesWidget.php
│   │   └── Pages/
│   │       ├── EventsDashboard.php
│   │       └── RuleTester.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── WebhookController.php
│   │   └── Middleware/
│   │       └── CaptureEventContext.php
│   ├── Listeners/
│   │   ├── EloquentEventListener.php
│   │   ├── QueryEventListener.php
│   │   └── GlobalEventInterceptor.php
│   ├── Models/
│   │   ├── EventRule.php
│   │   ├── EventRuleCondition.php
│   │   ├── EventRuleAction.php
│   │   └── EventLog.php
│   ├── Services/
│   │   ├── EventInterceptor.php
│   │   ├── ContextCollector.php
│   │   ├── RuleEngine.php
│   │   └── TemplateRenderer.php
│   ├── Support/
│   │   ├── EventContext.php
│   │   ├── SqlParser.php
│   │   └── ModelIntrospector.php
│   ├── EventsManagerServiceProvider.php
│   └── FilamentEventsManagerPlugin.php
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── TestCase.php
├── composer.json
├── README.md
└── CHANGELOG.md
```

### Componentes Principais

#### 1. Event Interceptor
- **Responsabilidade**: Capturar todos os eventos Eloquent e DB queries
- **Implementação**: Event listeners globais com pattern matching
- **Performance**: Cache de regras ativas para evitar overhead

#### 2. Rule Engine
- **Responsabilidade**: Avaliar condições e decidir que ações executar
- **Implementação**: Sistema de condições encadeáveis com operadores lógicos
- **Flexibilidade**: Suporte a condições complexas e custom evaluators

#### 3. Action Executors
- **Responsabilidade**: Executar ações configuradas quando regras são ativadas
- **Implementação**: Strategy pattern com executors pluggáveis
- **Extensibilidade**: Interface para custom actions

#### 4. Context Collector
- **Responsabilidade**: Recolher informação contextual (user, request, session)
- **Implementação**: Middleware e service para capturar dados relevantes
- **Segurança**: Sanitização de dados sensíveis

---

## Especificações Funcionais

### User Stories

#### Como Developer
- **US1**: Quero instalar o package e ter uma dashboard funcional em menos de 5 minutos
- **US2**: Quero criar regras visuais sem tocar em código
- **US3**: Quero testar regras antes de as ativar em produção
- **US4**: Quero exportar/importar configurações entre ambientes
- **US5**: Quero estender o sistema com custom actions

#### Como Product Manager
- **US6**: Quero configurar alertas automáticos quando KPIs mudam
- **US7**: Quero setup de approval workflows para operações críticas
- **US8**: Quero relatórios de compliance automáticos
- **US9**: Quero dashboard em tempo real de atividade do sistema

#### Como Admin/Security
- **US10**: Quero audit trail completo de todas as ações
- **US11**: Quero alertas de segurança para padrões suspeitos
- **US12**: Quero controlo granular de permissões por regra
- **US13**: Quero backup automático de configurações críticas

### Funcionalidades Core

#### 1. Gestão de Regras
- ✅ CRUD completo de regras
- ✅ Ativação/desativação individual
- ✅ Cloning e templating
- ✅ Versionamento de regras
- ✅ Bulk operations

#### 2. Sistema de Condições
- ✅ Conditions builder visual
- ✅ Operadores: =, !=, >, <, >=, <=, contains, starts_with, ends_with, in, not_in
- ✅ Comparação com valores estáticos ou dinâmicos
- ✅ Operadores lógicos: AND, OR, NOT
- ✅ Agrupamento de condições
- ✅ Suporte a related models

#### 3. Tipos de Triggers
- ✅ **Eloquent Events**: created, updated, deleted, restored, retrieved
- ✅ **Database Queries**: INSERT, UPDATE, DELETE, SELECT
- ✅ **Custom Events**: Eventos específicos da aplicação
- ✅ **Schedule Based**: Time-based triggers
- ✅ **API Calls**: Webhook triggers

#### 4. Tipos de Ações
- ✅ **Email**: Templates com variáveis dinâmicas
- ✅ **Notifications**: Database, broadcast, slack
- ✅ **Webhooks**: HTTP requests com payload customizável
- ✅ **Activity Log**: Integração com Spatie
- ✅ **Database**: Criar/atualizar registos
- ✅ **Queue Jobs**: Dispatch jobs com payload
- ✅ **Custom Code**: Execute closures ou commands

---

## Design da Base de Dados

### Schema das Tabelas

#### event_rules
```sql
CREATE TABLE event_rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    trigger_type ENUM('eloquent', 'query', 'custom', 'schedule') NOT NULL,
    trigger_config JSON NOT NULL, -- Model class, events, table, etc.
    is_active BOOLEAN DEFAULT TRUE,
    priority INTEGER DEFAULT 0, -- Ordem de execução
    created_by BIGINT NULL,
    updated_by BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_active_priority (is_active, priority),
    INDEX idx_trigger_type (trigger_type),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);
```

#### event_rule_conditions
```sql
CREATE TABLE event_rule_conditions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_rule_id BIGINT NOT NULL,
    field_path VARCHAR(255) NOT NULL, -- user.name, order.total, etc.
    operator ENUM('=', '!=', '>', '<', '>=', '<=', 'contains', 'starts_with', 'ends_with', 'in', 'not_in', 'changed', 'was') NOT NULL,
    value TEXT NULL, -- JSON for complex values
    value_type ENUM('static', 'dynamic', 'model_field') DEFAULT 'static',
    logical_operator ENUM('AND', 'OR') DEFAULT 'AND',
    group_id VARCHAR(36) NULL, -- UUID for grouping conditions
    sort_order INTEGER DEFAULT 0,

    FOREIGN KEY (event_rule_id) REFERENCES event_rules(id) ON DELETE CASCADE,
    INDEX idx_rule_group (event_rule_id, group_id)
);
```

#### event_rule_actions
```sql
CREATE TABLE event_rule_actions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_rule_id BIGINT NOT NULL,
    action_type VARCHAR(100) NOT NULL, -- email, webhook, activity_log, etc.
    action_config JSON NOT NULL, -- Action-specific configuration
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,

    FOREIGN KEY (event_rule_id) REFERENCES event_rules(id) ON DELETE CASCADE,
    INDEX idx_rule_order (event_rule_id, sort_order)
);
```

#### event_logs
```sql
CREATE TABLE event_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_rule_id BIGINT NOT NULL,
    trigger_type VARCHAR(50) NOT NULL,
    model_type VARCHAR(255) NULL,
    model_id BIGINT NULL,
    event_name VARCHAR(255) NOT NULL,
    context JSON NOT NULL, -- Full context data
    actions_executed JSON NOT NULL, -- Results of each action
    execution_time_ms INTEGER NOT NULL,
    triggered_at TIMESTAMP NOT NULL,

    -- User context
    user_id BIGINT NULL,
    user_name VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,

    -- Request context
    request_url TEXT NULL,
    request_method VARCHAR(10) NULL,
    session_id VARCHAR(255) NULL,

    FOREIGN KEY (event_rule_id) REFERENCES event_rules(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_rule_date (event_rule_id, triggered_at),
    INDEX idx_model (model_type, model_id),
    INDEX idx_user_date (user_id, triggered_at),
    INDEX idx_triggered_at (triggered_at)
);
```

### Otimizações

#### Índices Compostos
```sql
-- Para queries frequentes
INDEX idx_active_rules_by_trigger (is_active, trigger_type, priority);
INDEX idx_logs_recent (triggered_at DESC, event_rule_id);
INDEX idx_user_activity (user_id, triggered_at DESC);
```

#### Partitioning (para alto volume)
```sql
-- Partition event_logs by month
PARTITION BY RANGE (YEAR(triggered_at) * 100 + MONTH(triggered_at)) (
    PARTITION p202401 VALUES LESS THAN (202402),
    PARTITION p202402 VALUES LESS THAN (202403),
    -- etc.
);
```

---

## Componentes Filament

### Resources

#### EventRuleResource
```php
class EventRuleResource extends Resource
{
    protected static ?string $model = EventRule::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = 'Events Manager';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Basic Information')
                ->schema([
                    TextInput::make('name')->required(),
                    Textarea::make('description'),
                    Toggle::make('is_active')->default(true),
                    TextInput::make('priority')->numeric()->default(0),
                ]),

            Section::make('Trigger Configuration')
                ->schema([
                    Select::make('trigger_type')
                        ->options([
                            'eloquent' => 'Eloquent Model Events',
                            'query' => 'Database Queries',
                            'custom' => 'Custom Events',
                            'schedule' => 'Schedule Based',
                        ])
                        ->reactive(),

                    // Dynamic fields based on trigger_type
                    Group::make()
                        ->schema(fn (Get $get) => match ($get('trigger_type')) {
                            'eloquent' => static::getEloquentTriggerFields(),
                            'query' => static::getQueryTriggerFields(),
                            'custom' => static::getCustomTriggerFields(),
                            'schedule' => static::getScheduleTriggerFields(),
                            default => [],
                        }),
                ]),

            Section::make('Conditions')
                ->schema([
                    Repeater::make('conditions')
                        ->relationship('conditions')
                        ->schema([
                            TextInput::make('field_path')
                                ->label('Field')
                                ->placeholder('e.g., user.email, order.total'),

                            Select::make('operator')
                                ->options([
                                    '=' => 'Equals',
                                    '!=' => 'Not Equals',
                                    '>' => 'Greater Than',
                                    '<' => 'Less Than',
                                    'contains' => 'Contains',
                                    'changed' => 'Was Changed',
                                ])
                                ->reactive(),

                            TextInput::make('value')
                                ->visible(fn (Get $get) => !in_array($get('operator'), ['changed'])),

                            Select::make('logical_operator')
                                ->options(['AND' => 'AND', 'OR' => 'OR'])
                                ->default('AND'),
                        ]),
                ]),

            Section::make('Actions')
                ->schema([
                    Repeater::make('actions')
                        ->relationship('actions')
                        ->schema([
                            Select::make('action_type')
                                ->options([
                                    'email' => 'Send Email',
                                    'webhook' => 'Call Webhook',
                                    'activity_log' => 'Activity Log',
                                    'notification' => 'Send Notification',
                                ])
                                ->reactive(),

                            Group::make()
                                ->schema(fn (Get $get) => match ($get('action_type')) {
                                    'email' => static::getEmailActionFields(),
                                    'webhook' => static::getWebhookActionFields(),
                                    'activity_log' => static::getActivityLogActionFields(),
                                    'notification' => static::getNotificationActionFields(),
                                    default => [],
                                }),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('trigger_type')->badge(),
                TextColumn::make('conditions_count')->counts('conditions'),
                TextColumn::make('actions_count')->counts('actions'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('priority')->sortable(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('trigger_type'),
                TernaryFilter::make('is_active'),
            ])
            ->actions([
                Action::make('test')
                    ->icon('heroicon-m-play')
                    ->action(fn (EventRule $record) => redirect()->route('filament.admin.pages.rule-tester', ['rule' => $record])),
                Tables\Actions\EditAction::make(),
                Action::make('clone')
                    ->icon('heroicon-m-document-duplicate'),
            ]);
    }
}
```

#### EventLogResource
```php
class EventLogResource extends Resource
{
    protected static ?string $model = EventLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Events Manager';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_rule.name')->label('Rule'),
                TextColumn::make('event_name'),
                TextColumn::make('model_type'),
                TextColumn::make('user_name'),
                TextColumn::make('execution_time_ms')->suffix(' ms'),
                TextColumn::make('triggered_at')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('event_rule_id')->relationship('eventRule', 'name'),
                Filter::make('triggered_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($query, $date) => $query->whereDate('triggered_at', '>=', $date))
                            ->when($data['until'], fn ($query, $date) => $query->whereDate('triggered_at', '<=', $date));
                    }),
            ])
            ->defaultSort('triggered_at', 'desc');
    }
}
```

### Widgets

#### EventsOverviewWidget
```php
class EventsOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Rules', EventRule::where('is_active', true)->count())
                ->description('Currently monitoring')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Events Today', EventLog::whereDate('triggered_at', today())->count())
                ->description('Triggered in last 24h')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Average Response Time',
                number_format(EventLog::whereDate('triggered_at', today())->avg('execution_time_ms'), 1) . 'ms'
            )
                ->description('Today\'s average')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
```

### Custom Pages

#### RuleTester
```php
class RuleTester extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static string $view = 'filament-events-manager::pages.rule-tester';

    public EventRule $rule;
    public array $testData = [];
    public array $testResults = [];

    public function mount(): void
    {
        $this->rule = request()->route('rule');
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Test Data')
                ->schema([
                    KeyValue::make('testData')
                        ->label('Mock Data')
                        ->keyLabel('Field')
                        ->valueLabel('Value'),
                ]),
        ];
    }

    public function testRule()
    {
        // Simulate rule execution with test data
        $this->testResults = app(RuleEngine::class)->test($this->rule, $this->testData);

        $this->notify('success', 'Rule tested successfully');
    }
}
```

---

## Sistema de Eventos

### Event Listeners

#### GlobalEventInterceptor
```php
class GlobalEventInterceptor
{
    public function __construct(
        private RuleEngine $ruleEngine,
        private ContextCollector $contextCollector
    ) {}

    public function handle($eventName, $data): void
    {
        // Skip if no active rules
        if (!$this->hasActiveRules($eventName)) {
            return;
        }

        $context = $this->contextCollector->collect($eventName, $data);

        $this->ruleEngine->processEvent($eventName, $data, $context);
    }

    private function hasActiveRules(string $eventName): bool
    {
        return Cache::remember(
            "active_rules_{$eventName}",
            300, // 5 minutes
            fn () => EventRule::active()
                ->whereJsonContains('trigger_config->events', $eventName)
                ->exists()
        );
    }
}
```

#### EloquentEventListener
```php
class EloquentEventListener
{
    public function boot(): void
    {
        // Listen to all Eloquent events
        Event::listen('eloquent.*', function ($eventName, $data) {
            app(GlobalEventInterceptor::class)->handle($eventName, $data);
        });
    }
}
```

#### QueryEventListener
```php
class QueryEventListener
{
    public function boot(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
            // Parse SQL to determine operation type and affected tables
            $sqlInfo = app(SqlParser::class)->parse($event->sql, $event->bindings);

            if ($this->shouldProcess($sqlInfo)) {
                app(GlobalEventInterceptor::class)->handle('query.executed', [
                    'sql_info' => $sqlInfo,
                    'query_event' => $event,
                ]);
            }
        });
    }
}
```

### Services

#### RuleEngine
```php
class RuleEngine
{
    public function processEvent(string $eventName, array $data, EventContext $context): void
    {
        $rules = $this->getMatchingRules($eventName);

        foreach ($rules as $rule) {
            if ($this->evaluateConditions($rule, $data, $context)) {
                $this->executeActions($rule, $data, $context);
                $this->logExecution($rule, $data, $context);
            }
        }
    }

    private function evaluateConditions(EventRule $rule, array $data, EventContext $context): bool
    {
        if ($rule->conditions->isEmpty()) {
            return true; // No conditions means always execute
        }

        return app(ConditionEvaluator::class)->evaluate($rule->conditions, $data, $context);
    }

    private function executeActions(EventRule $rule, array $data, EventContext $context): void
    {
        foreach ($rule->actions as $action) {
            try {
                app(ActionManager::class)->execute($action, $data, $context);
            } catch (\Exception $e) {
                Log::error("Action execution failed", [
                    'rule_id' => $rule->id,
                    'action_id' => $action->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
```

#### ContextCollector
```php
class ContextCollector
{
    public function collect(string $eventName, array $data): EventContext
    {
        return new EventContext([
            'event_name' => $eventName,
            'triggered_at' => now(),
            'user' => $this->getUserContext(),
            'request' => $this->getRequestContext(),
            'session' => $this->getSessionContext(),
            'data' => $data,
        ]);
    }

    private function getUserContext(): array
    {
        $user = auth()->user();

        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'roles' => $user?->roles?->pluck('name'),
            'permissions' => $user?->permissions?->pluck('name'),
            'impersonating' => session('impersonate_id'),
        ];
    }

    private function getRequestContext(): array
    {
        if (!request()) {
            return ['source' => 'console'];
        }

        return [
            'source' => 'web',
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'route_name' => request()->route()?->getName(),
        ];
    }
}
```

---

## Sistema de Ações

### Action Executors

#### EmailAction
```php
class EmailAction implements ActionExecutor
{
    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        $config = $action->action_config;

        $to = $this->renderTemplate($config['to'], $data, $context);
        $subject = $this->renderTemplate($config['subject'], $data, $context);
        $body = $this->renderTemplate($config['body'], $data, $context);

        Mail::to($to)->send(new DynamicMail($subject, $body));

        return [
            'status' => 'sent',
            'to' => $to,
            'subject' => $subject,
        ];
    }

    private function renderTemplate(string $template, array $data, EventContext $context): string
    {
        return app(TemplateRenderer::class)->render($template, $data, $context);
    }
}
```

#### ActivityLogAction
```php
class ActivityLogAction implements ActionExecutor
{
    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        $config = $action->action_config;

        $model = $data[0] ?? null;

        if (!$model instanceof Model) {
            throw new \InvalidArgumentException('Activity log requires a model instance');
        }

        $logName = $config['log_name'] ?? 'default';
        $description = $this->renderTemplate($config['description'], $data, $context);

        $log = activity($logName)
            ->performedOn($model)
            ->causedBy($context->user['id'] ?? null)
            ->withProperties([
                'rule_id' => $action->event_rule_id,
                'custom_properties' => $config['properties'] ?? [],
                'context' => $context->toArray(),
            ])
            ->log($description);

        return [
            'status' => 'logged',
            'log_id' => $log->id,
            'description' => $description,
        ];
    }
}
```

#### WebhookAction
```php
class WebhookAction implements ActionExecutor
{
    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        $config = $action->action_config;

        $url = $config['url'];
        $method = $config['method'] ?? 'POST';
        $headers = $config['headers'] ?? [];

        $payload = [
            'event' => $context->event_name,
            'data' => $data,
            'context' => $context->toArray(),
            'timestamp' => now()->toISOString(),
        ];

        $response = Http::withHeaders($headers)
            ->timeout($config['timeout'] ?? 30)
            ->{strtolower($method)}($url, $payload);

        return [
            'status' => $response->successful() ? 'success' : 'failed',
            'response_code' => $response->status(),
            'response_body' => $response->body(),
            'payload_sent' => $payload,
        ];
    }
}
```

### Template Renderer
```php
class TemplateRenderer
{
    public function render(string $template, array $data, EventContext $context): string
    {
        $variables = array_merge(
            $this->extractModelVariables($data),
            $this->extractContextVariables($context)
        );

        return $this->replacePlaceholders($template, $variables);
    }

    private function extractModelVariables(array $data): array
    {
        $variables = [];

        foreach ($data as $key => $value) {
            if ($value instanceof Model) {
                $prefix = $key === 0 ? 'model' : $key;
                $variables = array_merge($variables, $this->modelToVariables($value, $prefix));
            }
        }

        return $variables;
    }

    private function modelToVariables(Model $model, string $prefix): array
    {
        $variables = [];

        foreach ($model->getAttributes() as $key => $value) {
            $variables["{$prefix}.{$key}"] = $value;
        }

        // Add original values if model was changed
        if ($model->wasChanged()) {
            foreach ($model->getOriginal() as $key => $value) {
                $variables["{$prefix}.original.{$key}"] = $value;
            }
        }

        return $variables;
    }

    private function replacePlaceholders(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }
}
```

---

## Integrações

### Spatie Activity Log Integration

#### Configuration Enhancement
```php
// In action config
[
    'type' => 'activity_log',
    'config' => [
        'log_name' => 'events_manager',
        'description' => 'Order #{model.id} status changed from {model.original.status} to {model.status}',
        'properties' => [
            'include_old_values' => true,
            'include_new_values' => true,
            'custom_properties' => [
                'rule_name' => '{rule.name}',
                'triggered_by' => '{user.name}',
                'ip_address' => '{request.ip}',
            ],
        ],
        'causer_type' => 'auto', // auto, user, system, custom
        'batch_uuid' => true,
    ]
]
```

### Laravel Notifications
```php
class EventTriggeredNotification extends Notification
{
    public function __construct(
        private EventRule $rule,
        private array $data,
        private EventContext $context
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'slack'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Event Alert: {$this->rule->name}")
            ->line("An event rule has been triggered:")
            ->line("Rule: {$this->rule->name}")
            ->line("Triggered at: {$this->context->triggered_at}")
            ->action('View Details', url('/admin/events-manager/logs'));
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->content("🚨 Event Alert: {$this->rule->name}")
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'Rule' => $this->rule->name,
                    'User' => $this->context->user['name'] ?? 'System',
                    'Time' => $this->context->triggered_at,
                ]);
            });
    }
}
```

### Queue Integration
```php
class ProcessEventRuleJob implements ShouldQueue
{
    public function __construct(
        private EventRule $rule,
        private array $data,
        private EventContext $context
    ) {}

    public function handle(): void
    {
        foreach ($this->rule->actions as $action) {
            dispatch(new ExecuteActionJob($action, $this->data, $this->context))
                ->onQueue($action->action_config['queue'] ?? 'default');
        }
    }
}
```

---

## Interface de Utilizador

### Dashboard Principal
```
Events Manager Dashboard
├── Header Stats
│   ├── Active Rules: 23
│   ├── Events Today: 1,247
│   ├── Avg Response: 45ms
│   └── Success Rate: 99.2%
│
├── Quick Actions
│   ├── [+ Create Rule]
│   ├── [Test Rule]
│   ├── [View Logs]
│   └── [Settings]
│
├── Recent Activity (Live Updates)
│   ├── 10:30 - "Order Shipped" triggered for Order #123
│   ├── 10:25 - "Stock Alert" triggered for Product #456
│   └── 10:20 - "User Registered" triggered for User #789
│
└── Charts
    ├── Events per Hour (last 24h)
    ├── Most Triggered Rules (top 10)
    └── Response Time Trends
```

### Rule Builder Interface
```
Create Event Rule
├── Basic Info
│   ├── Name: [Required text field]
│   ├── Description: [Optional textarea]
│   └── Priority: [Number, default 0]
│
├── Trigger Configuration
│   ├── Type: [Eloquent Model | Database Query | Custom Event]
│   ├── If Eloquent:
│   │   ├── Model: [Dropdown with all models]
│   │   └── Events: [☑ created ☑ updated ☐ deleted]
│   ├── If Query:
│   │   ├── Tables: [Multi-select]
│   │   └── Operations: [☑ INSERT ☑ UPDATE ☐ DELETE]
│   └── If Custom:
│       └── Event Name: [Text input with autocomplete]
│
├── Conditions Builder (Visual)
│   ├── [Field] [Operator] [Value] [AND/OR]
│   ├── user.email contains @company.com AND
│   ├── order.total > 1000 OR
│   └── status changed from pending
│   └── [+ Add Condition] [+ Add Group]
│
└── Actions
    ├── Action 1: Send Email
    │   ├── To: admin@company.com
    │   ├── Subject: Order Alert - #{model.id}
    │   └── Template: [Rich text editor with variables]
    ├── Action 2: Activity Log
    │   ├── Log Name: orders
    │   └── Description: Order #{model.id} updated
    └── [+ Add Action]
```

### Live Testing Interface
```
Rule Tester
├── Selected Rule: "High Value Orders"
├── Test Scenarios
│   ├── Scenario 1: Create Order (total: $5000)
│   │   ├── Mock Data: [Key-Value editor]
│   │   ├── [▶ Run Test]
│   │   └── Results:
│   │       ├── ✅ Conditions Met: Yes
│   │       ├── ✅ Email Sent: admin@test.com
│   │       └── ⏱ Execution Time: 234ms
│   └── Scenario 2: Update Order (total: $500)
│       ├── Mock Data: [Key-Value editor]
│       ├── [▶ Run Test]
│       └── Results:
│           ├── ❌ Conditions Met: No (total too low)
│           └── ⏱ Execution Time: 12ms
└── [Save Scenario] [Load Scenario] [Export Results]
```

### Logs Viewer
```
Event Logs
├── Filters
│   ├── Rule: [Dropdown]
│   ├── Date Range: [Date picker]
│   ├── User: [Search input]
│   └── Status: [Success/Failed/All]
│
├── Table View
│   ├── Time | Rule | Event | Model | User | Duration | Status
│   ├── 10:30 | Order Shipped | eloquent.updated | Order #123 | John | 45ms | ✅
│   └── 10:25 | Stock Alert | eloquent.updated | Product #456 | System | 12ms | ✅
│
└── Detail Modal (on row click)
    ├── Event Details
    ├── Context Data (JSON viewer)
    ├── Actions Executed
    └── [Replay] [Export]
```

---

## Roadmap de Desenvolvimento

### Fase 1: Core Foundation (4-6 semanas)
**Objetivo**: MVP funcional com funcionalidades básicas

#### Sprint 1-2: Database & Models (2 semanas)
- ✅ Criação das migrations
- ✅ Models Eloquent com relationships
- ✅ Seeders para dados de exemplo
- ✅ Basic model factories para testing

#### Sprint 3-4: Event System (2 semanas)
- ✅ GlobalEventInterceptor
- ✅ EloquentEventListener
- ✅ ContextCollector
- ✅ Basic RuleEngine
- ✅ ConditionEvaluator (operadores básicos)

#### Sprint 5-6: Actions & Templates (2 semanas)
- ✅ ActionManager e interface ActionExecutor
- ✅ EmailAction, WebhookAction básicos
- ✅ TemplateRenderer com variáveis simples
- ✅ Basic error handling e logging

### Fase 2: Filament Integration (3-4 semanas)
**Objetivo**: Interface completa para gestão de regras

#### Sprint 7-8: Resources & CRUD (2 semanas)
- ✅ EventRuleResource completo
- ✅ EventLogResource com filtros
- ✅ Form builders dinâmicos
- ✅ Table customizations

#### Sprint 9-10: Widgets & Dashboard (2 semanas)
- ✅ EventsOverviewWidget
- ✅ RecentTriggersWidget
- ✅ Dashboard page customizada
- ✅ Real-time updates (polling/websockets)

### Fase 3: Advanced Features (4-5 semanas)
**Objetivo**: Funcionalidades avançadas e integrações

#### Sprint 11-12: Query Events (2 semanas)
- ✅ QueryEventListener
- ✅ SqlParser para análise de queries
- ✅ Database operation detection
- ✅ Query performance monitoring

#### Sprint 13-14: Spatie Integration (2 semanas)
- ✅ ActivityLogAction completo
- ✅ Enhanced context capture
- ✅ Custom properties mapping
- ✅ Batch logging support

#### Sprint 15: Testing Interface (1 semana)
- ✅ RuleTester page
- ✅ Mock data generation
- ✅ Test scenario management
- ✅ Results visualization

### Fase 4: Production Ready (3-4 semanas)
**Objetivo**: Otimização, segurança e documentação

#### Sprint 16-17: Performance & Security (2 semanas)
- ✅ Rule caching strategies
- ✅ Database optimizations
- ✅ Rate limiting para actions
- ✅ Security audit e sanitization

#### Sprint 18-19: Documentation & Examples (2 semanas)
- ✅ Comprehensive README
- ✅ API documentation
- ✅ Usage examples
- ✅ Video tutorials

### Fase 5: Extensions & Ecosystem (Ongoing)
**Objetivo**: Expandir funcionalidades e comunidade

#### Backlog Futuro
- ✅ GraphQL event triggers
- ✅ Machine learning para pattern detection
- ✅ Visual rule builder (drag & drop)
- ✅ Multi-tenant support
- ✅ API para external integrations
- ✅ Mobile app para monitoring

### Milestones Críticos

#### Milestone 1: Core MVP (Semana 6)
- Basic event interception working
- Simple email action functional
- Database schema stable
- Initial Filament resource

#### Milestone 2: Production Alpha (Semana 10)
- Complete Filament interface
- All basic actions working
- Testing interface available
- Documentation draft

#### Milestone 3: Beta Release (Semana 15)
- Query events support
- Spatie integration complete
- Performance optimized
- Security reviewed

#### Milestone 4: Stable Release (Semana 19)
- Complete documentation
- Examples and tutorials
- Community feedback incorporated
- Production deployments validated

---

## Testing Strategy

### Unit Tests

#### Models
```php
class EventRuleTest extends TestCase
{
    /** @test */
    public function it_can_evaluate_simple_conditions()
    {
        $rule = EventRule::factory()->create();
        $condition = EventRuleCondition::factory()->create([
            'event_rule_id' => $rule->id,
            'field_path' => 'email',
            'operator' => 'contains',
            'value' => '@company.com',
        ]);

        $this->assertTrue($rule->evaluateConditions(['email' => 'john@company.com']));
        $this->assertFalse($rule->evaluateConditions(['email' => 'john@other.com']));
    }

    /** @test */
    public function it_handles_complex_condition_groups()
    {
        // Test AND/OR logic with grouped conditions
    }
}
```

#### Actions
```php
class EmailActionTest extends TestCase
{
    /** @test */
    public function it_sends_email_with_correct_template_variables()
    {
        Mail::fake();

        $action = new EmailAction();
        $actionConfig = EventRuleAction::factory()->make([
            'action_config' => [
                'to' => 'admin@test.com',
                'subject' => 'Order #{model.id} created',
                'body' => 'Order total: ${model.total}',
            ],
        ]);

        $data = [Order::factory()->make(['id' => 123, 'total' => 500])];
        $context = new EventContext(['user' => ['name' => 'John']]);

        $result = $action->execute($actionConfig, $data, $context);

        Mail::assertSent(DynamicMail::class, function ($mail) {
            return $mail->subject === 'Order #123 created';
        });
    }
}
```

### Feature Tests

#### Rule Execution
```php
class RuleExecutionTest extends TestCase
{
    /** @test */
    public function it_triggers_rule_when_eloquent_model_updated()
    {
        // Create a rule for User email changes
        $rule = EventRule::factory()->create([
            'trigger_type' => 'eloquent',
            'trigger_config' => [
                'model' => User::class,
                'events' => ['updated'],
            ],
        ]);

        EventRuleCondition::factory()->create([
            'event_rule_id' => $rule->id,
            'field_path' => 'email',
            'operator' => 'changed',
        ]);

        $emailAction = EventRuleAction::factory()->create([
            'event_rule_id' => $rule->id,
            'action_type' => 'email',
            'action_config' => [
                'to' => 'admin@test.com',
                'subject' => 'User email changed',
            ],
        ]);

        Mail::fake();

        // Update user email
        $user = User::factory()->create(['email' => 'old@test.com']);
        $user->update(['email' => 'new@test.com']);

        // Assert email was sent
        Mail::assertSent(DynamicMail::class);

        // Assert event was logged
        $this->assertDatabaseHas('event_logs', [
            'event_rule_id' => $rule->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }
}
```

#### Filament Interface
```php
class EventRuleResourceTest extends TestCase
{
    /** @test */
    public function admin_can_create_event_rule()
    {
        $admin = User::factory()->admin()->create();

        livewire(CreateEventRule::class)
            ->fillForm([
                'name' => 'Test Rule',
                'trigger_type' => 'eloquent',
                'trigger_config' => [
                    'model' => User::class,
                    'events' => ['created'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('event_rules', [
            'name' => 'Test Rule',
            'trigger_type' => 'eloquent',
        ]);
    }

    /** @test */
    public function event_logs_are_displayed_correctly()
    {
        $rule = EventRule::factory()->create();
        $logs = EventLog::factory()->count(5)->create(['event_rule_id' => $rule->id]);

        livewire(ListEventLogs::class)
            ->assertCanSeeTableRecords($logs);
    }
}
```

### Integration Tests

#### Full Workflow
```php
class FullWorkflowTest extends TestCase
{
    /** @test */
    public function complete_order_workflow_triggers_multiple_actions()
    {
        // Setup: Create rules for order lifecycle
        $this->createOrderShippedRule();
        $this->createHighValueOrderRule();

        Mail::fake();
        Notification::fake();

        // Act: Create and ship a high-value order
        $order = Order::factory()->create(['total' => 5000, 'status' => 'pending']);
        $order->update(['status' => 'shipped']);

        // Assert: Multiple rules triggered
        Mail::assertSentCount(2); // Order shipped + High value
        Notification::assertSentTo($order->customer, OrderShippedNotification::class);

        // Assert: Activity logged via Spatie
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'description' => 'Order shipped',
        ]);

        // Assert: Event logs created
        $this->assertDatabaseHas('event_logs', [
            'model_type' => Order::class,
            'model_id' => $order->id,
            'event_name' => 'eloquent.updated: App\Models\Order',
        ]);
    }
}
```

### Performance Tests
```php
class PerformanceTest extends TestCase
{
    /** @test */
    public function rule_evaluation_performs_within_acceptable_limits()
    {
        // Create 100 rules
        EventRule::factory()->count(100)->create();

        $startTime = microtime(true);

        // Trigger event that could match multiple rules
        User::factory()->create();

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Should complete within 100ms
        $this->assertLessThan(100, $executionTime);
    }
}
```

### Test Data Factories
```php
class EventRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'trigger_type' => 'eloquent',
            'trigger_config' => [
                'model' => User::class,
                'events' => ['created', 'updated'],
            ],
            'is_active' => true,
            'priority' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function forUserUpdates(): static
    {
        return $this->state([
            'trigger_config' => [
                'model' => User::class,
                'events' => ['updated'],
            ],
        ]);
    }

    public function forOrderCreation(): static
    {
        return $this->state([
            'trigger_config' => [
                'model' => Order::class,
                'events' => ['created'],
            ],
        ]);
    }
}
```

---

## Documentação

### README.md Structure
```markdown
# st693ava/filament-events-manager

🚀 **Visual Event Automation for Filament v4**

Configure complex event triggers and automated actions through a beautiful Filament interface - no code required!

## ✨ Features
- 🎯 Visual rule builder
- 📧 Multiple action types (Email, Webhooks, Notifications)
- 🔍 Real-time monitoring dashboard
- 📊 Complete audit trail
- 🔗 Spatie Activity Log integration
- ⚡ Performance optimized

## 🚀 Quick Start
[Installation instructions]

## 📖 Documentation
- [Configuration Guide](docs/configuration.md)
- [Creating Rules](docs/creating-rules.md)
- [Available Actions](docs/actions.md)
- [API Reference](docs/api.md)

## 🎬 Video Tutorials
- [Getting Started (5 min)](link)
- [Advanced Rules (10 min)](link)
- [Custom Actions (15 min)](link)
```

### Installation Guide
```markdown
# Installation

## Requirements
- PHP 8.2+
- Laravel 11+
- Filament v4
- MySQL 8.0+ or PostgreSQL 13+

## Step 1: Install Package
```bash
composer require st693ava/filament-events-manager
```

## Step 2: Publish & Run Migrations
```bash
php artisan vendor:publish --tag="filament-events-manager-migrations"
php artisan migrate
```

## Step 3: Register Plugin
```php
// In AdminPanelProvider
use St693ava\FilamentEventsManager\FilamentEventsManagerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentEventsManagerPlugin::make(),
        ]);
}
```

## Step 4: Configure (Optional)
```bash
php artisan vendor:publish --tag="filament-events-manager-config"
```
```

### Usage Examples
```markdown
# Creating Your First Rule

## Example 1: Welcome Email for New Users

1. **Navigate to Events Manager → Rules**
2. **Click "Create Rule"**
3. **Fill basic info:**
   - Name: "Welcome New Users"
   - Description: "Send welcome email to newly registered users"

4. **Configure Trigger:**
   - Type: Eloquent Model Events
   - Model: App\Models\User
   - Events: ☑ created

5. **Add Action:**
   - Type: Send Email
   - To: `{model.email}`
   - Subject: "Welcome to our platform!"
   - Body: "Hi {model.name}, welcome aboard!"

6. **Save & Activate**

## Example 2: High-Value Order Alerts

1. **Create Rule:** "High Value Order Alert"
2. **Trigger:** Eloquent Model Events → Order → created
3. **Conditions:**
   - `total` > `1000`
4. **Actions:**
   - Email admin: "High value order #{model.id}"
   - Log to activity: "High value order created"
   - Slack notification to #sales

## Example 3: Stock Level Monitoring

1. **Create Rule:** "Low Stock Alert"
2. **Trigger:** Eloquent Model Events → Product → updated
3. **Conditions:**
   - `stock_quantity` <= `10`
   - `stock_quantity` `changed`
4. **Actions:**
   - Email inventory team
   - Create urgent task in project management
   - Update dashboard metrics
```

### API Documentation
```markdown
# API Reference

## Programmatic Rule Creation

```php
use St693ava\FilamentEventsManager\Models\EventRule;

$rule = EventRule::create([
    'name' => 'API Generated Rule',
    'trigger_type' => 'eloquent',
    'trigger_config' => [
        'model' => User::class,
        'events' => ['updated'],
    ],
    'is_active' => true,
]);

$rule->conditions()->create([
    'field_path' => 'email',
    'operator' => 'changed',
]);

$rule->actions()->create([
    'action_type' => 'email',
    'action_config' => [
        'to' => 'admin@example.com',
        'subject' => 'User email updated',
    ],
]);
```

## Custom Action Executors

```php
use St693ava\FilamentEventsManager\Actions\Contracts\ActionExecutor;

class CustomSlackAction implements ActionExecutor
{
    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        // Your implementation
        return ['status' => 'sent'];
    }
}

// Register in service provider
app(ActionManager::class)->register('custom_slack', CustomSlackAction::class);
```
```

---

## Considerações Técnicas

### Performance Otimizations

#### Rule Caching
```php
// Cache active rules by event type
Cache::remember("active_eloquent_rules", 300, function () {
    return EventRule::active()
        ->where('trigger_type', 'eloquent')
        ->with(['conditions', 'actions'])
        ->get()
        ->groupBy('trigger_config.model');
});
```

#### Database Indexing
```sql
-- Critical indexes for performance
CREATE INDEX idx_event_rules_active_type ON event_rules (is_active, trigger_type);
CREATE INDEX idx_event_logs_compound ON event_logs (event_rule_id, triggered_at DESC);
CREATE INDEX idx_conditions_field_operator ON event_rule_conditions (field_path, operator);
```

#### Queue Strategy
```php
// For high-volume applications
class ProcessEventRuleJob implements ShouldQueue
{
    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60]; // Exponential backoff

    public function handle(): void
    {
        // Batch process multiple events
        $this->processEventBatch($this->events);
    }
}
```

### Security Considerations

#### Input Sanitization
```php
class ConditionEvaluator
{
    private function sanitizeValue($value): mixed
    {
        // Prevent code injection in templates
        if (is_string($value)) {
            return strip_tags($value);
        }

        return $value;
    }
}
```

#### Permission Checks
```php
// In EventRuleResource
public static function canCreate(): bool
{
    return auth()->user()->can('create_event_rules');
}

public static function canEdit(Model $record): bool
{
    return auth()->user()->can('edit_event_rules', $record);
}
```

#### Rate Limiting
```php
// Prevent action flooding
class ActionExecutor
{
    protected function checkRateLimit(EventRuleAction $action): bool
    {
        $key = "action_limit_{$action->id}";
        $limit = $action->action_config['rate_limit'] ?? 60; // per minute

        return RateLimiter::tooManyAttempts($key, $limit);
    }
}
```

### Scalability Planning

#### Database Partitioning
```sql
-- Partition event_logs by month for better performance
ALTER TABLE event_logs PARTITION BY RANGE (YEAR(triggered_at) * 100 + MONTH(triggered_at));
```

#### Horizontal Scaling
```php
// Support for multiple app instances
class EventInterceptor
{
    public function shouldProcess(string $eventName): bool
    {
        // Distribute rules across instances using consistent hashing
        $hash = crc32($eventName);
        $instance = $hash % config('events-manager.instances', 1);

        return $instance === config('events-manager.current_instance', 0);
    }
}
```

#### Monitoring & Alerting
```php
// Built-in health checks
class EventsManagerHealthCheck
{
    public function check(): array
    {
        return [
            'active_rules' => EventRule::active()->count(),
            'avg_execution_time' => EventLog::where('triggered_at', '>', now()->subHour())->avg('execution_time_ms'),
            'failed_actions_last_hour' => EventLog::where('triggered_at', '>', now()->subHour())->whereJsonContains('actions_executed', ['status' => 'failed'])->count(),
            'queue_size' => Queue::size('events-manager'),
        ];
    }
}
```

### Migration Strategy

#### Version Compatibility
```php
// Support for gradual migration from existing observers
class LegacyObserverBridge
{
    public function handle($event, $data): void
    {
        // Continue calling existing observers
        $this->callLegacyObservers($event, $data);

        // Also process through Events Manager
        app(GlobalEventInterceptor::class)->handle($event, $data);
    }
}
```

#### Backwards Compatibility
```php
// Maintain API compatibility across versions
class EventRuleResource
{
    public function getFormSchema(): array
    {
        $schema = $this->getBaseSchema();

        // Add version-specific fields
        if (version_compare(config('filament-events-manager.version'), '2.0', '>=')) {
            $schema[] = $this->getAdvancedConditionsField();
        }

        return $schema;
    }
}
```

---

## Conclusão

O **st693ava/filament-events-manager** representa uma evolução significativa na forma como automatizamos workflows em aplicações Laravel/Filament. Este package elimina a necessidade de código boilerplate para tarefas comuns de automação, permitindo que equipas técnicas e não-técnicas configurem regras complexas através de uma interface visual intuitiva.

### Benefícios Principais:
- **Redução de Desenvolvimento**: 80% menos código para automações típicas
- **Flexibilidade**: Configuração dinâmica sem deployments
- **Auditoria Completa**: Rastreamento total de ações e utilizadores
- **Performance**: Otimizado para alta escala com caching inteligente
- **Extensibilidade**: Sistema pluggável para custom actions

### Diferenciadores Competitivos:
- Integração nativa com Filament v4
- Suporte simultâneo para eventos Eloquent e DB queries
- Interface de testing em tempo real
- Integração com Spatie Activity Log
- Template system avançado com variáveis dinâmicas

Este plano fornece a base sólida para desenvolver um package robusto, escalável e fácil de usar que revolucionará a forma como automatizamos workflows em Laravel.

**Próximo Passo**: Iniciar desenvolvimento com Fase 1 - Core Foundation, focando primeiro na arquitetura sólida de eventos e depois expandindo para a interface Filament completa.