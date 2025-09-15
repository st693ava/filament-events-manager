<?php

use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Models\EventLog;
use Workbench\App\Models\User;

it('can create an event rule', function () {
    $rule = createEventRule([
        'name' => 'Test Rule',
        'description' => 'A test rule',
        'trigger_type' => 'eloquent',
        'is_active' => true,
        'priority' => 50,
    ]);

    expect($rule)
        ->toBeInstanceOf(EventRule::class)
        ->name->toBe('Test Rule')
        ->description->toBe('A test rule')
        ->trigger_type->toBe('eloquent')
        ->is_active->toBeTrue()
        ->priority->toBe(50);
});

it('has correct fillable attributes', function () {
    $rule = new EventRule();

    expect($rule->getFillable())->toBe([
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'is_active',
        'priority',
        'created_by_user_id',
        'updated_by_user_id',
    ]);
});

it('casts attributes correctly', function () {
    $rule = createEventRule([
        'trigger_config' => ['test' => 'value'],
        'is_active' => '1',
    ]);

    expect($rule->trigger_config)
        ->toBeArray()
        ->toHaveKey('test', 'value');

    expect($rule->is_active)->toBeTrue();
});

it('has many conditions', function () {
    $rule = createEventRule();
    $condition1 = createEventRuleCondition(['event_rule_id' => $rule->id]);
    $condition2 = createEventRuleCondition(['event_rule_id' => $rule->id]);

    expect($rule->conditions)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(EventRuleCondition::class);
});

it('has many actions', function () {
    $rule = createEventRule();
    $action1 = createEventRuleAction(['event_rule_id' => $rule->id]);
    $action2 = createEventRuleAction(['event_rule_id' => $rule->id]);

    expect($rule->actions)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(EventRuleAction::class);
});

it('has many event logs', function () {
    $rule = createEventRule();

    // Criar logs usando o factory para garantir que todos os campos obrigatórios estão preenchidos
    $log1 = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create();

    $log2 = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create();

    // Debug: verificar quantos logs existem para esta regra usando query direta
    $directCount = EventLog::where('event_rule_id', $rule->id)->count();
    expect($directCount)->toBe(2);

    // Verificar a relação usando a relationship
    $ruleLogs = $rule->eventLogs()->get();

    expect($ruleLogs)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(EventLog::class);

    // Verificar que todos os logs pertencem à regra correta
    expect($ruleLogs->every(fn($log) => $log->event_rule_id === $rule->id))
        ->toBeTrue();
});

it('can belong to creator user', function () {
    $user = createUser();
    $rule = createEventRule(['created_by_user_id' => $user->id]);

    expect($rule->creator)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

it('can belong to updater user', function () {
    $user = createUser();
    $rule = createEventRule(['updated_by_user_id' => $user->id]);

    expect($rule->updater)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

it('has active scope', function () {
    createEventRule(['is_active' => true]);
    createEventRule(['is_active' => true]);
    createEventRule(['is_active' => false]);

    expect(EventRule::active()->count())->toBe(2);
});

it('has inactive scope', function () {
    createEventRule(['is_active' => true]);
    createEventRule(['is_active' => false]);
    createEventRule(['is_active' => false]);

    expect(EventRule::inactive()->count())->toBe(2);
});

it('has byTriggerType scope', function () {
    createEventRule(['trigger_type' => 'eloquent']);
    createEventRule(['trigger_type' => 'eloquent']);
    createEventRule(['trigger_type' => 'sql_query']);

    expect(EventRule::byTriggerType('eloquent')->count())->toBe(2);
    expect(EventRule::byTriggerType('sql_query')->count())->toBe(1);
});

it('has byPriority scope', function () {
    createEventRule(['priority' => 10]);
    createEventRule(['priority' => 50]);
    createEventRule(['priority' => 90]);

    expect(EventRule::byPriority()->first()->priority)->toBe(90);
});

it('can get trigger model class', function () {
    $rule = createEventRule([
        'trigger_type' => 'eloquent',
        'trigger_config' => ['model' => 'Workbench\\App\\Models\\User'],
    ]);

    expect($rule->getTriggerModelClass())->toBe('Workbench\\App\\Models\\User');
});

it('returns null for non-eloquent triggers when getting model class', function () {
    $rule = createEventRule([
        'trigger_type' => 'schedule',
        'trigger_config' => ['expression' => '0 0 * * *'],
    ]);

    expect($rule->getTriggerModelClass())->toBeNull();
});

it('can get trigger events', function () {
    $rule = createEventRule([
        'trigger_type' => 'eloquent',
        'trigger_config' => ['events' => ['created', 'updated']],
    ]);

    expect($rule->getTriggerEvents())
        ->toBeArray()
        ->toBe(['created', 'updated']);
});

it('returns empty array for non-eloquent triggers when getting events', function () {
    $rule = createEventRule([
        'trigger_type' => 'schedule',
        'trigger_config' => ['expression' => '0 0 * * *'],
    ]);

    expect($rule->getTriggerEvents())->toBe([]);
});

it('can check if rule matches event', function () {
    $rule = createEventRule([
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => 'Workbench\\App\\Models\\User',
            'events' => ['created', 'updated'],
        ],
    ]);

    expect($rule->matchesEvent('eloquent', 'Workbench\\App\\Models\\User', 'created'))->toBeTrue();
    expect($rule->matchesEvent('eloquent', 'Workbench\\App\\Models\\User', 'updated'))->toBeTrue();
    expect($rule->matchesEvent('eloquent', 'Workbench\\App\\Models\\User', 'deleted'))->toBeFalse();
    expect($rule->matchesEvent('eloquent', 'Workbench\\App\\Models\\TestProduct', 'created'))->toBeFalse();
    expect($rule->matchesEvent('sql_query', 'Workbench\\App\\Models\\User', 'created'))->toBeFalse();
});

it('inactive rules dont match events', function () {
    $rule = createEventRule([
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => 'Workbench\\App\\Models\\User',
            'events' => ['created'],
        ],
        'is_active' => false,
    ]);

    expect($rule->matchesEvent('eloquent', 'Workbench\\App\\Models\\User', 'created'))->toBeFalse();
});

it('can be converted to array', function () {
    $rule = createEventRule([
        'name' => 'Test Rule',
        'trigger_type' => 'eloquent',
    ]);

    $array = $rule->toArray();

    expect($array)
        ->toHaveKey('name', 'Test Rule')
        ->toHaveKey('trigger_type', 'eloquent')
        ->toHaveKey('id')
        ->toHaveKey('created_at')
        ->toHaveKey('updated_at');
});