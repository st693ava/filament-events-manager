<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use St693ava\FilamentEventsManager\Models\EventRule;

class MockDataGenerator
{
    /**
     * Generate mock data automatically based on rule configuration
     */
    public function generateAutoData(EventRule $rule): array
    {
        $triggerConfig = $rule->trigger_config ?? [];

        if ($rule->trigger_type === 'eloquent' && isset($triggerConfig['model'])) {
            return $this->generateFromModel($triggerConfig['model']);
        }

        return $this->generateGenericData();
    }

    /**
     * Generate realistic mock data
     */
    public function generateRealisticData(EventRule $rule): array
    {
        $data = $this->generateAutoData($rule);

        // Enhance with more realistic values
        return $this->enhanceWithRealisticValues($data);
    }

    /**
     * Generate edge case data for testing
     */
    public function generateEdgeCases(EventRule $rule): array
    {
        return [
            'edge_case_1' => $this->generateNullValues(),
            'edge_case_2' => $this->generateEmptyValues(),
            'edge_case_3' => $this->generateLargeValues(),
            'edge_case_4' => $this->generateSpecialCharacters(),
            'edge_case_5' => $this->generateBoundaryValues(),
        ];
    }

    /**
     * Generate mock data from a model class
     */
    private function generateFromModel(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return $this->generateGenericData();
        }

        try {
            $model = new $modelClass();

            if (! $model instanceof Model) {
                return $this->generateGenericData();
            }

            $fillable = $model->getFillable();
            $mockData = [];

            foreach ($fillable as $field) {
                $mockData[$field] = $this->generateValueForField($field);
            }

            // Add common model attributes
            $mockData['id'] = fake()->numberBetween(1, 1000);
            $mockData['created_at'] = now()->subDays(rand(1, 30));
            $mockData['updated_at'] = now()->subDays(rand(0, 7));

            // Add relationships data
            $mockData = $this->addRelationshipData($mockData, $model);

            return $mockData;

        } catch (\Exception $e) {
            return $this->generateGenericData();
        }
    }

    /**
     * Generate value for a specific field based on field name patterns
     */
    private function generateValueForField(string $field): mixed
    {
        return match (true) {
            str_contains($field, 'email') => fake()->email(),
            str_contains($field, 'name') => fake()->name(),
            str_contains($field, 'title') => fake()->sentence(3),
            str_contains($field, 'description') => fake()->paragraph(),
            str_contains($field, 'phone') => fake()->phoneNumber(),
            str_contains($field, 'address') => fake()->address(),
            str_contains($field, 'city') => fake()->city(),
            str_contains($field, 'country') => fake()->country(),
            str_contains($field, 'url') => fake()->url(),
            str_contains($field, 'slug') => fake()->slug(),
            str_contains($field, 'price') || str_contains($field, 'amount') => fake()->randomFloat(2, 10, 1000),
            str_contains($field, 'age') => fake()->numberBetween(18, 80),
            str_contains($field, 'quantity') => fake()->numberBetween(1, 100),
            str_contains($field, 'status') => fake()->randomElement(['active', 'inactive', 'pending']),
            str_contains($field, 'type') => fake()->randomElement(['basic', 'premium', 'standard']),
            str_contains($field, 'password') => 'hashed_password_' . Str::random(8),
            str_contains($field, 'token') => Str::random(32),
            str_contains($field, 'code') => strtoupper(Str::random(6)),
            str_contains($field, 'is_') => fake()->boolean(),
            str_contains($field, 'has_') => fake()->boolean(),
            str_contains($field, 'can_') => fake()->boolean(),
            str_contains($field, '_at') && str_contains($field, 'date') => fake()->dateTimeBetween('-1 year'),
            str_contains($field, '_id') => fake()->numberBetween(1, 100),
            str_ends_with($field, '_count') => fake()->numberBetween(0, 50),
            default => $this->generateRandomValue(),
        };
    }

    /**
     * Add common relationship data
     */
    private function addRelationshipData(array $mockData, Model $model): array
    {
        // Add user relationship if common
        if (method_exists($model, 'user') || isset($mockData['user_id'])) {
            $mockData['user'] = [
                'id' => $mockData['user_id'] ?? fake()->numberBetween(1, 100),
                'name' => fake()->name(),
                'email' => fake()->email(),
                'created_at' => fake()->dateTimeBetween('-2 years'),
            ];
        }

        // Add common relationships
        $relationships = ['category', 'parent', 'author', 'owner'];

        foreach ($relationships as $relation) {
            if (method_exists($model, $relation) || isset($mockData["{$relation}_id"])) {
                $mockData[$relation] = [
                    'id' => $mockData["{$relation}_id"] ?? fake()->numberBetween(1, 50),
                    'name' => fake()->words(2, true),
                    'slug' => fake()->slug(),
                ];
            }
        }

        return $mockData;
    }

    /**
     * Generate generic mock data
     */
    private function generateGenericData(): array
    {
        return [
            'id' => fake()->numberBetween(1, 1000),
            'name' => fake()->name(),
            'email' => fake()->email(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'quantity' => fake()->numberBetween(1, 100),
            'is_active' => fake()->boolean(),
            'created_at' => fake()->dateTimeBetween('-1 year'),
            'updated_at' => fake()->dateTimeBetween('-1 month'),
            'user_id' => fake()->numberBetween(1, 100),
            'user' => [
                'id' => fake()->numberBetween(1, 100),
                'name' => fake()->name(),
                'email' => fake()->email(),
            ],
        ];
    }

    /**
     * Enhance data with more realistic values
     */
    private function enhanceWithRealisticValues(array $data): array
    {
        // Add Portuguese specific data
        $portugalData = [
            'postal_code' => fake()->postcode(),
            'nif' => '2' . fake()->numberBetween(10000000, 99999999),
            'iban' => 'PT50' . fake()->numerify('################'),
            'cidade' => fake()->randomElement(['Lisboa', 'Porto', 'Braga', 'Coimbra', 'Aveiro']),
            'distrito' => fake()->randomElement(['Lisboa', 'Porto', 'Braga', 'Coimbra', 'Aveiro']),
        ];

        return array_merge($data, $portugalData);
    }

    /**
     * Generate edge case: null values
     */
    private function generateNullValues(): array
    {
        return [
            'id' => null,
            'name' => null,
            'email' => null,
            'description' => null,
            'status' => null,
            'user_id' => null,
            'user' => null,
        ];
    }

    /**
     * Generate edge case: empty values
     */
    private function generateEmptyValues(): array
    {
        return [
            'id' => 0,
            'name' => '',
            'email' => '',
            'description' => '',
            'status' => '',
            'amount' => 0,
            'quantity' => 0,
            'user' => [],
            'tags' => [],
        ];
    }

    /**
     * Generate edge case: large values
     */
    private function generateLargeValues(): array
    {
        return [
            'id' => PHP_INT_MAX,
            'name' => str_repeat('A', 1000),
            'email' => str_repeat('test', 50) . '@' . str_repeat('example', 20) . '.com',
            'description' => str_repeat('Lorem ipsum dolor sit amet. ', 100),
            'amount' => 999999999.99,
            'quantity' => PHP_INT_MAX,
            'large_array' => array_fill(0, 1000, 'item'),
        ];
    }

    /**
     * Generate edge case: special characters
     */
    private function generateSpecialCharacters(): array
    {
        return [
            'id' => 42,
            'name' => 'José da Silva "Ção" & Filhos <script>alert("test")</script>',
            'email' => 'test+special@example.com',
            'description' => 'Text with ñ, ç, á, é, í, ó, ú, â, ê, ô, à and 中文字符',
            'special_chars' => '!@#$%^&*()[]{}|;:,.<>?/~`',
            'unicode' => '🔥💎🚀⭐🎉',
            'html_content' => '<div class="test">HTML content</div>',
            'sql_injection' => "'; DROP TABLE users; --",
        ];
    }

    /**
     * Generate edge case: boundary values
     */
    private function generateBoundaryValues(): array
    {
        return [
            'zero' => 0,
            'negative' => -1,
            'max_int' => PHP_INT_MAX,
            'min_int' => PHP_INT_MIN,
            'float_max' => PHP_FLOAT_MAX,
            'float_min' => PHP_FLOAT_MIN,
            'boolean_true' => true,
            'boolean_false' => false,
            'empty_string' => '',
            'space_string' => ' ',
            'newline_string' => "\n",
            'tab_string' => "\t",
        ];
    }

    /**
     * Generate a random value
     */
    private function generateRandomValue(): mixed
    {
        $types = ['string', 'int', 'float', 'boolean', 'array'];
        $type = fake()->randomElement($types);

        return match ($type) {
            'string' => fake()->words(3, true),
            'int' => fake()->numberBetween(1, 1000),
            'float' => fake()->randomFloat(2, 1, 1000),
            'boolean' => fake()->boolean(),
            'array' => fake()->words(3),
            default => fake()->word(),
        };
    }

    /**
     * Generate scenario-based data
     */
    public function generateScenarioData(string $scenario): array
    {
        return match ($scenario) {
            'user_registration' => [
                'id' => fake()->numberBetween(1, 1000),
                'name' => fake()->name(),
                'email' => fake()->unique()->email(),
                'password' => 'hashed_password',
                'is_active' => true,
                'email_verified_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'order_created' => [
                'id' => fake()->numberBetween(1, 10000),
                'user_id' => fake()->numberBetween(1, 100),
                'total' => fake()->randomFloat(2, 10, 500),
                'status' => 'pending',
                'items_count' => fake()->numberBetween(1, 5),
                'created_at' => now(),
                'user' => [
                    'id' => fake()->numberBetween(1, 100),
                    'name' => fake()->name(),
                    'email' => fake()->email(),
                ],
            ],
            'product_updated' => [
                'id' => fake()->numberBetween(1, 1000),
                'name' => fake()->words(3, true),
                'price' => fake()->randomFloat(2, 5, 100),
                'stock' => fake()->numberBetween(0, 100),
                'is_active' => fake()->boolean(),
                'updated_at' => now(),
                'previous_price' => fake()->randomFloat(2, 5, 100),
            ],
            default => $this->generateGenericData(),
        };
    }
}