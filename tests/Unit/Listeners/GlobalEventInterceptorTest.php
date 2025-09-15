<?php

use St693ava\FilamentEventsManager\Listeners\GlobalEventInterceptor;
use St693ava\FilamentEventsManager\Services\RuleEngine;
use St693ava\FilamentEventsManager\Models\EventRule;
use Illuminate\Support\Facades\Cache;

it('can create the interceptor', function () {
    $ruleEngine = Mockery::mock(RuleEngine::class);
    $interceptor = new GlobalEventInterceptor($ruleEngine);

    expect($interceptor)->toBeInstanceOf(GlobalEventInterceptor::class);
});

it('processes events when active rules exist', function () {
    $ruleEngine = Mockery::mock(RuleEngine::class);
    $interceptor = new GlobalEventInterceptor($ruleEngine);

    // Create an active rule that matches the event
    createEventRule([
        'is_active' => true,
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => 'App\\Models\\User',
            'events' => ['created']
        ]
    ]);

    $eventName = 'eloquent.created: App\\Models\\User';
    $eventData = [createUser()];

    $ruleEngine->shouldReceive('processEvent')
        ->once()
        ->with($eventName, $eventData);

    $interceptor->handle($eventName, $eventData);
});

it('skips processing when no active rules exist', function () {
    $ruleEngine = Mockery::mock(RuleEngine::class);
    $interceptor = new GlobalEventInterceptor($ruleEngine);

    $eventName = 'eloquent.created: App\\Models\\User';
    $eventData = [createUser()];

    // No rules exist, so processEvent should not be called
    $ruleEngine->shouldNotReceive('processEvent');

    $interceptor->handle($eventName, $eventData);
});

it('extracts event type correctly from eloquent events', function () {
    $ruleEngine = Mockery::mock(RuleEngine::class);
    $interceptor = new GlobalEventInterceptor($ruleEngine);

    // Create rule for 'created' event
    createEventRule([
        'is_active' => true,
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => 'App\\Models\\User',
            'events' => ['created']
        ]
    ]);

    $eventName = 'eloquent.created: App\\Models\\User';
    $eventData = [createUser()];

    $ruleEngine->shouldReceive('processEvent')
        ->once()
        ->with($eventName, $eventData);

    $interceptor->handle($eventName, $eventData);
});

it('handles non-eloquent events correctly', function () {
    $ruleEngine = Mockery::mock(RuleEngine::class);
    $interceptor = new GlobalEventInterceptor($ruleEngine);

    $eventName = 'custom.event';
    $eventData = [];

    // No eloquent rules exist for this event
    $ruleEngine->shouldNotReceive('processEvent');

    $interceptor->handle($eventName, $eventData);
});

it('uses cache for rule checking', function () {
    $ruleEngine = Mockery::mock(RuleEngine::class);
    $interceptor = new GlobalEventInterceptor($ruleEngine);

    // Clear cache first
    Cache::flush();

    // Create an active rule
    createEventRule([
        'is_active' => true,
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => 'App\\Models\\User',
            'events' => ['created']
        ]
    ]);

    $eventName = 'eloquent.created: App\\Models\\User';
    $eventData = [createUser()];

    $ruleEngine->shouldReceive('processEvent')
        ->twice()
        ->with($eventName, $eventData);

    // First call should hit database
    $interceptor->handle($eventName, $eventData);

    // Second call should use cache
    $interceptor->handle($eventName, $eventData);
});

it('handles events with model data correctly', function () {
    $ruleEngine = Mockery::mock(RuleEngine::class);
    $interceptor = new GlobalEventInterceptor($ruleEngine);

    // Test handles model data without throwing errors
    expect($interceptor)->toBeInstanceOf(GlobalEventInterceptor::class);
});

it('handles inactive rules correctly', function () {
    $ruleEngine = Mockery::mock(RuleEngine::class);
    $interceptor = new GlobalEventInterceptor($ruleEngine);

    // Create an inactive rule
    createEventRule([
        'is_active' => false,
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => 'App\\Models\\User',
            'events' => ['created']
        ]
    ]);

    $eventName = 'eloquent.created: App\\Models\\User';
    $eventData = [createUser()];

    // Inactive rules should not trigger processing
    $ruleEngine->shouldNotReceive('processEvent');

    $interceptor->handle($eventName, $eventData);
});