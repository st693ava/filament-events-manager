<?php

use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleAction;

it('can create an event rule action', function () {
    $rule = createEventRule();
    $action = createEventRuleAction([
        'event_rule_id' => $rule->id,
        'action_type' => 'email',
        'action_config' => [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
        ],
        'sort_order' => 1,
    ]);

    expect($action)
        ->toBeInstanceOf(EventRuleAction::class)
        ->action_type->toBe('email')
        ->sort_order->toBe(1)
        ->event_rule_id->toBe($rule->id);

    expect($action->action_config)
        ->toBeArray()
        ->toHaveKey('to', 'test@example.com')
        ->toHaveKey('subject', 'Test Subject')
        ->toHaveKey('body', 'Test Body');
});

it('has correct fillable attributes', function () {
    $action = new EventRuleAction();

    expect($action->getFillable())->toBe([
        'event_rule_id',
        'action_type',
        'action_config',
        'sort_order',
    ]);
});

it('casts attributes correctly', function () {
    $action = createEventRuleAction([
        'action_config' => ['key' => 'value'],
        'sort_order' => '5',
    ]);

    expect($action->action_config)
        ->toBeArray()
        ->toHaveKey('key', 'value');

    expect($action->sort_order)->toBe(5);
});

it('belongs to event rule', function () {
    $rule = createEventRule();
    $action = createEventRuleAction(['event_rule_id' => $rule->id]);

    expect($action->eventRule)
        ->toBeInstanceOf(EventRule::class)
        ->id->toBe($rule->id);
});

it('has forRule scope', function () {
    $rule1 = createEventRule();
    $rule2 = createEventRule();

    $action1 = createEventRuleAction(['event_rule_id' => $rule1->id]);
    $action2 = createEventRuleAction(['event_rule_id' => $rule2->id]);

    expect(EventRuleAction::forRule($rule1->id)->count())->toBe(1);
    expect(EventRuleAction::forRule($rule2->id)->count())->toBe(1);
});

it('has byType scope', function () {
    createEventRuleAction(['action_type' => 'email']);
    createEventRuleAction(['action_type' => 'email']);
    createEventRuleAction(['action_type' => 'webhook']);

    expect(EventRuleAction::byType('email')->count())->toBe(2);
    expect(EventRuleAction::byType('webhook')->count())->toBe(1);
});

it('has ordered scope', function () {
    createEventRuleAction(['sort_order' => 3]);
    createEventRuleAction(['sort_order' => 1]);
    createEventRuleAction(['sort_order' => 2]);

    $actions = EventRuleAction::ordered()->get();

    expect($actions->first()->sort_order)->toBe(1);
    expect($actions->last()->sort_order)->toBe(3);
});

it('can check if action is email type', function () {
    $emailAction = createEventRuleAction(['action_type' => 'email']);
    $webhookAction = createEventRuleAction(['action_type' => 'webhook']);

    expect($emailAction->isEmailAction())->toBeTrue();
    expect($webhookAction->isEmailAction())->toBeFalse();
});

it('can check if action is webhook type', function () {
    $emailAction = createEventRuleAction(['action_type' => 'email']);
    $webhookAction = createEventRuleAction(['action_type' => 'webhook']);

    expect($emailAction->isWebhookAction())->toBeFalse();
    expect($webhookAction->isWebhookAction())->toBeTrue();
});

it('can check if action is notification type', function () {
    $emailAction = createEventRuleAction(['action_type' => 'email']);
    $notificationAction = createEventRuleAction(['action_type' => 'notification']);

    expect($emailAction->isNotificationAction())->toBeFalse();
    expect($notificationAction->isNotificationAction())->toBeTrue();
});

it('can get email recipient', function () {
    $action = createEventRuleAction([
        'action_type' => 'email',
        'action_config' => ['to' => 'test@example.com'],
    ]);

    expect($action->getEmailRecipient())->toBe('test@example.com');
});

it('returns null for email recipient on non-email actions', function () {
    $action = createEventRuleAction([
        'action_type' => 'webhook',
        'action_config' => ['url' => 'https://example.com'],
    ]);

    expect($action->getEmailRecipient())->toBeNull();
});

it('can get webhook url', function () {
    $action = createEventRuleAction([
        'action_type' => 'webhook',
        'action_config' => ['url' => 'https://example.com/webhook'],
    ]);

    expect($action->getWebhookUrl())->toBe('https://example.com/webhook');
});

it('returns null for webhook url on non-webhook actions', function () {
    $action = createEventRuleAction([
        'action_type' => 'email',
        'action_config' => ['to' => 'test@example.com'],
    ]);

    expect($action->getWebhookUrl())->toBeNull();
});

it('can validate email action configuration', function () {
    $validEmailAction = createEventRuleAction([
        'action_type' => 'email',
        'action_config' => [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
        ],
    ]);

    $invalidEmailAction = createEventRuleAction([
        'action_type' => 'email',
        'action_config' => [
            'subject' => 'Test Subject', // Missing 'to'
            'body' => 'Test Body',
        ],
    ]);

    expect($validEmailAction->hasValidConfiguration())->toBeTrue();
    expect($invalidEmailAction->hasValidConfiguration())->toBeFalse();
});

it('can validate webhook action configuration', function () {
    $validWebhookAction = createEventRuleAction([
        'action_type' => 'webhook',
        'action_config' => [
            'url' => 'https://example.com/webhook',
            'method' => 'POST',
        ],
    ]);

    $invalidWebhookAction = createEventRuleAction([
        'action_type' => 'webhook',
        'action_config' => [
            'method' => 'POST', // Missing 'url'
        ],
    ]);

    expect($validWebhookAction->hasValidConfiguration())->toBeTrue();
    expect($invalidWebhookAction->hasValidConfiguration())->toBeFalse();
});

it('can be converted to array', function () {
    $action = createEventRuleAction([
        'action_type' => 'email',
        'action_config' => ['to' => 'test@example.com'],
    ]);

    $array = $action->toArray();

    expect($array)
        ->toHaveKey('action_type', 'email')
        ->toHaveKey('action_config')
        ->toHaveKey('id')
        ->toHaveKey('created_at')
        ->toHaveKey('updated_at');
});