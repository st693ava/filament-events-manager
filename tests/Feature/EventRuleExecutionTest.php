<?php

use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Models\EventLog;
use St693ava\FilamentEventsManager\Support\EventContext;
use Workbench\App\Models\User;

it('can create complete event rule structure', function () {
    // Criar uma regra completa com condições e ações
    $rule = createEventRule([
        'name' => 'User Registration Welcome',
        'description' => 'Send welcome email when user registers',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => true,
        'priority' => 10,
    ]);

    // Adicionar condição
    $condition = createEventRuleCondition([
        'event_rule_id' => $rule->id,
        'field_path' => 'email',
        'operator' => 'contains',
        'value' => '@example.com',
        'logical_operator' => 'AND',
        'sort_order' => 1,
    ]);

    // Adicionar ação
    $action = createEventRuleAction([
        'event_rule_id' => $rule->id,
        'action_type' => 'email',
        'action_config' => [
            'to' => '{{ model.email }}',
            'subject' => 'Welcome {{ model.name }}!',
            'template' => 'welcome_email',
        ],
        'sort_order' => 1,
    ]);

    // Verificar estrutura completa
    expect($rule->conditions()->count())->toBe(1);
    expect($rule->actions()->count())->toBe(1);
    expect($rule->is_active)->toBeTrue();

    $firstCondition = $rule->conditions()->first();
    expect($firstCondition->field_path)->toBe('email');
    expect($firstCondition->operator)->toBe('contains');
    expect($firstCondition->value)->toBe('@example.com');

    $firstAction = $rule->actions()->first();
    expect($firstAction->action_type)->toBe('email');
    expect($firstAction->action_config)->toHaveKey('to');
    expect($firstAction->action_config['subject'])->toBe('Welcome {{ model.name }}!');
});

it('can simulate event log creation for rule execution', function () {
    // Criar regra
    $rule = createEventRule([
        'name' => 'Feature Test Rule',
        'trigger_type' => 'eloquent',
        'is_active' => true,
    ]);

    // Criar ação
    createEventRuleAction([
        'event_rule_id' => $rule->id,
        'action_type' => 'email',
        'action_config' => ['to' => 'test@example.com'],
    ]);

    // Criar usuário
    $user = createUser(['email' => 'test@example.com', 'name' => 'Test User']);

    // Simular criação de log (representa o resultado de uma execução de regra)
    $eventLog = createEventLog([
        'event_rule_id' => $rule->id,
        'trigger_type' => 'eloquent',
        'model_type' => User::class,
        'model_id' => $user->id,
        'event_name' => 'user.created',
        'context' => [
            'model_data' => $user->toArray(),
            'triggered_at' => now(),
        ],
        'actions_executed' => [
            [
                'action_type' => 'email',
                'status' => 'success',
                'details' => 'Email sent successfully',
                'executed_at' => now()->toISOString(),
            ],
        ],
        'execution_time_ms' => 150,
        'triggered_at' => now(),
    ]);

    // Verificar que o log foi criado corretamente
    expect($eventLog)->not->toBeNull();
    expect($eventLog->event_rule_id)->toBe($rule->id);
    expect($eventLog->model_type)->toBe(User::class);
    expect($eventLog->model_id)->toBe($user->id);
    expect($eventLog->actions_executed)->toHaveCount(1);
    expect($eventLog->actions_executed[0]['action_type'])->toBe('email');
    expect($eventLog->actions_executed[0]['status'])->toBe('success');
});

it('can handle multiple rules for same model type', function () {
    // Criar duas regras diferentes
    $rule1 = createEventRule([
        'name' => 'Welcome Email',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => true,
        'priority' => 10,
    ]);

    $rule2 = createEventRule([
        'name' => 'Admin Notification',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => true,
        'priority' => 20,
    ]);

    // Adicionar ações diferentes
    createEventRuleAction([
        'event_rule_id' => $rule1->id,
        'action_type' => 'email',
        'action_config' => ['to' => '{{ model.email }}'],
    ]);

    createEventRuleAction([
        'event_rule_id' => $rule2->id,
        'action_type' => 'webhook',
        'action_config' => ['url' => 'https://example.com/webhook'],
    ]);

    // Verificar que ambas as regras estão configuradas
    expect($rule1->actions()->count())->toBe(1);
    expect($rule2->actions()->count())->toBe(1);
    expect($rule1->actions()->first()->action_type)->toBe('email');
    expect($rule2->actions()->first()->action_type)->toBe('webhook');

    // Verificar prioridades
    expect($rule2->priority)->toBeGreaterThan($rule1->priority);
});

it('can validate rule configurations', function () {
    // Regra com configuração eloquent
    $eloquentRule = createEventRule([
        'name' => 'Eloquent Rule',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created', 'updated'],
        ],
        'is_active' => true,
    ]);

    // Regra com configuração de schedule
    $scheduleRule = createEventRule([
        'name' => 'Schedule Rule',
        'trigger_type' => 'schedule',
        'trigger_config' => [
            'expression' => '0 0 * * *',
            'timezone' => 'Europe/Lisbon',
        ],
        'is_active' => true,
    ]);

    // Verificar métodos de configuração
    expect($eloquentRule->getTriggerModelClass())->toBe(User::class);
    expect($eloquentRule->getTriggerEvents())->toBe(['created', 'updated']);

    expect($scheduleRule->getTriggerModelClass())->toBeNull();
    expect($scheduleRule->getTriggerEvents())->toBe([]);

    // Verificar matching de eventos
    expect($eloquentRule->matchesEvent('eloquent', User::class, 'created'))->toBeTrue();
    expect($eloquentRule->matchesEvent('eloquent', User::class, 'deleted'))->toBeFalse();
    expect($scheduleRule->matchesEvent('eloquent', User::class, 'created'))->toBeFalse();
});

it('can handle inactive rules correctly', function () {
    // Criar regra ativa com configuração completa
    $activeRule = createEventRule([
        'name' => 'Active Rule',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => true,
    ]);

    // Criar regra inativa com configuração completa
    $inactiveRule = createEventRule([
        'name' => 'Inactive Rule',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => false,
    ]);

    // Verificar scopes
    expect(EventRule::active()->count())->toBeGreaterThanOrEqual(1);
    expect(EventRule::inactive()->count())->toBeGreaterThanOrEqual(1);

    // Verificar que regra inativa não faz match (porque is_active = false)
    expect($inactiveRule->matchesEvent('eloquent', User::class, 'created'))->toBeFalse();
    // Verificar que regra ativa faz match
    expect($activeRule->matchesEvent('eloquent', User::class, 'created'))->toBeTrue();
});

it('can demonstrate complex condition logic', function () {
    $rule = createEventRule([
        'name' => 'Complex Conditions Rule',
        'trigger_type' => 'eloquent',
        'is_active' => true,
    ]);

    // Múltiplas condições com diferentes operadores
    $condition1 = createEventRuleCondition([
        'event_rule_id' => $rule->id,
        'field_path' => 'email',
        'operator' => 'contains',
        'value' => '@company.com',
        'logical_operator' => 'AND',
        'sort_order' => 1,
    ]);

    $condition2 = createEventRuleCondition([
        'event_rule_id' => $rule->id,
        'field_path' => 'name',
        'operator' => 'starts_with',
        'value' => 'Mr.',
        'logical_operator' => 'OR',
        'sort_order' => 2,
    ]);

    $condition3 = createEventRuleCondition([
        'event_rule_id' => $rule->id,
        'field_path' => 'status',
        'operator' => 'in',
        'value' => '["active", "premium"]',
        'logical_operator' => 'AND',
        'sort_order' => 3,
    ]);

    // Verificar que as condições foram criadas
    expect($rule->conditions()->count())->toBe(3);

    // Verificar diferentes tipos de operadores
    $conditions = $rule->conditions()->orderBy('sort_order')->get();
    expect($conditions[0]->isTextOperator())->toBeTrue();
    expect($conditions[1]->isTextOperator())->toBeTrue();
    expect($conditions[2]->isArrayOperator())->toBeTrue();

    // Verificar valor decodificado para condição de array
    expect($conditions[2]->getDecodedValue())->toBe(['active', 'premium']);
});

it('can demonstrate action execution simulation', function () {
    $rule = createEventRule([
        'name' => 'Action Test Rule',
        'trigger_type' => 'eloquent',
        'is_active' => true,
    ]);

    // Diferentes tipos de ações
    $emailAction = createEventRuleAction([
        'event_rule_id' => $rule->id,
        'action_type' => 'email',
        'action_config' => [
            'to' => '{{ model.email }}',
            'subject' => 'Test Email',
            'template' => 'test_template',
        ],
        'sort_order' => 1,
    ]);

    $webhookAction = createEventRuleAction([
        'event_rule_id' => $rule->id,
        'action_type' => 'webhook',
        'action_config' => [
            'url' => 'https://api.example.com/webhook',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
        ],
        'sort_order' => 2,
    ]);

    // Verificar configurações das ações
    expect($rule->actions()->count())->toBe(2);
    expect($emailAction->isEmailAction())->toBeTrue();
    expect($webhookAction->isWebhookAction())->toBeTrue();

    expect($emailAction->getEmailRecipient())->toBe('{{ model.email }}');
    expect($webhookAction->getWebhookUrl())->toBe('https://api.example.com/webhook');

    // Verificar ordenação
    $actions = $rule->actions()->orderBy('sort_order')->get();
    expect($actions[0]->action_type)->toBe('email');
    expect($actions[1]->action_type)->toBe('webhook');
});