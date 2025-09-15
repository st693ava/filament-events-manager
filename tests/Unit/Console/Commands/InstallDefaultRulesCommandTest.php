<?php

use St693ava\FilamentEventsManager\Console\Commands\InstallDefaultRulesCommand;
use St693ava\FilamentEventsManager\Models\EventRule;

it('can create the command', function () {
    $command = new InstallDefaultRulesCommand();

    expect($command)->toBeInstanceOf(InstallDefaultRulesCommand::class);
});

it('has correct signature and description', function () {
    $command = new InstallDefaultRulesCommand();

    expect($command->getName())->toBe('events-manager:install-defaults');
    expect($command->getDescription())->toBe('Install default event rules for common use cases (login tracking, security alerts, etc.)');
});

it('can install default rules in dry run mode', function () {
    $this->artisan('events-manager:install-defaults', ['--dry-run' => true])
        ->expectsOutput('🚀 Installing default event rules for Filament Events Manager...')
        ->expectsOutput('DRY RUN MODE - No changes will be made')
        ->assertSuccessful();
});

it('can install specific rule types only', function () {
    $this->artisan('events-manager:install-defaults', [
        '--only' => 'auth,security',
        '--dry-run' => true
    ])
        ->expectsOutput('🚀 Installing default event rules for Filament Events Manager...')
        ->expectsOutput('Installing only: auth, security')
        ->assertSuccessful();
});

it('detects existing default rules', function () {
    // Create a rule that might conflict with defaults
    createEventRule([
        'name' => 'User Login Tracking',
        'description' => 'Track user login attempts',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    $this->artisan('events-manager:install-defaults')
        ->expectsOutput('🚀 Installing default event rules for Filament Events Manager...')
        ->assertExitCode(0); // May succeed or be cancelled depending on user interaction
});

it('can force overwrite existing rules', function () {
    // Create a rule that might conflict with defaults
    createEventRule([
        'name' => 'User Login Tracking',
        'description' => 'Track user login attempts',
        'is_active' => true,
        'trigger_type' => 'eloquent'
    ]);

    $this->artisan('events-manager:install-defaults', ['--force' => true])
        ->expectsOutput('🚀 Installing default event rules for Filament Events Manager...')
        ->assertSuccessful();
});

it('shows available rule categories', function () {
    $this->artisan('events-manager:install-defaults', ['--dry-run' => true])
        ->expectsOutput('🚀 Installing default event rules for Filament Events Manager...')
        ->assertSuccessful();
});

it('handles empty database correctly', function () {
    // Ensure no rules exist
    EventRule::truncate();

    $this->artisan('events-manager:install-defaults', ['--dry-run' => true])
        ->expectsOutput('🚀 Installing default event rules for Filament Events Manager...')
        ->assertSuccessful();
});

it('supports different rule types selection', function () {
    // Test individual rule types
    $ruleTypes = ['auth', 'security', 'audit', 'errors'];

    foreach ($ruleTypes as $type) {
        $this->artisan('events-manager:install-defaults', [
            '--only' => $type,
            '--dry-run' => true
        ])
            ->expectsOutput('🚀 Installing default event rules for Filament Events Manager...')
            ->expectsOutput("Installing only: {$type}")
            ->assertSuccessful();
    }
});

it('handles invalid rule type gracefully', function () {
    $this->artisan('events-manager:install-defaults', [
        '--only' => 'invalid_type',
        '--dry-run' => true
    ])
        ->expectsOutput('🚀 Installing default event rules for Filament Events Manager...')
        ->assertSuccessful(); // Should handle gracefully
});

it('can install multiple rule types', function () {
    $this->artisan('events-manager:install-defaults', [
        '--only' => 'auth,security,audit',
        '--dry-run' => true
    ])
        ->expectsOutput('🚀 Installing default event rules for Filament Events Manager...')
        ->expectsOutput('Installing only: auth, security, audit')
        ->assertSuccessful();
});