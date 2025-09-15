<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\TestOrder;
use Workbench\App\Models\User;

class TestOrderFactory extends Factory
{
    protected $model = TestOrder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'total' => fake()->randomFloat(2, 10, 1000),
            'status' => fake()->randomElement(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }

    public function shipped(): static
    {
        return $this->state(fn () => ['status' => 'shipped']);
    }

    public function delivered(): static
    {
        return $this->state(fn () => ['status' => 'delivered']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function expensive(): static
    {
        return $this->state(fn () => ['total' => fake()->randomFloat(2, 1000, 5000)]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}