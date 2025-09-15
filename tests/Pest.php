<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
)->in('Feature', 'Unit');

uses(Tests\Browser\DuskTestCase::class)->in('Browser');

uses(Tests\TestCase::class)->in('Arch');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function actingAsUser(array $attributes = []): Illuminate\Contracts\Auth\Authenticatable
{
    $user = Workbench\App\Models\User::factory()->create($attributes);
    test()->actingAs($user);
    return $user;
}

function createUser(array $attributes = []): Workbench\App\Models\User
{
    return Workbench\App\Models\User::factory()->create($attributes);
}

function createTestProduct(array $attributes = []): Workbench\App\Models\TestProduct
{
    return Workbench\App\Models\TestProduct::factory()->create($attributes);
}

function createTestOrder(array $attributes = []): Workbench\App\Models\TestOrder
{
    return Workbench\App\Models\TestOrder::factory()->create($attributes);
}

function createEventRule(array $attributes = []): St693ava\FilamentEventsManager\Models\EventRule
{
    return Workbench\Database\Factories\EventRuleFactory::new()->create($attributes);
}

function createEventRuleCondition(array $attributes = []): St693ava\FilamentEventsManager\Models\EventRuleCondition
{
    return Workbench\Database\Factories\EventRuleConditionFactory::new()->create($attributes);
}

function createEventRuleAction(array $attributes = []): St693ava\FilamentEventsManager\Models\EventRuleAction
{
    return Workbench\Database\Factories\EventRuleActionFactory::new()->create($attributes);
}

function createEventLog(array $attributes = []): St693ava\FilamentEventsManager\Models\EventLog
{
    return Workbench\Database\Factories\EventLogFactory::new()->create($attributes);
}