<?php

namespace St693ava\FilamentEventsManager\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;
use St693ava\FilamentEventsManager\Services\ConditionEvaluator;
use St693ava\FilamentEventsManager\Support\EventContext;
use St693ava\FilamentEventsManager\Tests\Models\User;
use St693ava\FilamentEventsManager\Tests\TestCase;

class ConditionEvaluatorTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new ConditionEvaluator();
    }

    public function test_evaluates_empty_conditions_as_true(): void
    {
        $conditions = new Collection();
        $data = [];
        $context = new EventContext(['event_name' => 'test']);

        $result = $this->evaluator->evaluate($conditions, $data, $context);

        $this->assertTrue($result);
    }

    public function test_evaluates_equals_condition(): void
    {
        $user = new User(['name' => 'João Silva', 'email' => 'joao@test.com']);

        $condition = new EventRuleCondition([
            'field_path' => 'name',
            'operator' => '=',
            'value' => 'João Silva',
        ]);

        $conditions = new Collection([$condition]);
        $data = [$user];
        $context = new EventContext(['event_name' => 'test']);

        $result = $this->evaluator->evaluate($conditions, $data, $context);

        $this->assertTrue($result);
    }

    public function test_evaluates_not_equals_condition(): void
    {
        $user = new User(['name' => 'João Silva', 'email' => 'joao@test.com']);

        $condition = new EventRuleCondition([
            'field_path' => 'name',
            'operator' => '!=',
            'value' => 'Maria Silva',
        ]);

        $conditions = new Collection([$condition]);
        $data = [$user];
        $context = new EventContext(['event_name' => 'test']);

        $result = $this->evaluator->evaluate($conditions, $data, $context);

        $this->assertTrue($result);
    }

    public function test_evaluates_contains_condition(): void
    {
        $user = new User(['name' => 'João Silva', 'email' => 'joao@test.com']);

        $condition = new EventRuleCondition([
            'field_path' => 'email',
            'operator' => 'contains',
            'value' => '@test.com',
        ]);

        $conditions = new Collection([$condition]);
        $data = [$user];
        $context = new EventContext(['event_name' => 'test']);

        $result = $this->evaluator->evaluate($conditions, $data, $context);

        $this->assertTrue($result);
    }

    public function test_evaluates_starts_with_condition(): void
    {
        $user = new User(['name' => 'João Silva', 'email' => 'joao@test.com']);

        $condition = new EventRuleCondition([
            'field_path' => 'email',
            'operator' => 'starts_with',
            'value' => 'joao@',
        ]);

        $conditions = new Collection([$condition]);
        $data = [$user];
        $context = new EventContext(['event_name' => 'test']);

        $result = $this->evaluator->evaluate($conditions, $data, $context);

        $this->assertTrue($result);
    }

    public function test_evaluates_numeric_comparison(): void
    {
        $user = new User(['name' => 'João Silva', 'email' => 'joao@test.com']);
        $user->age = 25; // Simular um atributo numérico

        $condition = new EventRuleCondition([
            'field_path' => 'age',
            'operator' => '>',
            'value' => 18,
        ]);

        $conditions = new Collection([$condition]);
        $data = [$user];
        $context = new EventContext(['event_name' => 'test']);

        $result = $this->evaluator->evaluate($conditions, $data, $context);

        $this->assertTrue($result);
    }

    public function test_evaluates_and_logic(): void
    {
        $user = new User(['name' => 'João Silva', 'email' => 'joao@test.com']);

        $condition1 = new EventRuleCondition([
            'field_path' => 'name',
            'operator' => '=',
            'value' => 'João Silva',
            'logical_operator' => 'AND',
            'sort_order' => 1,
        ]);

        $condition2 = new EventRuleCondition([
            'field_path' => 'email',
            'operator' => 'contains',
            'value' => '@test.com',
            'logical_operator' => 'AND',
            'sort_order' => 2,
        ]);

        $conditions = new Collection([$condition1, $condition2]);
        $data = [$user];
        $context = new EventContext(['event_name' => 'test']);

        $result = $this->evaluator->evaluate($conditions, $data, $context);

        $this->assertTrue($result);
    }

    public function test_evaluates_or_logic(): void
    {
        $user = new User(['name' => 'João Silva', 'email' => 'joao@test.com']);

        $condition1 = new EventRuleCondition([
            'field_path' => 'name',
            'operator' => '=',
            'value' => 'Maria Silva', // Falso
            'logical_operator' => 'OR',
            'sort_order' => 1,
        ]);

        $condition2 = new EventRuleCondition([
            'field_path' => 'email',
            'operator' => 'contains',
            'value' => '@test.com', // Verdadeiro
            'logical_operator' => 'AND',
            'sort_order' => 2,
        ]);

        $conditions = new Collection([$condition1, $condition2]);
        $data = [$user];
        $context = new EventContext(['event_name' => 'test']);

        $result = $this->evaluator->evaluate($conditions, $data, $context);

        $this->assertTrue($result); // Pelo menos uma condição é verdadeira
    }
}