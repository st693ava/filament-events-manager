<?php

use St693ava\FilamentEventsManager\Console\Commands\TestEventRuleCommand;
use St693ava\FilamentEventsManager\Models\EventRule;

it('can create the command', function () {
    $command = new TestEventRuleCommand();

    expect($command)->toBeInstanceOf(TestEventRuleCommand::class);
});

it('has correct signature and description', function () {
    $command = new TestEventRuleCommand();

    expect($command->getName())->toBe('events:test-rule');
    expect($command->getDescription())->toBe('Test event rules with mock data');
});

it('can handle empty rule list', function () {
    // No rules exist
    $this->artisan('events:test-rule', ['--all' => true])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->expectsOutput('Nenhuma regra ativa encontrada.')
        ->assertSuccessful();
});

it('can test single rule by id', function () {
    $rule = createEventRule([
        'name' => 'Test Rule',
        'is_active' => true,
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => 'App\\Models\\User',
            'events' => ['created']
        ]
    ]);

    $this->artisan('events:test-rule', ['rule' => $rule->id])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->expectsOutput("Testando regra: {$rule->name} (ID: {$rule->id})")
        ->assertExitCode(0); // May succeed or fail depending on dependencies
});

it('can test single rule by name', function () {
    $rule = createEventRule([
        'name' => 'User Registration Rule',
        'is_active' => true,
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => 'App\\Models\\User',
            'events' => ['created']
        ]
    ]);

    $this->artisan('events:test-rule', ['rule' => 'User Registration'])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->assertExitCode(0); // May succeed or fail depending on dependencies
});

it('handles non-existent rule gracefully', function () {
    $this->artisan('events:test-rule', ['rule' => 'Non Existent Rule'])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->expectsOutput("Regra 'Non Existent Rule' não encontrada.")
        ->assertExitCode(1);
});

it('can test all active rules', function () {
    createEventRule([
        'name' => 'Rule 1',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    createEventRule([
        'name' => 'Rule 2',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    createEventRule([
        'name' => 'Inactive Rule',
        'is_active' => false,
        'trigger_type' => 'eloquent'
    ]);

    $this->artisan('events:test-rule', ['--all' => true])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->expectsOutput('Testando 2 regras ativas...')
        ->assertExitCode(0); // May succeed or fail depending on dependencies
});

it('supports dry run option', function () {
    $rule = createEventRule([
        'name' => 'Test Rule',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    $this->artisan('events:test-rule', [
        'rule' => $rule->id,
        '--dry-run' => true
    ])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->assertExitCode(0); // May succeed or fail depending on dependencies
});

it('supports detailed option', function () {
    $rule = createEventRule([
        'name' => 'Test Rule',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    $this->artisan('events:test-rule', [
        'rule' => $rule->id,
        '--detailed' => true
    ])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->assertExitCode(0); // May succeed or fail depending on dependencies
});

it('supports different output formats', function () {
    $rule = createEventRule([
        'name' => 'Test Rule',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    // Test JSON format
    $this->artisan('events:test-rule', [
        'rule' => $rule->id,
        '--format' => 'json'
    ])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->assertExitCode(0); // May succeed or fail depending on dependencies

    // Test detail format
    $this->artisan('events:test-rule', [
        'rule' => $rule->id,
        '--format' => 'detail'
    ])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->assertExitCode(0); // May succeed or fail depending on dependencies
});

it('handles custom scenario option', function () {
    $rule = createEventRule([
        'name' => 'Test Rule',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    $this->artisan('events:test-rule', [
        'rule' => $rule->id,
        '--scenario' => 'user_registration'
    ])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->assertExitCode(0); // May succeed or fail depending on dependencies
});

it('handles custom data option', function () {
    $rule = createEventRule([
        'name' => 'Test Rule',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    $customData = json_encode(['user_id' => 123, 'email' => 'test@example.com']);

    $this->artisan('events:test-rule', [
        'rule' => $rule->id,
        '--data' => $customData
    ])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->assertExitCode(0); // May succeed or fail depending on dependencies
});

it('handles invalid json data gracefully', function () {
    $rule = createEventRule([
        'name' => 'Test Rule',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    $this->artisan('events:test-rule', [
        'rule' => $rule->id,
        '--data' => 'invalid json'
    ])
        ->expectsOutput('🧪 Filament Events Manager - Rule Tester')
        ->expectsOutput('Dados JSON inválidos fornecidos, usando dados automáticos.')
        ->assertExitCode(0); // May succeed or fail depending on dependencies
});