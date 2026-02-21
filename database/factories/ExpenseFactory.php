<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Expense>
     */
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expense_definition_id' => fake()->optional()->boolean(40) ? ExpenseDefinition::factory() : null,
            'description' => fake()->sentence(3),
            'amount' => [
                'amount_cents' => fake()->numberBetween(500, 25_000),
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
            'date' => fake()->date(),
        ];
    }
}
