<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Enums\Recurrence;
use App\Models\ExpenseDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseDefinition>
 */
class ExpenseDefinitionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ExpenseDefinition>
     */
    protected $model = ExpenseDefinition::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'amount' => [
                'amount_cents' => fake()->numberBetween(1000, 50_000),
                'currency' => fake()->currencyCode(),
            ],
            'category' => fake()->randomElement([
                'equipment',
                'software',
                'studio',
                'training',
                'marketing',
                'membership',
                'travel',
                'office',
                'professional_services',
                'other',
            ]),
            'recurrence' => fake()->randomElement(Recurrence::cases()),
            'recurrence_day' => fake()->optional()->numberBetween(1, 28),
            'starts_at' => fake()->date(),
            'ends_at' => null,
            'is_active' => fake()->boolean(90),
        ];
    }
}
