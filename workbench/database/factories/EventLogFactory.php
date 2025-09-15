<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use St693ava\FilamentEventsManager\Models\EventLog;
use St693ava\FilamentEventsManager\Models\EventRule;
use Workbench\App\Models\User;

class EventLogFactory extends Factory
{
    protected $model = EventLog::class;

    public function definition(): array
    {
        return [
            'event_rule_id' => EventRule::factory(),
            'trigger_type' => fake()->randomElement(['eloquent', 'sql_query', 'schedule', 'custom']),
            'model_type' => fake()->randomElement(['Workbench\\App\\Models\\User', 'Workbench\\App\\Models\\TestProduct']),
            'model_id' => fake()->numberBetween(1, 1000),
            'event_name' => fake()->randomElement(['user.created', 'product.updated', 'order.created']),
            'context' => [
                'model_data' => [
                    'name' => fake()->name(),
                    'email' => fake()->email(),
                ],
                'changes' => ['name' => fake()->name()],
            ],
            'actions_executed' => [
                [
                    'type' => 'email',
                    'status' => 'success',
                    'executed_at' => now()->toISOString(),
                    'execution_time_ms' => fake()->numberBetween(50, 200),
                ],
            ],
            'execution_time_ms' => fake()->numberBetween(10, 500),
            'triggered_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'user_id' => null,
            'user_name' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'request_url' => fake()->url(),
            'request_method' => 'POST',
            'session_id' => fake()->uuid(),
        ];
    }

    public function forRule(EventRule $rule): static
    {
        return $this->state(fn () => ['event_rule_id' => $rule->id]);
    }

    public function withUser(?User $user = null): static
    {
        return $this->state(fn () => ['user_id' => $user?->id ?? User::factory()->create()->id]);
    }


    public function eloquentTrigger(string $model, string $event = 'created'): static
    {
        return $this->state(fn () => [
            'trigger_type' => 'eloquent',
            'event_name' => strtolower(class_basename($model)) . '.' . $event,
            'trigger_data' => [
                'model' => $model,
                'id' => fake()->numberBetween(1, 1000),
                'event' => $event,
            ],
        ]);
    }

    public function sqlTrigger(string $table, string $operation = 'INSERT'): static
    {
        return $this->state(fn () => [
            'trigger_type' => 'sql_query',
            'event_name' => strtolower($operation) . '.' . $table,
            'trigger_data' => [
                'table' => $table,
                'operation' => $operation,
                'affected_rows' => fake()->numberBetween(1, 5),
            ],
        ]);
    }

    public function scheduleTrigger(): static
    {
        return $this->state(fn () => [
            'trigger_type' => 'schedule',
            'event_name' => 'scheduled.task',
            'trigger_data' => [
                'expression' => '0 0 * * *',
                'timezone' => 'Europe/Lisbon',
                'scheduled_for' => now()->toISOString(),
            ],
        ]);
    }

    public function withExecutionTime(int $timeMs): static
    {
        return $this->state(fn () => ['execution_time_ms' => $timeMs]);
    }

    public function recent(): static
    {
        return $this->state(fn () => ['triggered_at' => fake()->dateTimeBetween('-1 day', 'now')]);
    }

    public function old(): static
    {
        return $this->state(fn () => ['triggered_at' => fake()->dateTimeBetween('-1 year', '-1 month')]);
    }
}