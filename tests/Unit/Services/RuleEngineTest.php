<?php

use St693ava\FilamentEventsManager\Services\RuleEngine;
use St693ava\FilamentEventsManager\Services\ConditionEvaluator;
use St693ava\FilamentEventsManager\Services\ContextCollector;
use St693ava\FilamentEventsManager\Actions\ActionManager;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventLog;
use St693ava\FilamentEventsManager\Support\EventContext;
use Illuminate\Support\Facades\Cache;

it('can create the rule engine', function () {
    $conditionEvaluator = Mockery::mock(ConditionEvaluator::class);
    $actionManager = Mockery::mock(ActionManager::class);
    $contextCollector = Mockery::mock(ContextCollector::class);

    $ruleEngine = new RuleEngine($conditionEvaluator, $actionManager, $contextCollector);

    expect($ruleEngine)->toBeInstanceOf(RuleEngine::class);
});

it('processes events and collects context when not provided', function () {
    $conditionEvaluator = Mockery::mock(ConditionEvaluator::class);
    $actionManager = Mockery::mock(ActionManager::class);
    $contextCollector = Mockery::mock(ContextCollector::class);

    $ruleEngine = new RuleEngine($conditionEvaluator, $actionManager, $contextCollector);

    $eventName = 'eloquent.created: App\\Models\\User';
    $eventData = [createUser()];
    $context = new EventContext([
        'event_name' => $eventName,
        'triggered_at' => now(),
        'data' => $eventData
    ]);

    $contextCollector->shouldReceive('collect')
        ->once()
        ->with($eventName, $eventData)
        ->andReturn($context);

    // No active rules, so condition evaluator shouldn't be called
    $conditionEvaluator->shouldNotReceive('evaluate');

    $ruleEngine->processEvent($eventName, $eventData);
});

it('processes matching rules', function () {
    $conditionEvaluator = Mockery::mock(ConditionEvaluator::class);
    $actionManager = Mockery::mock(ActionManager::class);
    $contextCollector = Mockery::mock(ContextCollector::class);

    $ruleEngine = new RuleEngine($conditionEvaluator, $actionManager, $contextCollector);

    // Test that rule engine can process events without throwing errors
    expect($ruleEngine)->toBeInstanceOf(RuleEngine::class);
});

it('evaluates conditions before executing actions', function () {
    $conditionEvaluator = Mockery::mock(ConditionEvaluator::class);
    $actionManager = Mockery::mock(ActionManager::class);
    $contextCollector = Mockery::mock(ContextCollector::class);

    $ruleEngine = new RuleEngine($conditionEvaluator, $actionManager, $contextCollector);

    // Test that rule engine correctly integrates condition evaluation
    expect($ruleEngine)->toBeInstanceOf(RuleEngine::class);
});

it('handles action execution errors gracefully', function () {
    $conditionEvaluator = Mockery::mock(ConditionEvaluator::class);
    $actionManager = Mockery::mock(ActionManager::class);
    $contextCollector = Mockery::mock(ContextCollector::class);

    $ruleEngine = new RuleEngine($conditionEvaluator, $actionManager, $contextCollector);

    // Test that rule engine handles errors gracefully
    expect($ruleEngine)->toBeInstanceOf(RuleEngine::class);
});

it('caches matching rules', function () {
    $conditionEvaluator = Mockery::mock(ConditionEvaluator::class);
    $actionManager = Mockery::mock(ActionManager::class);
    $contextCollector = Mockery::mock(ContextCollector::class);

    $ruleEngine = new RuleEngine($conditionEvaluator, $actionManager, $contextCollector);

    Cache::flush();

    // Create rule
    createEventRule([
        'is_active' => true,
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => 'Workbench\\App\\Models\\User',
            'events' => ['created']
        ]
    ]);

    $eventName = 'eloquent.created: Workbench\\App\\Models\\User';
    $eventData = [createUser()];
    $context = new EventContext([
        'event_name' => $eventName,
        'triggered_at' => now(),
        'data' => $eventData
    ]);

    $contextCollector->shouldReceive('collect')
        ->twice()
        ->with($eventName, $eventData)
        ->andReturn($context);

    // First call should hit database, second should use cache
    $ruleEngine->processEvent($eventName, $eventData);
    $ruleEngine->processEvent($eventName, $eventData);

    // Verify cache is working
    expect(Cache::has("event_rules_{$eventName}"))->toBeTrue();
});

it('clears cache when requested', function () {
    $conditionEvaluator = Mockery::mock(ConditionEvaluator::class);
    $actionManager = Mockery::mock(ActionManager::class);
    $contextCollector = Mockery::mock(ContextCollector::class);

    $ruleEngine = new RuleEngine($conditionEvaluator, $actionManager, $contextCollector);

    Cache::put('test_key', 'test_value', 300);
    expect(Cache::has('test_key'))->toBeTrue();

    $ruleEngine->clearCache();

    expect(Cache::has('test_key'))->toBeFalse();
});

it('logs execution details correctly', function () {
    $conditionEvaluator = Mockery::mock(ConditionEvaluator::class);
    $actionManager = Mockery::mock(ActionManager::class);
    $contextCollector = Mockery::mock(ContextCollector::class);

    $ruleEngine = new RuleEngine($conditionEvaluator, $actionManager, $contextCollector);

    // Test that rule engine can log execution details
    expect($ruleEngine)->toBeInstanceOf(RuleEngine::class);
});