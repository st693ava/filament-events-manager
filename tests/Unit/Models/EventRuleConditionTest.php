<?php

use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;

it('can create an event rule condition', function () {
    $rule = createEventRule();
    $condition = createEventRuleCondition([
        'event_rule_id' => $rule->id,
        'field_path' => 'email',
        'operator' => 'contains',
        'value' => '@test.com',
        'logical_operator' => 'AND',
    ]);

    expect($condition)
        ->toBeInstanceOf(EventRuleCondition::class)
        ->field_path->toBe('email')
        ->operator->toBe('contains')
        ->value->toBe('@test.com')
        ->logical_operator->toBe('AND')
        ->event_rule_id->toBe($rule->id);
});

it('has correct fillable attributes', function () {
    $condition = new EventRuleCondition();

    expect($condition->getFillable())->toBe([
        'event_rule_id',
        'field_path',
        'operator',
        'value',
        'value_type',
        'logical_operator',
        'group_id',
        'sort_order',
    ]);
});

it('casts attributes correctly', function () {
    $condition = createEventRuleCondition([
        'sort_order' => '5',
        'value' => json_encode(['test' => 'data']),
    ]);

    expect($condition->sort_order)->toBe(5);
});

it('belongs to event rule', function () {
    $rule = createEventRule();
    $condition = createEventRuleCondition(['event_rule_id' => $rule->id]);

    expect($condition->eventRule)
        ->toBeInstanceOf(EventRule::class)
        ->id->toBe($rule->id);
});

it('has forRule scope', function () {
    $rule1 = createEventRule();
    $rule2 = createEventRule();

    $condition1 = createEventRuleCondition(['event_rule_id' => $rule1->id]);
    $condition2 = createEventRuleCondition(['event_rule_id' => $rule2->id]);

    expect(EventRuleCondition::forRule($rule1->id)->count())->toBe(1);
    expect(EventRuleCondition::forRule($rule2->id)->count())->toBe(1);
});

it('has byGroup scope', function () {
    createEventRuleCondition(['group_id' => 'group1']);
    createEventRuleCondition(['group_id' => 'group1']);
    createEventRuleCondition(['group_id' => 'group2']);

    expect(EventRuleCondition::byGroup('group1')->count())->toBe(2);
    expect(EventRuleCondition::byGroup('group2')->count())->toBe(1);
});

it('has ordered scope', function () {
    createEventRuleCondition(['sort_order' => 3]);
    createEventRuleCondition(['sort_order' => 1]);
    createEventRuleCondition(['sort_order' => 2]);

    $conditions = EventRuleCondition::ordered()->get();

    expect($conditions->first()->sort_order)->toBe(1);
    expect($conditions->last()->sort_order)->toBe(3);
});

it('can check if condition uses dynamic values', function () {
    $staticCondition = createEventRuleCondition(['value_type' => 'static']);
    $dynamicCondition = createEventRuleCondition(['value_type' => 'dynamic']);
    $fieldCondition = createEventRuleCondition(['value_type' => 'model_field']);

    expect($staticCondition->isDynamic())->toBeFalse();
    expect($dynamicCondition->isDynamic())->toBeTrue();
    expect($fieldCondition->isDynamic())->toBeTrue();
});

it('can check if condition requires value', function () {
    $equalCondition = createEventRuleCondition(['operator' => '=']);
    $changedCondition = createEventRuleCondition(['operator' => 'changed']);

    expect($equalCondition->requiresValue())->toBeTrue();
    expect($changedCondition->requiresValue())->toBeFalse();
});

it('can get parsed value', function () {
    $stringCondition = createEventRuleCondition(['value' => 'test']);
    $numberCondition = createEventRuleCondition(['value' => '123']);
    $arrayCondition = createEventRuleCondition(['value' => json_encode(['a', 'b', 'c'])]);

    expect($stringCondition->getParsedValue())->toBe('test');
    expect($numberCondition->getParsedValue())->toBe(123); // JSON decode converts numeric strings to integers
    expect($arrayCondition->getParsedValue())->toBe(['a', 'b', 'c']);
});

it('can validate operator and value compatibility', function () {
    $validCondition = createEventRuleCondition([
        'operator' => 'in',
        'value' => json_encode(['a', 'b', 'c']),
    ]);

    $invalidCondition = createEventRuleCondition([
        'operator' => 'in',
        'value' => 'single_value',
    ]);

    expect($validCondition->isValidOperatorValueCombination())->toBeTrue();
    expect($invalidCondition->isValidOperatorValueCombination())->toBeFalse();
});

it('can be converted to array', function () {
    $condition = createEventRuleCondition([
        'field_path' => 'email',
        'operator' => '=',
        'value' => 'test@example.com',
    ]);

    $array = $condition->toArray();

    expect($array)
        ->toHaveKey('field_path', 'email')
        ->toHaveKey('operator', '=')
        ->toHaveKey('value', 'test@example.com')
        ->toHaveKey('id')
        ->toHaveKey('created_at')
        ->toHaveKey('updated_at');
});