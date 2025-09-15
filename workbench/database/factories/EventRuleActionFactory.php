<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleAction;

class EventRuleActionFactory extends Factory
{
    protected $model = EventRuleAction::class;

    public function definition(): array
    {
        return [
            'event_rule_id' => EventRule::factory(),
            'action_type' => fake()->randomElement(['email', 'webhook', 'notification', 'activity_log']),
            'action_config' => [
                'to' => fake()->email(),
                'subject' => fake()->sentence(),
                'body' => fake()->paragraph(),
            ],
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function forRule(EventRule $rule): static
    {
        return $this->state(fn () => ['event_rule_id' => $rule->id]);
    }

    public function emailAction(string $to = null, string $subject = null): static
    {
        return $this->state(fn () => [
            'action_type' => 'email',
            'action_config' => [
                'to' => $to ?? fake()->email(),
                'subject' => $subject ?? fake()->sentence(),
                'body' => fake()->paragraph(),
                'from' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
        ]);
    }

    public function webhookAction(string $url = null): static
    {
        return $this->state(fn () => [
            'action_type' => 'webhook',
            'action_config' => [
                'url' => $url ?? fake()->url(),
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'payload' => [
                    'event' => '{{ trigger_type }}',
                    'data' => '{{ data }}',
                ],
                'retry_attempts' => 3,
                'retry_delay' => 60,
            ],
        ]);
    }

    public function notificationAction(string $title = null): static
    {
        return $this->state(fn () => [
            'action_type' => 'notification',
            'action_config' => [
                'title' => $title ?? fake()->sentence(),
                'body' => fake()->paragraph(),
                'channels' => ['database'],
                'users' => [],
                'roles' => [],
            ],
        ]);
    }

    public function activityLogAction(string $description = null): static
    {
        return $this->state(fn () => [
            'action_type' => 'activity_log',
            'action_config' => [
                'description' => $description ?? fake()->sentence(),
                'log_name' => 'event_triggered',
                'properties' => [
                    'rule_name' => '{{ rule.name }}',
                    'triggered_at' => '{{ now }}',
                ],
            ],
        ]);
    }

    public function withOrder(int $order): static
    {
        return $this->state(fn () => ['sort_order' => $order]);
    }
}