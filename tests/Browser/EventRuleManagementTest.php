<?php

use Laravel\Dusk\Browser;
use Tests\Browser\DuskTestCase;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use Workbench\App\Models\User;

uses(DuskTestCase::class);

beforeEach(function () {
    // Criar utilizador para autenticação
    $this->user = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
});

it('can navigate to event rules page and see the interface', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-rules')
            ->waitForText('Event Rules')
            ->assertSee('Event Rules')
            ->assertSee('Create Event Rule')
            ->screenshot('event-rules-index');
    });
});

it('can create a new event rule through the browser interface', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-rules')
            ->waitForText('Event Rules')
            ->click('@create-button')
            ->waitForText('Create Event Rule')
            ->type('[name="name"]', 'Browser Test Rule')
            ->type('[name="description"]', 'Created via browser test')
            ->select('[name="trigger_type"]', 'eloquent')
            ->select('[name="is_active"]', '1')
            ->type('[name="priority"]', '15')
            ->press('Create')
            ->waitForText('Event Rule created successfully')
            ->assertSee('Browser Test Rule')
            ->screenshot('event-rule-created');

        // Verificar que foi criada na base de dados
        $rule = EventRule::where('name', 'Browser Test Rule')->first();
        expect($rule)->not->toBeNull();
        expect($rule->description)->toBe('Created via browser test');
        expect($rule->trigger_type)->toBe('eloquent');
        expect($rule->is_active)->toBeTrue();
        expect($rule->priority)->toBe(15);
    });
});

it('can edit an existing event rule', function () {
    // Criar regra existente
    $rule = createEventRule([
        'name' => 'Original Rule Name',
        'description' => 'Original description',
        'is_active' => true,
        'priority' => 10,
    ]);

    $this->browse(function (Browser $browser) use ($rule) {
        $browser->loginAsFilamentUser($this->user)
            ->visit("/admin/event-rules/{$rule->id}/edit")
            ->waitForText('Edit Event Rule')
            ->clear('[name="name"]')
            ->type('[name="name"]', 'Updated Rule Name')
            ->clear('[name="description"]')
            ->type('[name="description"]', 'Updated description')
            ->select('[name="is_active"]', '0')
            ->clear('[name="priority"]')
            ->type('[name="priority"]', '25')
            ->press('Save changes')
            ->waitForText('Event Rule saved successfully')
            ->screenshot('event-rule-edited');

        // Verificar as alterações na base de dados
        $rule->refresh();
        expect($rule->name)->toBe('Updated Rule Name');
        expect($rule->description)->toBe('Updated description');
        expect($rule->is_active)->toBeFalse();
        expect($rule->priority)->toBe(25);
    });
});

it('can view event rule details page', function () {
    $rule = createEventRule([
        'name' => 'Detailed View Rule',
        'description' => 'Rule for testing detailed view',
        'trigger_type' => 'eloquent',
        'trigger_config' => [
            'model' => User::class,
            'events' => ['created'],
        ],
        'is_active' => true,
        'priority' => 20,
    ]);

    // Adicionar condições e ações
    createEventRuleCondition([
        'event_rule_id' => $rule->id,
        'field_path' => 'email',
        'operator' => 'contains',
        'value' => '@test.com',
    ]);

    createEventRuleAction([
        'event_rule_id' => $rule->id,
        'type' => 'email',
        'config' => ['to' => '{{ model.email }}'],
    ]);

    $this->browse(function (Browser $browser) use ($rule) {
        $browser->loginAsFilamentUser($this->user)
            ->visit("/admin/event-rules/{$rule->id}")
            ->waitForText('View Event Rule')
            ->assertSee('Detailed View Rule')
            ->assertSee('Rule for testing detailed view')
            ->assertSee('eloquent')
            ->assertSee('Active')
            ->assertSee('Priority: 20')
            ->assertSee('Conditions')
            ->assertSee('Actions')
            ->screenshot('event-rule-view');
    });
});

it('can delete an event rule', function () {
    $rule = createEventRule([
        'name' => 'Rule to Delete',
        'is_active' => true,
    ]);

    $ruleId = $rule->id;

    $this->browse(function (Browser $browser) use ($rule, $ruleId) {
        $browser->loginAsFilamentUser($this->user)
            ->visit("/admin/event-rules/{$rule->id}/edit")
            ->waitForText('Edit Event Rule')
            ->click('@delete-button')
            ->waitForText('Are you sure?')
            ->press('Confirm')
            ->waitForText('Event Rule deleted successfully')
            ->screenshot('event-rule-deleted');

        // Verificar que foi eliminada
        expect(EventRule::find($ruleId))->toBeNull();
    });
});

it('can filter event rules by status', function () {
    // Criar regras com diferentes status
    $activeRule = createEventRule([
        'name' => 'Active Browser Rule',
        'is_active' => true,
    ]);

    $inactiveRule = createEventRule([
        'name' => 'Inactive Browser Rule',
        'is_active' => false,
    ]);

    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-rules')
            ->waitForText('Event Rules')

            // Filtrar por ativas
            ->click('[data-filter="is_active"]')
            ->click('[data-filter-value="active"]')
            ->waitForText('Active Browser Rule')
            ->assertSee('Active Browser Rule')
            ->assertDontSee('Inactive Browser Rule')
            ->screenshot('filtered-active-rules')

            // Limpar filtros
            ->click('[data-clear-filters]')
            ->waitForText('Inactive Browser Rule')

            // Filtrar por inativas
            ->click('[data-filter="is_active"]')
            ->click('[data-filter-value="inactive"]')
            ->waitForText('Inactive Browser Rule')
            ->assertSee('Inactive Browser Rule')
            ->assertDontSee('Active Browser Rule')
            ->screenshot('filtered-inactive-rules');
    });
});

it('can search event rules by name', function () {
    createEventRule(['name' => 'User Registration Handler']);
    createEventRule(['name' => 'Product Update Listener']);
    createEventRule(['name' => 'Order Notification System']);

    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-rules')
            ->waitForText('Event Rules')

            // Pesquisar por "User"
            ->type('[data-search]', 'User')
            ->waitForText('User Registration Handler')
            ->assertSee('User Registration Handler')
            ->assertDontSee('Product Update Listener')
            ->assertDontSee('Order Notification System')
            ->screenshot('search-user-rules')

            // Limpar pesquisa
            ->clear('[data-search]')
            ->waitForText('Product Update Listener')

            // Pesquisar por "Product"
            ->type('[data-search]', 'Product')
            ->waitForText('Product Update Listener')
            ->assertSee('Product Update Listener')
            ->assertDontSee('User Registration Handler')
            ->assertDontSee('Order Notification System')
            ->screenshot('search-product-rules');
    });
});

it('can bulk delete event rules', function () {
    $rule1 = createEventRule(['name' => 'Bulk Delete Rule 1']);
    $rule2 = createEventRule(['name' => 'Bulk Delete Rule 2']);
    $rule3 = createEventRule(['name' => 'Keep This Rule']);

    $this->browse(function (Browser $browser) use ($rule1, $rule2, $rule3) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-rules')
            ->waitForText('Event Rules')

            // Selecionar as primeiras duas regras
            ->check("[data-row-key=\"{$rule1->id}\"] input[type=\"checkbox\"]")
            ->check("[data-row-key=\"{$rule2->id}\"] input[type=\"checkbox\"]")

            // Executar ação em massa
            ->click('[data-bulk-action="delete"]')
            ->waitForText('Are you sure?')
            ->press('Confirm')
            ->waitForText('Event Rules deleted successfully')
            ->screenshot('bulk-delete-completed');

        // Verificar que foram eliminadas
        expect(EventRule::find($rule1->id))->toBeNull();
        expect(EventRule::find($rule2->id))->toBeNull();
        expect(EventRule::find($rule3->id))->not->toBeNull();
    });
});

it('shows validation errors when creating invalid rule', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-rules')
            ->waitForText('Event Rules')
            ->click('@create-button')
            ->waitForText('Create Event Rule')

            // Tentar criar sem preencher campos obrigatórios
            ->press('Create')
            ->waitForText('The name field is required')
            ->assertSee('The name field is required')
            ->assertSee('The trigger type field is required')
            ->screenshot('validation-errors')

            // Preencher apenas o nome e tentar novamente
            ->type('[name="name"]', 'Incomplete Rule')
            ->press('Create')
            ->waitForText('The trigger type field is required')
            ->assertSee('The trigger type field is required')
            ->assertDontSee('The name field is required')
            ->screenshot('partial-validation-errors');
    });
});

it('can navigate between different pages of event rules', function () {
    // Criar muitas regras para forçar paginação
    for ($i = 1; $i <= 25; $i++) {
        createEventRule(['name' => "Pagination Rule {$i}"]);
    }

    $this->browse(function (Browser $browser) {
        $browser->loginAsFilamentUser($this->user)
            ->visit('/admin/event-rules')
            ->waitForText('Event Rules')
            ->assertSee('Pagination Rule 1')

            // Ir para a segunda página
            ->click('[data-pagination-next]')
            ->waitForText('Pagination Rule 11')
            ->assertSee('Pagination Rule 11')
            ->assertDontSee('Pagination Rule 1')
            ->screenshot('pagination-page-2')

            // Voltar à primeira página
            ->click('[data-pagination-previous]')
            ->waitForText('Pagination Rule 1')
            ->assertSee('Pagination Rule 1')
            ->assertDontSee('Pagination Rule 11')
            ->screenshot('pagination-page-1');
    });
});