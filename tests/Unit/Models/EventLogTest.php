<?php

use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventLog;
use Workbench\App\Models\User;

it('can create an event log', function () {
    $rule = createEventRule();
    $user = createUser();

    $log = \Workbench\Database\Factories\EventLogFactory::new([
        'event_rule_id' => $rule->id,
        'trigger_type' => 'eloquent',
        'event_name' => 'user.created',
        'user_id' => $user->id,
        'execution_time_ms' => 150,
    ])->create();

    expect($log)
        ->toBeInstanceOf(EventLog::class)
        ->event_rule_id->toBe($rule->id)
        ->trigger_type->toBe('eloquent')
        ->event_name->toBe('user.created')
        ->user_id->toBe($user->id)
        ->execution_time_ms->toBe(150);
});

it('has correct fillable attributes', function () {
    $log = new EventLog();

    expect($log->getFillable())->toBe([
        'event_rule_id',
        'trigger_type',
        'model_type',
        'model_id',
        'event_name',
        'context',
        'actions_executed',
        'execution_time_ms',
        'triggered_at',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
        'request_url',
        'request_method',
        'session_id',
    ]);
});

it('casts attributes correctly', function () {
    $log = \Workbench\Database\Factories\EventLogFactory::new([
        'context' => ['key' => 'value'],
        'actions_executed' => [['action' => 'email', 'status' => 'sent']],
        'execution_time_ms' => '150',
    ])->create();

    expect($log->context)
        ->toBeArray()
        ->toHaveKey('key', 'value');

    expect($log->actions_executed)
        ->toBeArray()
        ->toHaveCount(1);

    expect($log->execution_time_ms)->toBe(150);
    expect($log->triggered_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('belongs to event rule', function () {
    $rule = createEventRule();
    $log = \Workbench\Database\Factories\EventLogFactory::new(['event_rule_id' => $rule->id])->create();

    expect($log->eventRule)
        ->toBeInstanceOf(EventRule::class)
        ->id->toBe($rule->id);
});

it('can belong to user', function () {
    $user = createUser();
    $log = \Workbench\Database\Factories\EventLogFactory::new(['user_id' => $user->id])->create();

    expect($log->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

it('has forRule scope', function () {
    // Limpar dados existentes para este teste
    EventLog::truncate();

    // Criar regras separadas para testar isoladamente
    $rule1 = \Workbench\Database\Factories\EventRuleFactory::new()->create();
    $rule2 = \Workbench\Database\Factories\EventRuleFactory::new()->create();

    // Criar logs específicos para cada regra
    $log1 = \Workbench\Database\Factories\EventLogFactory::new(['event_rule_id' => $rule1->id])->create();
    $log2 = \Workbench\Database\Factories\EventLogFactory::new(['event_rule_id' => $rule2->id])->create();

    // Verificar contagem específica baseada nos logs criados
    $rule1Count = EventLog::forRule($rule1->id)->where('id', $log1->id)->count();
    $rule2Count = EventLog::forRule($rule2->id)->where('id', $log2->id)->count();

    expect($rule1Count)->toBe(1);
    expect($rule2Count)->toBe(1);
});

it('has byTriggerType scope', function () {
    // Limpar dados existentes para este teste
    EventLog::truncate();

    // Criar logs específicos e isolados
    $eloquentLog1 = \Workbench\Database\Factories\EventLogFactory::new(['trigger_type' => 'eloquent'])->create();
    $eloquentLog2 = \Workbench\Database\Factories\EventLogFactory::new(['trigger_type' => 'eloquent'])->create();
    $webhookLog = \Workbench\Database\Factories\EventLogFactory::new(['trigger_type' => 'webhook'])->create();

    // Verificar contagem com base nos IDs específicos criados
    $eloquentCount = EventLog::byTriggerType('eloquent')->whereIn('id', [$eloquentLog1->id, $eloquentLog2->id])->count();
    $webhookCount = EventLog::byTriggerType('webhook')->where('id', $webhookLog->id)->count();

    expect($eloquentCount)->toBe(2);
    expect($webhookCount)->toBe(1);
});

it('has recent scope', function () {
    // Limpar dados existentes para este teste
    EventLog::truncate();

    // Criar um log antigo usando o método old() do factory
    $oldLog = \Workbench\Database\Factories\EventLogFactory::new()->old()->create();

    // Criar um log recente usando o método recent() do factory
    $recentLog = \Workbench\Database\Factories\EventLogFactory::new()->recent()->create();

    // Verificar que apenas os logs recentes são retornados baseados nos IDs específicos
    $recentLogs = EventLog::recent()->whereIn('id', [$oldLog->id, $recentLog->id])->get();
    expect($recentLogs->count())->toBe(1);
    expect($recentLogs->first()->id)->toBe($recentLog->id);
});

it('has forUser scope', function () {
    $user1 = createUser();
    $user2 = createUser();

    $log1 = \Workbench\Database\Factories\EventLogFactory::new(['user_id' => $user1->id])->create();
    $log2 = \Workbench\Database\Factories\EventLogFactory::new(['user_id' => $user2->id])->create();

    expect(EventLog::forUser($user1->id)->count())->toBe(1);
    expect(EventLog::forUser($user2->id)->count())->toBe(1);
});

it('can check if log is for eloquent trigger', function () {
    $eloquentLog = \Workbench\Database\Factories\EventLogFactory::new(['trigger_type' => 'eloquent'])->create();
    $webhookLog = \Workbench\Database\Factories\EventLogFactory::new(['trigger_type' => 'webhook'])->create();

    expect($eloquentLog->isEloquentTrigger())->toBeTrue();
    expect($webhookLog->isEloquentTrigger())->toBeFalse();
});

it('can check if log has actions', function () {
    $logWithActions = \Workbench\Database\Factories\EventLogFactory::new([
        'actions_executed' => [['action' => 'email', 'status' => 'sent']],
    ])->create();

    $logWithoutActions = \Workbench\Database\Factories\EventLogFactory::new([
        'actions_executed' => [],
    ])->create();

    expect($logWithActions->hasActions())->toBeTrue();
    expect($logWithoutActions->hasActions())->toBeFalse();
});

it('can get execution time in seconds', function () {
    $log = \Workbench\Database\Factories\EventLogFactory::new(['execution_time_ms' => 1500])->create();

    expect($log->getExecutionTimeInSeconds())->toBe(1.5);
});

it('can check if log is fast execution', function () {
    $fastLog = \Workbench\Database\Factories\EventLogFactory::new(['execution_time_ms' => 50])->create();
    $slowLog = \Workbench\Database\Factories\EventLogFactory::new(['execution_time_ms' => 2000])->create();

    expect($fastLog->isFastExecution())->toBeTrue();
    expect($slowLog->isFastExecution())->toBeFalse();
});

it('can get context value', function () {
    $log = \Workbench\Database\Factories\EventLogFactory::new([
        'context' => ['user_name' => 'John Doe', 'action' => 'created'],
    ])->create();

    expect($log->getContextValue('user_name'))->toBe('John Doe');
    expect($log->getContextValue('action'))->toBe('created');
    expect($log->getContextValue('nonexistent', 'default'))->toBe('default');
});

it('can get successful actions count', function () {
    $log = \Workbench\Database\Factories\EventLogFactory::new([
        'actions_executed' => [
            ['action' => 'email', 'status' => 'success'],
            ['action' => 'webhook', 'status' => 'success'],
            ['action' => 'notification', 'status' => 'failed'],
        ],
    ])->create();

    expect($log->getSuccessfulActionsCount())->toBe(2);
});

it('can get failed actions count', function () {
    $log = \Workbench\Database\Factories\EventLogFactory::new([
        'actions_executed' => [
            ['action' => 'email', 'status' => 'success'],
            ['action' => 'webhook', 'status' => 'failed'],
            ['action' => 'notification', 'status' => 'failed'],
        ],
    ])->create();

    expect($log->getFailedActionsCount())->toBe(2);
});

it('can format triggered at for humans', function () {
    $log = \Workbench\Database\Factories\EventLogFactory::new([
        'triggered_at' => now()->subMinutes(5),
    ])->create();

    expect($log->getTriggeredAtForHumans())->toBe('5 minutes ago');
});

it('can be converted to array', function () {
    $log = \Workbench\Database\Factories\EventLogFactory::new([
        'trigger_type' => 'eloquent',
        'event_name' => 'user.created',
    ])->create();

    $array = $log->toArray();

    expect($array)
        ->toHaveKey('trigger_type', 'eloquent')
        ->toHaveKey('event_name', 'user.created')
        ->toHaveKey('id')
        ->toHaveKey('triggered_at');
});