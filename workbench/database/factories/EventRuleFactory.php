<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use St693ava\FilamentEventsManager\Models\EventRule;
use Workbench\App\Models\User;

class EventRuleFactory extends Factory
{
    protected $model = EventRule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'trigger_type' => fake()->randomElement(['eloquent', 'sql_query', 'schedule', 'custom']),
            'trigger_config' => [
                'model' => 'Workbench\\App\\Models\\User',
                'events' => ['created'],
            ],
            'is_active' => true,
            'priority' => fake()->numberBetween(1, 100),
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function eloquentTrigger(string $model = 'Workbench\\App\\Models\\User', array $events = ['created']): static
    {
        return $this->state(fn () => [
            'trigger_type' => 'eloquent',
            'trigger_config' => [
                'model' => $model,
                'events' => $events,
            ],
        ]);
    }

    public function sqlTrigger(array $operations = ['INSERT'], array $tables = ['users']): static
    {
        return $this->state(fn () => [
            'trigger_type' => 'sql_query',
            'trigger_config' => [
                'operations' => $operations,
                'tables' => $tables,
            ],
        ]);
    }

    public function scheduleTrigger(string $expression = '0 0 * * *'): static
    {
        return $this->state(fn () => [
            'trigger_type' => 'schedule',
            'trigger_config' => [
                'expression' => $expression,
                'timezone' => 'Europe/Lisbon',
            ],
        ]);
    }

    public function customTrigger(string $eventName): static
    {
        return $this->state(fn () => [
            'trigger_type' => 'custom',
            'trigger_config' => [
                'event_name' => $eventName,
            ],
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn () => ['priority' => fake()->numberBetween(80, 100)]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn () => ['priority' => fake()->numberBetween(1, 20)]);
    }

    public function withCreator(?User $user = null): static
    {
        return $this->state(fn () => ['created_by_user_id' => $user?->id ?? User::factory()->create()->id]);
    }
}