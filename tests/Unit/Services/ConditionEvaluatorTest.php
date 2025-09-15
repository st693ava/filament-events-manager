<?php

use St693ava\FilamentEventsManager\Services\ConditionEvaluator;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;
use St693ava\FilamentEventsManager\Support\EventContext;
use Illuminate\Database\Eloquent\Collection;

it('can create the condition evaluator', function () {
    $evaluator = new ConditionEvaluator();

    expect($evaluator)->toBeInstanceOf(ConditionEvaluator::class);
});

it('returns true for empty conditions', function () {
    $evaluator = new ConditionEvaluator();
    $conditions = new Collection([]);
    $data = [createUser()];
    $context = new EventContext([
        'event_name' => 'test.event',
        'triggered_at' => now(),
        'data' => $data
    ]);

    $result = $evaluator->evaluate($conditions, $data, $context);

    expect($result)->toBeTrue();
});

it('evaluates single condition correctly', function () {
    $evaluator = new ConditionEvaluator();
    $user = createUser(['email' => 'test@example.com']);

    $condition = createEventRuleCondition([
        'field_path' => 'email',
        'operator' => 'contains',
        'value' => '@example.com',
        'logical_operator' => 'AND'
    ]);

    $conditions = new Collection([$condition]);
    $data = [$user];
    $context = new EventContext([
        'event_name' => 'user.created',
        'triggered_at' => now(),
        'data' => $data
    ]);

    $result = $evaluator->evaluate($conditions, $data, $context);

    expect($result)->toBeTrue();
});

it('evaluates equals operator correctly', function () {
    $evaluator = new ConditionEvaluator();
    $user = createUser(['name' => 'John Doe']);

    $condition = createEventRuleCondition([
        'field_path' => 'name',
        'operator' => '=',
        'value' => 'John Doe'
    ]);

    $conditions = new Collection([$condition]);
    $data = [$user];
    $context = new EventContext([
        'event_name' => 'user.created',
        'triggered_at' => now(),
        'data' => $data
    ]);

    $result = $evaluator->evaluate($conditions, $data, $context);

    expect($result)->toBeTrue();
});

it('evaluates contains operator correctly', function () {
    $evaluator = new ConditionEvaluator();
    $user = createUser(['email' => 'john@example.com']);

    $condition = createEventRuleCondition([
        'field_path' => 'email',
        'operator' => 'contains',
        'value' => '@example'
    ]);

    $conditions = new Collection([$condition]);
    $data = [$user];
    $context = new EventContext([
        'event_name' => 'user.created',
        'triggered_at' => now(),
        'data' => $data
    ]);

    $result = $evaluator->evaluate($conditions, $data, $context);

    expect($result)->toBeTrue();
});

it('returns false when conditions fail', function () {
    $evaluator = new ConditionEvaluator();
    $user = createUser(['name' => 'John Doe']);

    $condition = createEventRuleCondition([
        'field_path' => 'name',
        'operator' => '=',
        'value' => 'Jane Doe'
    ]);

    $conditions = new Collection([$condition]);
    $data = [$user];
    $context = new EventContext([
        'event_name' => 'user.created',
        'triggered_at' => now(),
        'data' => $data
    ]);

    $result = $evaluator->evaluate($conditions, $data, $context);

    expect($result)->toBeFalse();
});