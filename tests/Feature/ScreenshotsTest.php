<?php

use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventLog;
use Workbench\App\Models\User;

beforeEach(function () {
    // Criar usuário admin para autenticação
    $this->adminUser = createUser([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);
});

it('can capture event rules list screenshot', function () {
    // Criar algumas regras de exemplo para a screenshot
    $rules = [
        createEventRule([
            'name' => 'Welcome Email for New Users',
            'description' => 'Send welcome email when a user registers',
            'trigger_type' => 'eloquent',
            'trigger_config' => [
                'model' => User::class,
                'events' => ['created'],
            ],
            'is_active' => true,
            'priority' => 10,
        ]),
        createEventRule([
            'name' => 'Admin Notification',
            'description' => 'Notify admins when important events occur',
            'trigger_type' => 'eloquent',
            'trigger_config' => [
                'model' => User::class,
                'events' => ['updated'],
            ],
            'is_active' => true,
            'priority' => 20,
        ]),
        createEventRule([
            'name' => 'Daily Report',
            'description' => 'Generate daily reports',
            'trigger_type' => 'schedule',
            'trigger_config' => [
                'expression' => '0 9 * * *',
                'timezone' => 'Europe/Lisbon',
            ],
            'is_active' => false,
            'priority' => 5,
        ]),
    ];

    $page = visit('/admin/event-rules');
    $page->assertSee('Event Rules');
    $page->assertSee('Welcome Email for New Users');
    $page->assertSee('Admin Notification');
    $page->assertSee('Daily Report');
    $page->screenshot(filename: 'event-rules-list');
})->group('screenshots');

it('can capture event rule creation screenshot', function () {
    $page = visit('/admin/event-rules/create');
    $page->assertSee('Create Event Rule');
    $page->assertSee('Name');
    $page->assertSee('Description');
    $page->assertSee('Trigger Type');
    $page->screenshot(filename: 'event-rule-create-form');
})->group('screenshots');

it('can capture event logs list screenshot', function () {
    // Criar algumas regras e logs de exemplo
    $rule = createEventRule([
        'name' => 'User Registration Rule',
        'trigger_type' => 'eloquent',
    ]);

    // Criar logs de exemplo
    $logs = [
        createEventLog([
            'event_rule_id' => $rule->id,
            'trigger_type' => 'eloquent',
            'model_type' => User::class,
            'event_name' => 'user.created',
            'context' => ['user_id' => 1, 'email' => 'john@example.com'],
            'actions_executed' => [
                ['action' => 'email', 'status' => 'success', 'details' => 'Welcome email sent'],
            ],
            'execution_time_ms' => 150,
            'triggered_at' => now()->subHours(2),
        ]),
        createEventLog([
            'event_rule_id' => $rule->id,
            'trigger_type' => 'eloquent',
            'model_type' => User::class,
            'event_name' => 'user.updated',
            'context' => ['user_id' => 2, 'email' => 'jane@example.com'],
            'actions_executed' => [
                ['action' => 'webhook', 'status' => 'success', 'details' => 'Webhook sent to external API'],
            ],
            'execution_time_ms' => 89,
            'triggered_at' => now()->subHour(),
        ]),
    ];

    $page = visit('/admin/event-logs');
    $page->assertSee('Event Logs');
    $page->assertSee('User Registration Rule');
    $page->screenshot(filename: 'event-logs-list');
})->group('screenshots');

it('can capture dashboard screenshot', function () {
    // Criar dados de exemplo para o dashboard
    $rules = [];
    for ($i = 0; $i < 5; $i++) {
        $rules[] = createEventRule([
            'name' => "Rule Example {$i}",
            'is_active' => $i % 2 === 0,
        ]);
    }

    foreach ($rules as $rule) {
        for ($j = 0; $j < rand(2, 8); $j++) {
            createEventLog([
                'event_rule_id' => $rule->id,
                'triggered_at' => now()->subDays(rand(1, 7)),
            ]);
        }
    }

    $page = visit('/admin');
    $page->assertSee('Dashboard');
    $page->screenshot(filename: 'dashboard-overview');
})->group('screenshots');

it('can capture rule details and actions screenshot', function () {
    // Criar regra complexa com condições e ações
    $rule = createEventRule([
        'name' => 'Premium User Welcome Sequence',
        'description' => 'Multi-step welcome sequence for premium users',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => true,
        'priority' => 15,
    ]);

    // Criar condições
    createEventRuleCondition([
        'event_rule_id' => $rule->id,
        'field_path' => 'email',
        'operator' => 'contains',
        'value' => '@premium.com',
        'logical_operator' => 'AND',
        'sort_order' => 1,
    ]);

    // Criar ações
    createEventRuleAction([
        'event_rule_id' => $rule->id,
        'action_type' => 'email',
        'action_config' => [
            'to' => '{{ model.email }}',
            'subject' => 'Welcome to Premium!',
            'template' => 'premium_welcome',
        ],
        'sort_order' => 1,
    ]);

    $page = visit("/admin/event-rules/{$rule->id}/edit");
    $page->assertSee('Premium User Welcome Sequence');
    $page->assertSee('Conditions');
    $page->assertSee('Actions');
    $page->screenshot(filename: 'event-rule-edit-detailed');
})->group('screenshots');