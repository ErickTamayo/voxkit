<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Audition;
use App\Models\Contact;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Job>
     */
    protected $model = Job::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'audition_id' => fake()->boolean(50) ? Audition::factory() : null,
            'client_id' => Contact::factory(),
            'agent_id' => fake()->boolean(40) ? Contact::factory() : null,
            'project_title' => fake()->sentence(3),
            'brand_name' => fake()->optional()->company(),
            'character_name' => fake()->optional()->firstName(),
            'category' => fake()->randomElement([
                'commercial',
                'animation',
                'video_game',
                'audiobook',
                'elearning',
                'corporate',
                'documentary',
                'narration',
                'promo',
                'trailer',
                'radio_imaging',
                'ivr',
                'explainer',
                'podcast',
                'dubbing',
                'announcement',
                'meditation',
                'tv_series',
                'film',
                'coaching',
                'other',
            ]),
            'word_count' => fake()->optional()->numberBetween(50, 1000),
            'contracted_rate' => [
                'amount_cents' => fake()->numberBetween(10_000, 250_000),
                'currency' => fake()->currencyCode(),
            ],
            'rate_type' => fake()->randomElement([
                'flat',
                'hourly',
                'per_finished_hour',
                'per_word',
                'per_line',
                'buyout',
            ]),
            'estimated_hours' => fake()->optional()->randomFloat(2, 0.5, 12),
            'actual_hours' => fake()->optional()->randomFloat(2, 0.5, 12),
            'session_date' => fake()->optional()->dateTimeBetween('-1 month', '+1 month'),
            'delivery_deadline' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'delivered_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'status' => fake()->randomElement([
                'booked',
                'in_progress',
                'delivered',
                'revision',
                'completed',
                'cancelled',
            ]),
        ];
    }
}
