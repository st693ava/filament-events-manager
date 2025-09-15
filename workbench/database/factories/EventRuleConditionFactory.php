<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;

class EventRuleConditionFactory extends Factory
{
    protected $model = EventRuleCondition::class;

    public function definition(): array
    {
        return [
            'event_rule_id' => EventRule::factory(),
            'field_path' => fake()->randomElement(['email', 'name', 'created_at', 'total', 'status']),
            'operator' => fake()->randomElement(['=', '!=', '>', '<', '>=', '<=', 'contains', 'starts_with', 'changed', 'was']),
            'value' => fake()->word(),
            'value_type' => 'static',
            'logical_operator' => 'AND',
            'group_id' => null,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function forRule(EventRule $rule): static
    {
        return $this->state(fn () => ['event_rule_id' => $rule->id]);
    }

    public function equals(string $field, mixed $value): static
    {
        return $this->state(fn () => [
            'field_path' => $field,
            'operator' => '=',
            'value' => $value,
        ]);
    }

    public function greaterThan(string $field, mixed $value): static
    {
        return $this->state(fn () => [
            'field_path' => $field,
            'operator' => '>',
            'value' => $value,
        ]);
    }

    public function lessThan(string $field, mixed $value): static
    {
        return $this->state(fn () => [
            'field_path' => $field,
            'operator' => '<',
            'value' => $value,
        ]);
    }

    public function contains(string $field, mixed $value): static
    {
        return $this->state(fn () => [
            'field_path' => $field,
            'operator' => 'contains',
            'value' => $value,
        ]);
    }

    public function changed(string $field): static
    {
        return $this->state(fn () => [
            'field_path' => $field,
            'operator' => 'changed',
            'value' => null,
        ]);
    }

    public function orCondition(): static
    {
        return $this->state(fn () => ['logical_operator' => 'OR']);
    }

    public function inGroup(int $groupId): static
    {
        return $this->state(fn () => ['group_id' => $groupId]);
    }

    public function withOrder(int $order): static
    {
        return $this->state(fn () => ['sort_order' => $order]);
    }
}