<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\TestProduct;

class TestProductFactory extends Factory
{
    protected $model = TestProduct::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'price' => fake()->randomFloat(2, 10, 1000),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'category' => fake()->randomElement(['Electronics', 'Clothing', 'Books', 'Home', 'Sports']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function lowStock(int $quantity = 5): static
    {
        return $this->state(fn () => ['stock_quantity' => $quantity]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock_quantity' => 0]);
    }

    public function expensive(): static
    {
        return $this->state(fn () => ['price' => fake()->randomFloat(2, 500, 2000)]);
    }

    public function inCategory(string $category): static
    {
        return $this->state(fn () => ['category' => $category]);
    }
}