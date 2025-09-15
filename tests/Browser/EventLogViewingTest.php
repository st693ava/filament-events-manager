<?php

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventLog;
use Workbench\App\Models\User;

uses(DuskTestCase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
});

it('can navigate to event logs page and see the interface', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs')
            ->assertSee('Event Logs')
            ->assertSee('View')
            ->screenshot('event-logs-index');
    });
});

it('can view event log details', function () {
    $rule = createEventRule(['name' => 'Browser Log Test Rule']);

    $log = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create([
            'event_name' => 'user.registration.completed',
            'trigger_type' => 'eloquent',
            'model_type' => User::class,
            'model_id' => 123,
            'context' => [
                'user_data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0 Test Browser',
            ],
            'actions_executed' => [
                [
                    'type' => 'email',
                    'status' => 'success',
                    'details' => 'Welcome email sent successfully',
                    'executed_at' => now()->toISOString(),
                ],
            ],
            'execution_time_ms' => 150,
        ]);

    $this->browse(function (Browser $browser) use ($log) {
        $browser->loginAsFilamentUser($this->user)
            ->visit("/admin/event-logs/{$log->id}")
            ->waitForText('View Event Log')
            ->assertSee('user.registration.completed')
            ->assertSee('eloquent')
            ->assertSee(User::class)
            ->assertSee('John Doe')
            ->assertSee('john@example.com')
            ->assertSee('192.168.1.1')
            ->assertSee('Welcome email sent successfully')
            ->assertSee('150ms')
            ->screenshot('event-log-view');
    });
});

it('can filter event logs by trigger type', function () {
    $rule = createEventRule();

    // Criar logs com diferentes trigger types
    $eloquentLog = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->eloquentTrigger(User::class, 'created')
        ->create(['event_name' => 'eloquent.test.event']);

    $sqlLog = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->sqlTrigger('users', 'INSERT')
        ->create(['event_name' => 'sql.test.event']);

    $scheduleLog = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->scheduleTrigger()
        ->create(['event_name' => 'schedule.test.event']);

    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs')

            // Filtrar por eloquent
            ->click('[data-filter="trigger_type"]')
            ->click('[data-filter-value="eloquent"]')
            ->waitForText('eloquent.test.event')
            ->assertSee('eloquent.test.event')
            ->assertDontSee('sql.test.event')
            ->assertDontSee('schedule.test.event')
            ->screenshot('filtered-eloquent-logs')

            // Limpar filtros
            ->click('[data-clear-filters]')
            ->waitForText('sql.test.event')

            // Filtrar por sql_query
            ->click('[data-filter="trigger_type"]')
            ->click('[data-filter-value="sql_query"]')
            ->waitForText('sql.test.event')
            ->assertSee('sql.test.event')
            ->assertDontSee('eloquent.test.event')
            ->assertDontSee('schedule.test.event')
            ->screenshot('filtered-sql-logs');
    });
});

it('can search event logs by event name', function () {
    $rule = createEventRule();

    \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create(['event_name' => 'user.login.success']);

    \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create(['event_name' => 'product.price.updated']);

    \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create(['event_name' => 'order.payment.completed']);

    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs')

            // Pesquisar por "login"
            ->type('[data-search]', 'login')
            ->waitForText('user.login.success')
            ->assertSee('user.login.success')
            ->assertDontSee('product.price.updated')
            ->assertDontSee('order.payment.completed')
            ->screenshot('search-login-logs')

            // Limpar pesquisa
            ->clear('[data-search]')
            ->waitForText('product.price.updated')

            // Pesquisar por "product"
            ->type('[data-search]', 'product')
            ->waitForText('product.price.updated')
            ->assertSee('product.price.updated')
            ->assertDontSee('user.login.success')
            ->assertDontSee('order.payment.completed')
            ->screenshot('search-product-logs');
    });
});

it('can sort event logs by triggered date', function () {
    $rule = createEventRule();

    // Criar logs com datas diferentes
    $oldLog = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create([
            'event_name' => 'old.event',
            'triggered_at' => now()->subDays(7),
        ]);

    $recentLog = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create([
            'event_name' => 'recent.event',
            'triggered_at' => now()->subHours(1),
        ]);

    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs')

            // Por padrão deve mostrar os mais recentes primeiro
            ->assertSeeIn('[data-table] tbody tr:first-child', 'recent.event')

            // Clicar no cabeçalho para ordenar por data (ascendente)
            ->click('[data-sort="triggered_at"]')
            ->waitFor('[data-table]')
            ->assertSeeIn('[data-table] tbody tr:first-child', 'old.event')
            ->screenshot('sorted-logs-asc')

            // Clicar novamente para ordenar descendente
            ->click('[data-sort="triggered_at"]')
            ->waitFor('[data-table]')
            ->assertSeeIn('[data-table] tbody tr:first-child', 'recent.event')
            ->screenshot('sorted-logs-desc');
    });
});

it('shows correct execution time and status indicators', function () {
    $rule = createEventRule();

    // Log com execução rápida e sucesso
    $fastSuccessLog = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create([
            'event_name' => 'fast.success.event',
            'execution_time_ms' => 50,
            'actions_executed' => [
                [
                    'type' => 'email',
                    'status' => 'success',
                    'details' => 'Email sent successfully',
                ],
            ],
        ]);

    // Log com execução lenta e erro
    $slowErrorLog = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create([
            'event_name' => 'slow.error.event',
            'execution_time_ms' => 2500,
            'actions_executed' => [
                [
                    'type' => 'webhook',
                    'status' => 'error',
                    'details' => 'Webhook failed to send',
                ],
            ],
        ]);

    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs')

            // Verificar indicadores de tempo e status
            ->assertSee('50ms')
            ->assertSee('2500ms')
            ->assertSee('success')
            ->assertSee('error')

            // Verificar estilos visuais (se aplicável)
            ->assertVisible('[data-status="success"]')
            ->assertVisible('[data-status="error"]')
            ->screenshot('execution-indicators');
    });
});

it('can view detailed action execution results', function () {
    $rule = createEventRule();

    $log = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create([
            'event_name' => 'complex.action.event',
            'actions_executed' => [
                [
                    'type' => 'email',
                    'status' => 'success',
                    'details' => 'Welcome email sent to user@example.com',
                    'executed_at' => now()->subMinutes(5)->toISOString(),
                    'execution_time_ms' => 120,
                ],
                [
                    'type' => 'webhook',
                    'status' => 'retry',
                    'details' => 'Webhook timed out, scheduled for retry',
                    'executed_at' => now()->subMinutes(4)->toISOString(),
                    'execution_time_ms' => 5000,
                ],
                [
                    'type' => 'notification',
                    'status' => 'success',
                    'details' => 'Slack notification sent to #alerts channel',
                    'executed_at' => now()->subMinutes(3)->toISOString(),
                    'execution_time_ms' => 80,
                ],
            ],
        ]);

    $this->browse(function (Browser $browser) use ($log) {
        $browser->loginAsFilamentUser($this->user)
            ->visit("/admin/event-logs/{$log->id}")
            ->waitForText('View Event Log')
            ->assertSee('Actions Executed')
            ->assertSee('Welcome email sent to user@example.com')
            ->assertSee('Webhook timed out, scheduled for retry')
            ->assertSee('Slack notification sent to #alerts channel')
            ->assertSee('120ms')
            ->assertSee('5000ms')
            ->assertSee('80ms')
            ->screenshot('detailed-actions-view');
    });
});

it('shows context data in a readable format', function () {
    $rule = createEventRule();

    $log = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create([
            'event_name' => 'context.rich.event',
            'context' => [
                'model_data' => [
                    'id' => 123,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'preferences' => [
                        'notifications' => true,
                        'theme' => 'dark',
                    ],
                ],
                'request_data' => [
                    'ip_address' => '10.0.0.1',
                    'user_agent' => 'Chrome/91.0.4472.124',
                    'referer' => 'https://app.example.com/dashboard',
                ],
                'changes' => [
                    'name' => ['Old Name', 'Test User'],
                    'email' => ['old@example.com', 'test@example.com'],
                ],
            ],
        ]);

    $this->browse(function (Browser $browser) use ($log) {
        $browser->loginAsFilamentUser($this->user)
            ->visit("/admin/event-logs/{$log->id}")
            ->waitForText('View Event Log')
            ->assertSee('Context Data')
            ->assertSee('Test User')
            ->assertSee('test@example.com')
            ->assertSee('10.0.0.1')
            ->assertSee('Chrome/91.0.4472.124')
            ->assertSee('Old Name')
            ->assertSee('dark')
            ->screenshot('context-data-view');
    });
});

it('can paginate through large numbers of event logs', function () {
    $rule = createEventRule();

    // Criar muitos logs para testar paginação
    for ($i = 1; $i <= 30; $i++) {
        \Workbench\Database\Factories\EventLogFactory::new()
            ->forRule($rule)
            ->create(['event_name' => "pagination.test.event.{$i}"]);
    }

    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs')

            // Verificar primeira página
            ->assertSee('pagination.test.event.30') // Mais recente primeiro
            ->assertDontSee('pagination.test.event.5')

            // Ir para páginas seguintes
            ->click('[data-pagination-next]')
            ->waitFor('[data-table]')
            ->assertSee('pagination.test.event.15')
            ->assertDontSee('pagination.test.event.30')
            ->screenshot('logs-pagination-page-2')

            // Voltar à primeira página
            ->click('[data-pagination-previous]')
            ->waitFor('[data-table]')
            ->assertSee('pagination.test.event.30')
            ->assertDontSee('pagination.test.event.15')
            ->screenshot('logs-pagination-page-1');
    });
});

it('cannot create or edit event logs manually', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-logs')
            ->waitForText('Event Logs')

            // Verificar que não existe botão de criar
            ->assertMissing('@create-button')
            ->assertDontSee('Create Event Log')
            ->screenshot('no-create-button');

        // Tentar aceder diretamente ao URL de criação
        $browser->visit('/admin/event-logs/create')
            ->assertSee('404')
            ->screenshot('create-not-allowed');
    });

    // Verificar que também não é possível editar
    $rule = createEventRule();
    $log = \Workbench\Database\Factories\EventLogFactory::new()
        ->forRule($rule)
        ->create();

    $this->browse(function (Browser $browser) use ($log) {
        $browser->visit("/admin/event-logs/{$log->id}/edit")
            ->assertSee('404')
            ->screenshot('edit-not-allowed');
    });
});