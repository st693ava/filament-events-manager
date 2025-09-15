<?php

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Models\EventLog;
use Workbench\App\Models\User;

uses(DuskTestCase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
});

it('can create a rule and see it process events in real time', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-rules')
            ->waitForText('Event Rules')

            // Criar uma nova regra
            ->click('@create-button')
            ->waitForText('Create Event Rule')
            ->type('[name="name"]', 'Real-time User Registration')
            ->type('[name="description"]', 'Process new user registrations automatically')
            ->select('[name="trigger_type"]', 'eloquent')
            ->type('[name="trigger_config[model]"]', User::class)
            ->type('[name="trigger_config[events][]"]', 'created')
            ->select('[name="is_active"]', '1')
            ->type('[name="priority"]', '10')
            ->press('Create')
            ->waitForText('Event Rule created successfully')
            ->screenshot('realtime-rule-created');

        $rule = EventRule::where('name', 'Real-time User Registration')->first();

        // Adicionar uma condição
        $browser->visit("/admin/event-rules/{$rule->id}/edit")
            ->waitForText('Edit Event Rule')
            ->click('@add-condition-button')
            ->waitForText('Add Condition')
            ->type('[name="conditions[0][field_path]"]', 'email')
            ->select('[name="conditions[0][operator]"]', 'contains')
            ->type('[name="conditions[0][value]"]', '@example.com')
            ->press('Save Condition')
            ->screenshot('condition-added');

        // Adicionar uma ação
        $browser->click('@add-action-button')
            ->waitForText('Add Action')
            ->select('[name="actions[0][type]"]', 'email')
            ->type('[name="actions[0][config][to]"]', '{{ model.email }}')
            ->type('[name="actions[0][config][subject]"]', 'Welcome {{ model.name }}!')
            ->type('[name="actions[0][config][template]"]', 'welcome_email')
            ->press('Save Action')
            ->press('Save changes')
            ->waitForText('Event Rule saved successfully')
            ->screenshot('action-added');

        // Navegar para os logs para monitorizar
        $browser->visit('/admin/event-logs')
            ->waitForText('Event Logs')
            ->screenshot('logs-before-event');

        // Simular criação de um novo usuário que deve disparar a regra
        $newUser = User::create([
            'name' => 'Browser Test User',
            'email' => 'browsertest@example.com',
            'password' => bcrypt('password'),
        ]);

        // Aguardar e verificar se o log apareceu
        $browser->refresh()
            ->waitForText('Event Logs')
            ->pause(2000) // Aguardar processamento
            ->assertSee('user.created')
            ->assertSee('Browser Test User')
            ->screenshot('logs-after-event');

        // Verificar detalhes do log
        $eventLog = EventLog::where('model_id', $newUser->id)->first();
        expect($eventLog)->not->toBeNull();

        $browser->visit("/admin/event-logs/{$eventLog->id}")
            ->waitForText('View Event Log')
            ->assertSee('browsertest@example.com')
            ->assertSee('Actions Executed')
            ->assertSee('email')
            ->screenshot('realtime-log-details');
    });
});

it('can test a rule and see immediate results', function () {
    // Criar regra através do browser
    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-rules')
            ->waitForText('Event Rules')
            ->click('@create-button')
            ->waitForText('Create Event Rule')
            ->type('[name="name"]', 'Test Rule for Browser')
            ->select('[name="trigger_type"]', 'eloquent')
            ->select('[name="is_active"]', '1')
            ->press('Create')
            ->waitForText('Event Rule created successfully')
            ->screenshot('test-rule-created');

        $rule = EventRule::where('name', 'Test Rule for Browser')->first();

        // Ir para a página de teste da regra (se existir interface para isso)
        $browser->visit("/admin/event-rules/{$rule->id}")
            ->waitForText('View Event Rule')
            ->click('@test-rule-button')
            ->waitForText('Test Event Rule')
            ->select('[name="scenario"]', 'user_registration')
            ->press('Run Test')
            ->waitForText('Test completed successfully')
            ->assertSee('Execution Time')
            ->assertSee('Conditions Evaluated')
            ->assertSee('Actions Executed')
            ->screenshot('test-results');
    });
});

it('can see rule execution statistics in real time', function () {
    // Criar regra com estatísticas
    $rule = createEventRule([
        'name' => 'Statistics Test Rule',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => true,
    ]);

    createEventRuleAction([
        'event_rule_id' => $rule->id,
        'type' => 'email',
        'config' => ['to' => 'stats@test.com'],
    ]);

    // Criar alguns logs de execução
    for ($i = 1; $i <= 5; $i++) {
        \Workbench\Database\Factories\EventLogFactory::new()
            ->forRule($rule)
            ->create([
                'event_name' => "stats.test.event.{$i}",
                'execution_time_ms' => rand(50, 500),
                'actions_executed' => [
                    [
                        'type' => 'email',
                        'status' => $i % 2 === 0 ? 'success' : 'error',
                    ],
                ],
            ]);
    }

    $this->browse(function (Browser $browser) use ($rule) {
        $browser->loginAsFilamentUser($this->user)
            ->visit("/admin/event-rules/{$rule->id}")
            ->waitForText('View Event Rule')
            ->assertSee('Statistics Test Rule')
            ->assertSee('Execution Statistics')
            ->assertSee('Total Executions')
            ->assertSee('Success Rate')
            ->assertSee('Average Execution Time')
            ->screenshot('rule-statistics');
    });
});

it('can monitor system performance through dashboard', function () {
    // Criar dados para o dashboard
    $activeRules = [];
    for ($i = 1; $i <= 3; $i++) {
        $activeRules[] = createEventRule([
            'name' => "Dashboard Rule {$i}",
            'is_active' => true,
        ]);
    }

    $inactiveRule = createEventRule([
        'name' => 'Inactive Dashboard Rule',
        'is_active' => false,
    ]);

    // Criar logs recentes
    foreach ($activeRules as $rule) {
        for ($j = 1; $j <= 10; $j++) {
            \Workbench\Database\Factories\EventLogFactory::new()
                ->forRule($rule)
                ->recent()
                ->create();
        }
    }

    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin')
            ->waitForText('Dashboard')

            // Verificar widgets de estatísticas
            ->assertSee('Active Rules')
            ->assertSee('3') // 3 regras ativas
            ->assertSee('Recent Events')
            ->assertSee('30') // 30 logs criados
            ->assertSee('Performance Metrics')
            ->screenshot('dashboard-overview');

        // Verificar gráficos (se existirem)
        if ($browser->element('[data-chart="events-timeline"]')) {
            $browser->assertVisible('[data-chart="events-timeline"]')
                ->screenshot('dashboard-charts');
        }
    });
});

it('can see live updates when events are processed', function () {
    $rule = createEventRule([
        'name' => 'Live Updates Rule',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => true,
    ]);

    createEventRuleAction([
        'event_rule_id' => $rule->id,
        'type' => 'notification',
        'config' => ['message' => 'Live update test'],
    ]);

    $this->browse(function (Browser $browser) use ($rule) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs');

        // Contar logs existentes
        $initialLogCount = EventLog::count();

        // Simular criação de usuário em background
        $this->artisan('events:test-rule', ['rule' => $rule->id]);

        // Aguardar e verificar se a interface foi atualizada
        $browser->pause(3000) // Aguardar processamento
            ->refresh()
            ->waitForText('Event Logs');

        // Verificar se novo log apareceu
        $newLogCount = EventLog::count();
        expect($newLogCount)->toBeGreaterThan($initialLogCount);

        $browser->assertSee('Live Updates Rule')
            ->screenshot('live-updates-result');
    });
});

it('can handle multiple rules processing simultaneously', function () {
    // Criar múltiplas regras que processam o mesmo evento
    $rules = [];
    for ($i = 1; $i <= 3; $i++) {
        $rules[] = createEventRule([
            'name' => "Simultaneous Rule {$i}",
            'trigger_type' => 'eloquent',
            'trigger_config' => [
                'model' => User::class,
                'events' => ['created'],
            ],
            'is_active' => true,
            'priority' => $i * 10,
        ]);

        createEventRuleAction([
            'event_rule_id' => $rules[$i-1]->id,
            'type' => 'email',
            'config' => ['to' => "rule{$i}@test.com"],
        ]);
    }

    $this->browse(function (Browser $browser) use ($rules) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs');

        $initialCount = EventLog::count();

        // Criar usuário que deve disparar todas as regras
        $testUser = User::create([
            'name' => 'Simultaneous Test User',
            'email' => 'simultaneous@test.com',
            'password' => bcrypt('password'),
        ]);

        // Aguardar processamento
        $browser->pause(5000)
            ->refresh()
            ->waitForText('Event Logs');

        // Verificar que foram criados logs para todas as regras
        $newCount = EventLog::count();
        expect($newCount)->toBe($initialCount + 3);

        // Verificar que todos os logs aparecem na interface
        $browser->assertSee('Simultaneous Rule 1')
            ->assertSee('Simultaneous Rule 2')
            ->assertSee('Simultaneous Rule 3')
            ->screenshot('simultaneous-processing');
    });
});

it('shows error states when rules fail to execute', function () {
    // Criar regra com ação que vai falhar
    $rule = createEventRule([
        'name' => 'Failing Rule Test',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => true,
    ]);

    createEventRuleAction([
        'event_rule_id' => $rule->id,
        'type' => 'webhook',
        'config' => [
            'url' => 'https://invalid-webhook-url.example.com/fail',
            'method' => 'POST',
        ],
    ]);

    $this->browse(function (Browser $browser) use ($rule) {
        // Executar teste da regra que vai falhar
        $this->artisan('events:test-rule', ['rule' => $rule->id]);

        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs')
            ->pause(2000)
            ->refresh();

        // Verificar se o erro aparece na interface
        $errorLog = EventLog::where('event_rule_id', $rule->id)->first();

        if ($errorLog) {
            $browser->visit("/admin/event-logs/{$errorLog->id}")
                ->waitForText('View Event Log')
                ->assertSee('Failing Rule Test')
                ->assertSee('error')
                ->assertSee('failed')
                ->screenshot('error-state-display');
        }
    });
});