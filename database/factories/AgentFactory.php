<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Agent>
     */
    protected $model = Agent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_name' => fake()->optional()->company(),
            'commission_rate' => fake()->optional()->numberBetween(500, 2000),
            'territories' => fake()->optional()->randomElements(['US', 'CA', 'GB', 'AU'], fake()->numberBetween(1, 3)),
            'is_exclusive' => fake()->boolean(),
            'contract_start' => fake()->optional()->date(),
            'contract_end' => fake()->optional()->date(),
        ];
    }
}
